<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\City;
use App\Models\Mall;
use App\Models\Store;
use Carbon\Carbon;
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
        if (!empty($validated['city'])) {
            $query->whereHas('mall', function ($mallQuery) use ($validated) {
                $mallQuery->where('city_id', $validated['city']);
            });
        }

        // Filter berdasarkan mall.
        if (!empty($validated['mall'])) {
            $query->where('mall_id', $validated['mall']);
        }

        // Filter berdasarkan kategori.
        if (!empty($validated['category'])) {
            $category = Category::findOrFail($validated['category']);

            $query->where(function ($storeQuery) use ($category) {
                $storeQuery
                    ->whereRaw(
                        'LOWER(category_text) = ?',
                        [mb_strtolower($category->name)]
                    )
                    ->orWhereHas('categories', function ($categoryQuery) use ($category) {
                        $categoryQuery->where('categories.id', $category->id);
                    });
            });
        }

        // Filter rating minimum.
        if (isset($validated['min_rating'])) {
            $query->where('rating', '>=', $validated['min_rating']);
        }

        $stores = $query->get();

        // Ubah data PostgreSQL ke format yang digunakan Blade.
        $results = $stores->map(function (Store $store) {
            return [
                'id' => $store->id,
                'storeName' => $store->name,
                'mallName' => $store->mall?->name ?? '-',
                'mall_id' => $store->mall_id,
                'city' => $store->mall?->city?->name ?? '-',
                'province' => $store->mall?->city?->province?->name ?? '-',
                'category' => $store->category_text
                    ?: $store->categories->pluck('name')->implode(', '),
                'rating' => $store->rating,
                'status' => $store->status,
                'openingHours' => $store->opening_hours,
                'location' => $store->location,
            ];
        });

        $now = Carbon::now('Asia/Jakarta');

        // Hanya tampilkan toko yang jam operasionalnya bisa diverifikasi.
        if ($request->boolean('open_now')) {
            $results = $results->filter(function (array $store) use ($now) {
                return $store['status'] !== 'Tutup Permanen'
                    && !empty($store['openingHours'])
                    && $this->isOpenNow($store['openingHours'], $now);
            });
        }

        $searchTerm = trim($validated['search'] ?? '');

        // Pencarian dengan toleransi typo.
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
                ->sortByDesc('_score')
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

    private function isOpenNow(string $hours, Carbon $now): bool
    {
        if (!preg_match(
            '/(\d{1,2}):(\d{2})\s*-\s*(\d{1,2}):(\d{2})/',
            $hours,
            $matches
        )) {
            return false;
        }

        $openingHour = (int) $matches[1];
        $openingMinute = (int) $matches[2];
        $closingHour = (int) $matches[3];
        $closingMinute = (int) $matches[4];

        if (
            $openingHour > 23 ||
            $closingHour > 23 ||
            $openingMinute > 59 ||
            $closingMinute > 59
        ) {
            return false;
        }

        $opensAt = $openingHour * 60 + $openingMinute;
        $closesAt = $closingHour * 60 + $closingMinute;
        $currentTime = $now->hour * 60 + $now->minute;

        if ($closesAt === 0) {
            $closesAt = 1440;
        }

        if ($opensAt === $closesAt) {
            return false;
        }

        return $opensAt < $closesAt
            ? $currentTime >= $opensAt && $currentTime < $closesAt
            : $currentTime >= $opensAt || $currentTime < $closesAt;
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
            'location',
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
