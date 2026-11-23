<?php

namespace App\Http\Controllers;

use App\Services\SparqlService;
use App\Models\Mall;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MallController extends Controller
{
    public function __construct(private readonly SparqlService $sparql) {}


public function index(Request $request): View
{
    $query = Mall::with(['city.province']);

    // Filter provinsi jika parameter URI dikirimkan.
    if ($request->filled('province')) {
        $provinceUri = $request->query('province');

        $query->whereHas('city.province', function ($provinceQuery) use ($provinceUri) {
            $provinceQuery->where('resource_uri', $provinceUri);
        });
    }

    $malls = $query->get()
        ->map(function (Mall $mall) {
            return [
                'id'   => $mall->id,
                'mall' => $mall->resource_uri,
                'name' => $mall->name,
                'city' => $mall->city?->name ?? '-',
                'province' => $mall->city?->province?->name ?? '-',
                'desc' => $mall->description,
            ];
        })
        ->sortBy([
            ['province', 'asc'],
            ['city', 'asc'],
            ['name', 'asc'],
        ])
        ->values();

    return view('malls.index', [
        'malls' => $malls,
        'grouped' => $malls->groupBy('province'),
        'provinces' => [],
    ]);
}


public function show(string $id): View
{
    $mallModel = Mall::with(['city.province'])
        ->findOrFail($id);

    $imageFiles = collect(\Illuminate\Support\Facades\File::files(
    public_path('images/malls')
));

$imageAliases = [
    'Centre Point Medan' => 'centre-point',
    'Internasional Plaza' => 'international-plaza',
    'Mall Ciputra Seraya' => 'mall-ciputra-seraya-pekanbaru',
    'Mal SKA' => 'mall-ska-pekanbaru',
    'Mal Pekanbaru' => 'mal-pekanbaru',
    'OPI Mall' => 'OPI-Mall-Palembang',
    'The Plaza Millenium' => 'plaza-millenium-medan',
    'Yuki Simpang Raya' => 'yuki-simpang-raya-medna',
];

$imageName = $imageAliases[$mallModel->name]
    ?? \Illuminate\Support\Str::slug($mallModel->name);

$imageFile = $imageFiles->first(function ($file) use ($imageName) {
    return strtolower(pathinfo($file->getFilename(), PATHINFO_FILENAME))
        === strtolower($imageName);
});

$mallImage = $imageFile
    ? asset('images/malls/'.$imageFile->getFilename())
    : null;

    $mall = [
        'image' => $mallImage,
        'id' => $mallModel->id,
        'resource_uri' => $mallModel->resource_uri,
        'name' => $mallModel->name,
        'desc' => $mallModel->description,
        'address' => $mallModel->address,
        'city' => $mallModel->city?->name ?? '-',
        'province' => $mallModel->city?->province?->name ?? '-',
        'hours' => $mallModel->opening_hours,
        'website' => $mallModel->website,
        'floors' => $mallModel->floors,
        'lat' => $mallModel->latitude,
        'lng' => $mallModel->longitude,
        'wheelchair' => null,
        'parking' => null,
        'elevator' => null,
    ];

    $stores = $mallModel->stores()
        ->with('categories')
        ->orderBy('name')
        ->get()
        ->map(function ($store) {
            return [
                'storeName' => $store->name,
                'category' => $store->category_text
                    ?: $store->categories->pluck('name')->implode(', '),
                'rating' => $store->rating,
                'status' => $store->status,
                'priceLevel' => null,
                'floor' => null,
            ];
        })
        ->values()
        ->all();

        $categories = collect($stores)
    ->pluck('category')
    ->filter()
    ->unique()
    ->values()
    ->all();

$priceLevels = collect($stores)
    ->pluck('priceLevel')
    ->filter()
    ->unique()
    ->sort()
    ->values()
    ->all();

    $id = (string) $mallModel->id;

    return view('malls.show', compact(
    'mall',
    'id',
    'stores',
    'categories',
    'priceLevels'
));
}







public function compare(Request $request): View
{
    $validated = $request->validate([
        'mall_ids' => ['required', 'array', 'min:2', 'max:2'],
        'mall_ids.*' => ['required', 'string', 'regex:/\A[a-z0-9_]+\z/'],
    ]);

    $mallIris = collect($validated['mall_ids'])
        ->map(fn ($id) => $this->sparql->internalResource(
            'http://sumateramall.org/resource/Mall/'.$id,
            'Mall'
        ));

    $unionPatterns = $mallIris
        ->map(fn ($iri) => '{ BIND(<'.$iri.'> AS ?mall) }')
        ->implode("\nUNION\n");

    $malls = $this->sparql->query(<<<SPARQL
PREFIX mm: <http://sumateramall.org/ontology#>
PREFIX schema: <http://schema.org/>

SELECT
    ?mall
    ?name
    ?city
    ?province
    ?storeCount
    ?categoryCount
WHERE {
    {
        {$unionPatterns}
    }

    ?mall schema:name ?name ;
          mm:locatedIn/mm:inCity ?cityResource .

    ?cityResource schema:name ?city .

    OPTIONAL {
        ?cityResource mm:inProvince ?provinceResource .
        ?provinceResource schema:name ?province .
    }

    OPTIONAL {
        SELECT
            ?mall
            (COUNT(DISTINCT ?store) AS ?storeCount)
            (COUNT(DISTINCT ?category) AS ?categoryCount)
        WHERE {
            ?store a mm:Store ;
                   mm:locatedIn ?mall .

            OPTIONAL {
                ?store mm:hasCategory ?category .
            }
        }
        GROUP BY ?mall
    }
}
ORDER BY ?name
SPARQL, 0);

   $malls = collect($malls)
    ->map(function ($mall) {
        $mall['id'] = basename($mall['mall']);

        $mall['city'] = $mall['city'] ?? '-';
        $mall['province'] = $mall['province'] ?? '-';
        $mall['storeCount'] = $mall['storeCount'] ?? 0;
        $mall['categoryCount'] = $mall['categoryCount'] ?? 0;

        return $mall;
    })
    ->values()
    ->all();

return view('malls.compare', [
    'malls' => $malls,
]);
}
}
