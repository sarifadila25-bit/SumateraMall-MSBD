<?php

namespace App\Http\Controllers;

use App\Models\Mall;
use App\Services\SparqlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
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
                    'id' => $mall->id,
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
            ->where(function ($query) use ($id) {
                $query->where('id', ctype_digit($id) ? (int) $id : 0)
                    ->orWhere('resource_uri', 'like', '%/'.$id);
            })
            ->firstOrFail();

        $imageFiles = collect(File::files(
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
            ?? Str::slug($mallModel->name);

        $imageFile = $imageFiles->first(function ($file) use ($imageName) {
            return strtolower(pathinfo($file->getFilename(), PATHINFO_FILENAME))
                === strtolower($imageName);
        });

        $mallImage = $imageFile
            ? asset('images/malls/'.$imageFile->getFilename())
            : null;

        $website = is_string($mallModel->website) && preg_match('/\Ahttps?:\/\//i', $mallModel->website)
            ? $mallModel->website
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
            'website' => $website,
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
            'mall_ids' => ['required', 'array', 'size:2'],
            'mall_ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:malls,id',
            ],
        ]);

        // Ambil dua mall yang dipilih dari PostgreSQL.
        $selectedMalls = Mall::whereIn('id', $validated['mall_ids'])
            ->get(['id', 'name']);

        // Siapkan pasangan nama mall dan ID database untuk dicocokkan di RDF.
        $values = $selectedMalls->map(function ($mall) {
            return '('
                .$this->sparql->literal($mall->name)
                .' '.(int) $mall->id
                .')';
        })->implode("\n");

        $sparql = <<<SPARQL
PREFIX mm: <http://sumateramall.org/ontology#>
PREFIX schema1: <http://schema.org/>

SELECT
    ?mall
    ?name
    ?city
    ?province
    ?dbId
    ?storeCount
    ?categoryCount
WHERE {
    VALUES (?selectedName ?dbId) {
        {$values}
    }

    ?mall a mm:Mall ;
          schema1:name ?name ;
          mm:inCity ?cityResource .

    FILTER(STR(?name) = STR(?selectedName))

    ?cityResource schema1:name ?city .

    OPTIONAL {
        ?mall mm:inProvince ?provinceResource .
        ?provinceResource schema1:name ?province .
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
SPARQL;

        // Cache dinonaktifkan agar hasil perbandingan selalu mengambil data terbaru.
        $results = $this->sparql->query($sparql, 0);

        $selectedById = $selectedMalls->keyBy(
            fn ($mall) => (string) $mall->id
        );

        $malls = collect($results)
            ->map(function ($mall) use ($selectedById) {
                $selected = $selectedById->get(
                    (string) ($mall['dbId'] ?? '')
                );

                if (! $selected) {
                    return null;
                }

                $mall['id'] = $selected->id;
                $mall['name'] = $selected->name;
                $mall['city'] = $mall['city'] ?? '-';
                $mall['province'] = $mall['province'] ?? '-';
                $mall['storeCount'] = (int) ($mall['storeCount'] ?? 0);
                $mall['categoryCount'] = (int) ($mall['categoryCount'] ?? 0);

                return $mall;
            })
            ->filter()
            ->values()
            ->all();

        return view('malls.compare', [
            'malls' => $malls,
        ]);
    }
}
