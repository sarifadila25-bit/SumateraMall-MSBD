<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\City;
use App\Models\Mall;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(): View
    {
        $cities = City::orderBy('name')
            ->get()
            ->map(fn ($city) => [
                'id' => $city->id,
                'name' => $city->name,
            ]);

        $categories = Category::orderBy('name')
            ->get()
            ->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
            ]);

        $malls = Mall::orderBy('name')
            ->get()
            ->map(fn ($mall) => [
                'id' => $mall->id,
                'name' => $mall->name,
            ]);

        return view('search.index', compact(
            'cities',
            'categories',
            'malls'
        ));
    }

    public function filter(Request $request): View
    {
        $validated = $request->validate([
            'city' => ['nullable', 'integer', 'exists:cities,id'],
            'mall' => ['nullable', 'integer', 'exists:malls,id'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'open_now' => ['sometimes', 'boolean'],
            'min_rating' => ['nullable', 'numeric', 'between:0,5'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $query = Store::with([
            'mall.city.province',
            'categories',
        ]);

        // Filter berdasarkan kota.
        if (! empty($validated['city'])) {
            $query->whereHas('mall.city', function ($cityQuery) use ($validated) {
                $cityQuery->where('id', $validated['city']);
            });
        }

        // Filter berdasarkan mall.
        if (! empty($validated['mall'])) {
            $query->where('mall_id', $validated['mall']);
        }

        // Filter berdasarkan kategori.
        // Menggunakan relasi kategori dan category_text
        // karena sebagian data kategori masih tersimpan sebagai teks.
        if (! empty($validated['category'])) {
            $category = Category::find($validated['category']);

            if ($category) {
                $query->where(function ($categoryQuery) use ($category) {
                    $categoryQuery
                        ->whereHas('categories', function ($relationQuery) use ($category) {
                            $relationQuery->where('categories.id', $category->id);
                        })
                        ->orWhere('category_text', 'ILIKE', '%' . $category->name . '%');
                });
            }
        }

        // Filter rating minimum.
        if (isset($validated['min_rating']) && $validated['min_rating'] !== '') {
            $query->where('rating', '>=', $validated['min_rating']);
        }

        $searchTerm = trim($validated['search'] ?? '');

        // Ambil data toko beserta mall, kota, dan kategorinya.
        $stores = $query->limit(5000)->get();

        $results = $stores->map(function (Store $store) {
            $mall = $store->mall;
            $city = $mall?->city;

            $categories = $store->categories
                ->pluck('name')
                ->filter()
                ->values();

            $categoryText = trim((string) $store->category_text);

            return [
                'id' => $store->id,
                'store' => $store->resource_uri,
                'storeName' => $store->name ?? '-',
                'mallName' => $mall?->name ?? '-',
                'city' => $city?->name ?? '-',
                'province' => $city?->province?->name ?? '-',
                'category' => $categoryText !== ''
                    ? $categoryText
                    : ($categories->implode(', ') ?: '-'),
                'rating' => $store->rating,
                'status' => $store->status,
                'floor' => $store->location,
                'address' => $mall?->address,
                'image' => $store->logo_url,
                'openingHours' => $store->opening_hours,
            ];
        });

        // Pencarian toleran typo (fuzzy matching).
        if ($searchTerm !== '') {
            $queryTokens = $this->searchTokens($searchTerm);

            $results = $results
                ->map(function (array $result) use ($queryTokens) {
                    $result['_score'] = $this->fuzzyMatchScore(
                        $queryTokens,
                        $result
                    );

                    return $result;
                })
                ->filter(fn (array $result) => $result['_score'] >= 0.55)
                ->sort(function (array $a, array $b) {
                    $scoreComparison = $b['_score'] <=> $a['_score'];

                    if ($scoreComparison !== 0) {
                        return $scoreComparison;
                    }

                    return (float) ($b['rating'] ?? 0)
                        <=> (float) ($a['rating'] ?? 0);
                })
                ->take(200)
                ->values()
                ->map(function (array $result) {
                    unset($result['_score']);

                    return $result;
                });
        } else {
            $results = $results
                ->sortBy('storeName')
                ->take(200)
                ->values();
        }

        return view('search.results', [
            'results' => $results,
            'count' => $results->count(),
            'searchTerm' => $searchTerm,
        ]);
    }

    private function searchTokens(string $search): array
    {
        $normalized = mb_strtolower($search);

        $normalized = preg_replace(
            '/[^\p{L}\p{N}\s]/u',
            ' ',
            $normalized
        );

        return array_values(array_filter(
            preg_split('/\s+/', trim($normalized))
        ));
    }

    private function fuzzyMatchScore(
        array $queryTokens,
        array $result
    ): float {
        $fields = [
            'storeName',
            'mallName',
            'city',
            'category',
            'floor',
            'address',
        ];

        $text = mb_strtolower(implode(' ', array_filter(
            array_map(fn ($field) => $result[$field] ?? '', $fields)
        )));

        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);

        $words = array_values(array_filter(
            preg_split('/\s+/', trim($text))
        ));

        if (empty($words) || empty($queryTokens)) {
            return 0;
        }

        $totalScore = 0;

        foreach ($queryTokens as $token) {
            $bestScore = 0;

            foreach ($words as $word) {
                if ($token === $word) {
                    $bestScore = 1;
                    break;
                }

                $maxLength = max(
                    mb_strlen($token),
                    mb_strlen($word)
                );

                if ($maxLength === 0) {
                    continue;
                }

                $distance = levenshtein($token, $word);

                $score = 1 - ($distance / $maxLength);

                $bestScore = max($bestScore, $score);
            }

            if ($bestScore < 0.55) {
                return 0;
            }

            $totalScore += $bestScore;
        }

        return $totalScore / count($queryTokens);
    }
}
