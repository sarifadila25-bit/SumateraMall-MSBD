<?php

namespace App\Http\Controllers;

use App\Services\SparqlService;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;

class HomeController extends Controller
{
    public function __construct(private readonly SparqlService $sparql) {}

    public function index(): View
    {
        $dataUnavailable = false;

        try {
            $malls = $this->sparql->query(<<<'SPARQL'
PREFIX mm: <http://sumateramall.org/ontology#>
PREFIX schema: <http://schema.org/>
SELECT DISTINCT ?mall ?name ?city ?province ?address ?hours
WHERE {
    ?mall a mm:Mall ;
          schema:name ?name ;
          mm:inCity ?cityResource ;
          mm:inProvince ?provinceResource .
    ?cityResource schema:name ?city .
    ?provinceResource schema:name ?province .
    OPTIONAL { ?mall schema:address ?address }
    OPTIONAL { ?mall mm:openingHoursText ?hours }
}
ORDER BY ?city ?name
SPARQL, 5, 8);
        } catch (ConnectionException|RequestException|RuntimeException $exception) {
            Log::warning('Homepage mall data is temporarily unavailable.', [
                'exception' => $exception,
            ]);
            $malls = [];
            $dataUnavailable = true;
        }

        $now = Carbon::now('Asia/Jakarta');
        $malls = collect($malls)
            ->map(function (array $mall) use ($now): array {
                $mall['id'] = substr($mall['mall'], strrpos($mall['mall'], '/') + 1);
                $mall['is_open'] = isset($mall['hours']) && $this->isOpenNow($mall['hours'], $now);

                return $mall;
            });
        $dataEmpty = ! $dataUnavailable && $malls->isEmpty();

        return view('home', [
            'openMalls' => $malls->filter(fn (array $mall): bool => $mall['is_open'])->values(),
            'malls' => $malls->values(),
            'cities' => $malls->pluck('city')->filter()->unique()->sort()->values(),
            'dataUnavailable' => $dataUnavailable,
            'dataEmpty' => $dataEmpty,
        ]);
    }

    private function isOpenNow(string $hours, Carbon $now): bool
    {
        if (preg_match_all('/(?<!\d)(\d{1,2})\s*[.:,]\s*(\d{2})(?!\d)/u', $hours, $matches, PREG_SET_ORDER) < 2) {
            return false;
        }

        $openingHour = (int) $matches[0][1];
        $openingMinute = (int) $matches[0][2];
        $closingHour = (int) $matches[1][1];
        $closingMinute = (int) $matches[1][2];

        if ($openingHour > 23 || $closingHour > 23 || $openingMinute > 59 || $closingMinute > 59) {
            return false;
        }

        $opensAt = $openingHour * 60 + $openingMinute;
        $closesAt = $closingHour * 60 + $closingMinute;
        $currentTime = $now->hour * 60 + $now->minute;

        if ($closesAt === 0) {
            $closesAt = 24 * 60;
        }

        if ($opensAt === $closesAt) {
            return false;
        }

        return $opensAt < $closesAt
            ? $currentTime >= $opensAt && $currentTime < $closesAt
            : $currentTime >= $opensAt || $currentTime < $closesAt;
    }
}
