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
    $mallModel = Mall::with([
        'city.province',
        'stores.categories',
    ])->findOrFail($id);

    $mall = [
        'resource_uri' => $mallModel->resource_uri,
        'name' => $mallModel->name,
        'desc' => $mallModel->description,
        'city' => $mallModel->city?->name ?? '-',
        'province' => $mallModel->city?->province?->name ?? '-',
        'address' => $mallModel->address,
        'hours' => $mallModel->opening_hours,
        'website' => $mallModel->website,
        'floors' => $mallModel->floors,
        'lat' => null,
        'lng' => null,
        'wheelchair' => null,
        'parking' => null,
        'elevator' => null,
    ];

    if ($mall['website']) {
        $scheme = parse_url($mall['website'], PHP_URL_SCHEME);

        if (! in_array(strtolower((string) $scheme), ['http', 'https'], true)) {
            $mall['website'] = null;
        }
    }

    $stores = $mallModel->stores->map(function ($store) {
        $categories = $store->categories
            ->pluck('name')
            ->filter()
            ->values();

        return [
            'store' => $store->resource_uri,
            'storeName' => $store->name,
            'category' => $store->category_text
                ?: $categories->implode(', '),
            'rating' => $store->rating,
            'status' => $store->status,
            'priceLevel' => null,
            'floor' => $store->location,
            'image' => $store->logo_url,
            'lat' => null,
            'lng' => null,
        ];
    })->sortBy('storeName')->values();

    return view('malls.show', [
        'mall' => $mall,
        'id' => $mallModel->id,
        'stores' => $stores,
    ]);
}



public function compare(Request $request): View
{
    $validated = $request->validate([
        'mall_ids' => ['required', 'array', 'min:2', 'max:3'],
        'mall_ids.*' => ['required', 'integer', 'distinct', 'exists:malls,id'],
    ]);

    $selectedIds = $validated['mall_ids'];

    $mallModels = Mall::with([
        'city.province',
        'stores.categories',
    ])
        ->whereIn('id', $selectedIds)
        ->get()
        ->keyBy('id');

    $malls = collect($selectedIds)
        ->map(function ($id) use ($mallModels) {
            $mall = $mallModels->get($id);

            if (! $mall) {
                return null;
            }

            return [
                'id' => $mall->id,
                'mall' => $mall->resource_uri,
                'name' => $mall->name,
                'city' => $mall->city?->name ?? '-',
                'province' => $mall->city?->province?->name ?? '-',
                'lat' => null,
                'lng' => null,
                'storeCount' => $mall->stores->count(),
                'categoryCount' => $mall->stores
    ->map(function ($store) {
        $categoryText = trim((string) $store->category_text);

        if ($categoryText !== '') {
            return trim(explode(',', $categoryText)[0]);
        }

        return $store->categories->first()?->name;
    })
    ->filter()
    ->unique(fn ($category) => mb_strtolower($category))
    ->count(),
            ];
        })
        ->filter()
        ->values();

    return view('malls.compare', [
        'malls' => $malls,
    ]);
}
}
