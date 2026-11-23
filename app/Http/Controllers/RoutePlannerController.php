<?php

namespace App\Http\Controllers;

use App\Models\Itinerary;
use App\Models\Mall;
use App\Services\OsmService;
use App\Services\SparqlService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class RoutePlannerController extends Controller
{
    public function __construct(
    private readonly SparqlService $sparql,
    private readonly OsmService $osm,
) {}

    public function index(): View
{
    $rdfMalls = $this->sparql->query(<<<'SPARQL'
PREFIX mall: <http://www.example.org/mall#>

SELECT ?id ?name ?city ?province ?address WHERE {
    ?mall a mall:Mall ;
          mall:namaMall ?name ;
          mall:beradaDiKota ?cityRes ;
          mall:beradaDiProvinsi ?provinceRes .

    ?cityRes mall:namaKota ?city .
    ?provinceRes mall:namaProvinsi ?province .

    OPTIONAL { ?mall mall:berlokasiDi ?address }

    BIND(STRAFTER(STR(?mall), 'mall#') AS ?id)
}
ORDER BY ?city ?name
SPARQL, 0);

    $coordinateMalls = Mall::query()
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->get()
        ->keyBy('resource_uri');

    $malls = [];

    foreach ($rdfMalls as $mall) {
        $uri = 'http://www.example.org/mall#'.$mall['id'];

        $coordinate = $coordinateMalls->get($uri);

        if (! $coordinate) {
            continue;
        }

        $mall['lat'] = (float) $coordinate->latitude;
        $mall['lng'] = (float) $coordinate->longitude;

        $malls[] = $mall;
    }

    return view('planner.index', compact('malls'));
}
    
public function calculate(Request $request): View|RedirectResponse
{
    $validated = $request->validate([
        'mall_ids' => ['required', 'array', 'min:2', 'max:8'],
        'mall_ids.*' => ['required', 'string', 'regex:/\A[A-Za-z0-9_]+\z/', 'distinct'],
        'start_lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:start_lng'],
        'start_lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:start_lat'],
        'locale' => ['nullable', 'in:id,en'],
    ]);

    $locale = $validated['locale'] ?? 'id';

    // Buat URI mall yang dipilih.
    $selectedUris = array_map(
        fn (string $id): string => 'http://www.example.org/mall#'.$id,
        $validated['mall_ids'],
    );

    // Ambil data mall dari RDF.
    $selectedIris = array_map(
        fn (string $uri): string => '<'.$uri.'>',
        $selectedUris,
    );

    $rdfMalls = $this->sparql->query(<<<SPARQL
PREFIX mall: <http://www.example.org/mall#>

SELECT ?id ?name ?city ?province ?address WHERE {
    VALUES ?mall { {$this->joinIris($selectedIris)} }

    ?mall a mall:Mall ;
          mall:namaMall ?name ;
          mall:beradaDiKota ?cityRes ;
          mall:beradaDiProvinsi ?provinceRes .

    ?cityRes mall:namaKota ?city .
    ?provinceRes mall:namaProvinsi ?province .

    OPTIONAL { ?mall mall:berlokasiDi ?address }

    BIND(STRAFTER(STR(?mall), 'mall#') AS ?id)
}
SPARQL, 0);

    if (count($rdfMalls) !== count($validated['mall_ids'])) {
        throw ValidationException::withMessages([
            'mall_ids' => 'Some selected malls are missing from the RDF graph.',
        ]);
    }

    // Ambil koordinat dari PostgreSQL.
    $coordinateMalls = Mall::query()
        ->whereIn('resource_uri', $selectedUris)
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->get()
        ->keyBy('resource_uri');

    if ($coordinateMalls->count() !== count($selectedUris)) {
        throw ValidationException::withMessages([
            'mall_ids' => 'Some selected malls do not have valid coordinates.',
        ]);
    }

    // Gabungkan data RDF dan koordinat PostgreSQL.
    $stops = [];

    foreach ($rdfMalls as $mall) {
        $uri = 'http://www.example.org/mall#'.$mall['id'];
        $coordinate = $coordinateMalls->get($uri);

        if (! $coordinate) {
            continue;
        }

        $stops[] = [
            'id' => $mall['id'],
            'name' => $mall['name'],
            'city' => $mall['city'],
            'address' => $mall['address'] ?? '',
            'lat' => (float) $coordinate->latitude,
            'lng' => (float) $coordinate->longitude,
        ];
    }

    $startLat = isset($validated['start_lat'])
        ? (float) $validated['start_lat']
        : null;

    $startLng = isset($validated['start_lng'])
        ? (float) $validated['start_lng']
        : null;

    $startIsMall = $startLat === null && $startLng === null;

    // Hitung rute menggunakan OpenStreetMap / OSRM.
    $route = $this->osm->planRoute($stops, $startLat, $startLng);

    // Lengkapi informasi titik awal jika dimulai dari mall pertama.
    if ($startIsMall) {
        $firstMall = $stops[0];

        $route['origin']['address'] = $firstMall['address'];
        $route['origin']['city'] = $firstMall['city'];
    }

    // Pesan jika OSRM menggunakan estimasi jarak.
    if (($route['source'] ?? null) === 'estimated') {
        $route['notice'] = $locale === 'en'
            ? 'Road routing is unavailable. Distances and durations are estimates.'
            : 'Rute jalan tidak tersedia. Jarak dan durasi yang ditampilkan merupakan estimasi.';
    }

    $itinerary = [
        ...$route,
        'plannedAt' => now('Asia/Jakarta')->toIso8601String(),
        'startIsMall' => $startIsMall,
        'locale' => $locale,
    ];

    // Simpan ke session agar export PDF dan ICS tetap berjalan.
    $request->session()->put('planner.itinerary', $itinerary);

    // Simpan permanen jika user sudah login.
    if ($request->user()) {
        Itinerary::create([
            'user_id' => $request->user()->id,
            'session_id' => $request->session()->getId(),
            'title' => 'Mall hopping itinerary - '.now('Asia/Jakarta')->format('d M Y H:i'),
            'payload' => $itinerary,
            'starts_at' => Carbon::parse(
                $itinerary['plannedAt'],
                'Asia/Jakarta'
            ),
        ]);
    }

    return view('planner.result', $itinerary);
}


    public function exportPdf(Request $request): Response|RedirectResponse
    {
        $itinerary = $request->session()->get('planner.itinerary');
        if (! is_array($itinerary) || empty($itinerary['malls'])) {
            return redirect()->route('planner.index')->with('error', 'Plan a route before exporting an itinerary.');
        }

        return Pdf::loadView('planner.pdf', ['itinerary' => $itinerary])
            ->setPaper('a4')
            ->download('sumateramall-itinerary.pdf');
    }

    public function exportIcs(Request $request): Response|RedirectResponse
    {
        $itinerary = $request->session()->get('planner.itinerary');
        if (! is_array($itinerary) || empty($itinerary['malls'])) {
            return redirect()->route('planner.index')->with('error', 'Plan a route before exporting an itinerary.');
        }

        $start = Carbon::parse($itinerary['plannedAt'] ?? now('Asia/Jakarta'), 'Asia/Jakarta')
            ->setTime(10, 0)
            ->utc();
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//SumateraMall//Mall Hopping//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
        ];

        foreach ($itinerary['malls'] as $index => $mall) {
            $eventStart = $start->copy()->addHours($index * 2);
            $eventEnd = $eventStart->copy()->addHours(2);
            $lines = array_merge($lines, [
                'BEGIN:VEVENT',
                'UID:'.sha1(($mall['id'] ?? $mall['name']).$eventStart->format('c')).'@sumateramall.org',
                'DTSTAMP:'.now('UTC')->format('Ymd\THis\Z'),
                'DTSTART:'.$eventStart->format('Ymd\THis\Z'),
                'DTEND:'.$eventEnd->format('Ymd\THis\Z'),
                'SUMMARY:'.$this->escapeCalendarText('Visit '.$mall['name']),
                'LOCATION:'.$this->escapeCalendarText(($mall['city'] ?? '').', Sumatra, Indonesia'),
                'END:VEVENT',
            ]);
        }

        $lines[] = 'END:VCALENDAR';

        return response(implode("\r\n", $lines)."\r\n", 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="sumateramall-itinerary.ics"',
        ]);
    }

    /**
     * @param  array<int, string>  $iris
     */
    private function joinIris(array $iris): string
    {
        return implode(' ', $iris);
    }

    private function escapeCalendarText(string $value): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $value);
    }
}
