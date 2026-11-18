<?php

namespace App\Http\Controllers;

use App\Models\ChatLog;
use App\Models\Category;
use App\Models\City;
use App\Models\Mall;
use App\Models\Province;
use App\Models\Store;
use App\Services\GeminiService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ChatbotController extends Controller
{
    public function __construct(
        private readonly GeminiService $gemini,
    ) {}

    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'session_id' => ['nullable', 'string', 'max:80', 'regex:/\A[a-zA-Z0-9_-]+\z/'],
            'locale' => ['nullable', 'in:id,en'],
        ]);

        $message = trim($validated['message']);

        if ($message === '') {
            throw ValidationException::withMessages([
                'message' => 'Enter a question.',
            ]);
        }

        $sessionId = $validated['session_id'] ?? Str::random(24);
        $locale = $validated['locale'] ?? 'id';

        $intent = $this->parseIntent($message);
        $results = $this->getResults($intent, $message);

        $aiEnabled = $this->gemini->isConfigured();
        $notice = null;

        if ($aiEnabled) {
            try {
                $reply = $this->gemini->reply($message, $results, $locale);
            } catch (ConnectionException|RequestException|RuntimeException $exception) {
                Log::error('SumateraMall Gemini response failed.', [
                    'exception' => $exception->getMessage(),
                ]);

                return response()->json([
                    'message' => 'The AI assistant is temporarily unavailable. Please try again later.',
                ], 503);
            }
        } else {
            $reply = $this->formatReply($intent, $results, $locale);

            $notice = $locale === 'en'
                ? 'AI is not configured yet; this is a rule-based reply.'
                : 'AI belum dikonfigurasi; jawaban ini menggunakan data PostgreSQL.';
        }

        ChatLog::create([
            'session_id' => $sessionId,
            'user_message' => $message,
            'bot_reply' => $reply,
            'intent_detected' => $intent['type'],
            'sparql_query_used' => null,
        ]);

        return response()->json([
            'reply' => $reply,
            'ai_enabled' => $aiEnabled,
            'notice' => $notice,
            'intent' => $intent,
            'results_count' => count($results),
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Detect the user's question category and filters.
     *
     * @return array<string, string>
     */
    protected function parseIntent(string $message): array
    {
        $text = mb_strtolower($message);

        $intent = ['type' => 'unknown'];

        if (preg_match('/(buka sekarang|masih buka|jam buka|open now|still open|opening hours)/u', $text)) {
            $intent['type'] = 'open_now';
        } elseif (preg_match('/(budget|biaya|estimasi|cost|estimate|harga)/u', $text)) {
            $intent['type'] = 'budget';
        } elseif (preg_match('/(top|terbaik|rating|populer|best|highest rated|popular)/u', $text)) {
            $intent['type'] = 'top_rated';
        } elseif (preg_match('/(info|tentang|profil|about|profile)/u', $text)) {
            $intent['type'] = 'mall_info';
        } elseif (preg_match('/(kota|daftar mall|provinsi|mall di|mall apa saja|mall.*yang ada|sebutkan mall|city|list malls|province|malls in)/u', $text)) {
            $intent['type'] = 'list_by_region';
        } elseif (preg_match('/(cari|dimana|ada|toko|rekomendasi|find|search|where|store|recommend)/u', $text)) {
            $intent['type'] = 'find_store';
        }

        $provinces = [
            'sumatera utara' => 'Sumatera Utara',
            'sumatera barat' => 'Sumatera Barat',
            'sumatera selatan' => 'Sumatera Selatan',
            'kepulauan riau' => 'Kepulauan Riau',
            'bangka belitung' => 'Bangka Belitung',
            'aceh' => 'Aceh',
            'sumut' => 'Sumatera Utara',
            'sumbar' => 'Sumatera Barat',
            'pekanbaru' => 'Riau',
            'batam' => 'Kepulauan Riau',
            'kepri' => 'Kepulauan Riau',
            'jambi' => 'Jambi',
            'sumsel' => 'Sumatera Selatan',
            'babel' => 'Bangka Belitung',
            'bengkulu' => 'Bengkulu',
            'lampung' => 'Lampung',
            'north sumatra' => 'Sumatera Utara',
            'west sumatra' => 'Sumatera Barat',
            'south sumatra' => 'Sumatera Selatan',
            'riau islands' => 'Kepulauan Riau',
            'riau' => 'Riau',
        ];

        foreach ($provinces as $keyword => $province) {
            if (str_contains($text, $keyword)) {
                $intent['province'] = $province;
                break;
            }
        }

        $cities = [
            'banda aceh' => 'Banda Aceh',
            'medan' => 'Medan',
            'padang' => 'Padang',
            'pekanbaru' => 'Pekanbaru',
            'tanjung pinang' => 'Tanjung Pinang',
            'batam' => 'Batam',
            'jambi' => 'Jambi',
            'palembang' => 'Palembang',
            'pangkal pinang' => 'Pangkalpinang',
            'bengkulu' => 'Bengkulu',
            'bandar lampung' => 'Bandar Lampung',
        ];

        foreach ($cities as $keyword => $city) {
            if (str_contains($text, $keyword)) {
                $intent['city'] = $city;
                break;
            }
        }

        $categories = [
            'makan' => 'Food & Beverage',
            'restoran' => 'Food & Beverage',
            'cafe' => 'Food & Beverage',
            'coffee' => 'Food & Beverage',
            'baju' => 'Fashion',
            'fashion' => 'Fashion',
            'sepatu' => 'Fashion',
            'elektronik' => 'Electronics',
            'hp' => 'Electronics',
            'bioskop' => 'Entertainment',
            'nonton' => 'Entertainment',
            'supermarket' => 'Supermarket',
            'restaurant' => 'Food & Beverage',
            'food' => 'Food & Beverage',
            'dining' => 'Food & Beverage',
            'clothes' => 'Fashion',
            'shoes' => 'Fashion',
            'electronics' => 'Electronics',
            'phone' => 'Electronics',
            'cinema' => 'Entertainment',
            'movie' => 'Entertainment',
        ];

        foreach ($categories as $keyword => $category) {
            if (str_contains($text, $keyword)) {
                $intent['category'] = $category;
                break;
            }
        }

        $malls = [
            'sun plaza' => 'Sun Plaza',
            'centre point' => 'Centre Point',
            'deli park' => 'DeliPark',
            'medan mall' => 'Medan Mall',
            'nagoya hill' => 'Nagoya Hill',
            'palembang icon' => 'Palembang Icon',
            'grand batam' => 'Grand Batam',
        ];

        foreach ($malls as $keyword => $mallName) {
            if (str_contains($text, $keyword)) {
                $intent['mall'] = $mallName;
                break;
            }
        }

        return $intent;
    }

    /**
     * Retrieve verified records from PostgreSQL using Eloquent.
     *
     * @param array<string, string> $intent
     * @return array<int, array<string, string>>
     */
    protected function getResults(array $intent, string $message): array
    {
        if (in_array($intent['type'], ['budget', 'open_now', 'unknown'], true)) {
            return [];
        }

        if ($intent['type'] === 'list_by_region') {
            $query = Mall::with(['city.province']);

            if (isset($intent['province'])) {
                $query->whereHas('city.province', function ($q) use ($intent) {
                    $q->where('name', $intent['province']);
                });
            }

            if (isset($intent['city'])) {
                $query->whereHas('city', function ($q) use ($intent) {
                    $q->where('name', 'ILIKE', '%'.$intent['city'].'%');
                });
            }

            return $query->orderBy('name')
                ->limit(30)
                ->get()
                ->map(fn (Mall $mall) => [
                    'mallName' => (string) $mall->name,
                    'city' => (string) ($mall->city?->name ?? '-'),
                    'province' => (string) ($mall->city?->province?->name ?? '-'),
                ])
                ->all();
        }

        if ($intent['type'] === 'mall_info') {
            $query = Mall::with(['city.province']);

            if (isset($intent['mall'])) {
                $query->where('name', 'ILIKE', '%'.$intent['mall'].'%');
            } elseif (isset($intent['city'])) {
                $query->whereHas('city', function ($q) use ($intent) {
                    $q->where('name', 'ILIKE', '%'.$intent['city'].'%');
                });
            } else {
                return [];
            }

            return $query->limit(1)
                ->get()
                ->map(fn (Mall $mall) => [
                    'mallName' => (string) $mall->name,
                    'city' => (string) ($mall->city?->name ?? '-'),
                    'province' => (string) ($mall->city?->province?->name ?? '-'),
                    'address' => (string) ($mall->address ?? ''),
                ])
                ->all();
        }

        $query = Store::with(['mall.city.province', 'categories'])
            ->whereHas('mall');

        if (isset($intent['province'])) {
            $query->whereHas('mall.city.province', function ($q) use ($intent) {
                $q->where('name', $intent['province']);
            });
        }

        if (isset($intent['city'])) {
            $query->whereHas('mall.city', function ($q) use ($intent) {
                $q->where('name', 'ILIKE', '%'.$intent['city'].'%');
            });
        }

        if (isset($intent['mall'])) {
            $query->whereHas('mall', function ($q) use ($intent) {
                $q->where('name', 'ILIKE', '%'.$intent['mall'].'%');
            });
        }

        if (isset($intent['category'])) {
            $category = $intent['category'];

            $query->where(function ($q) use ($category) {
                $q->where('category_text', 'ILIKE', '%'.$category.'%')
                    ->orWhereHas('categories', function ($categoryQuery) use ($category) {
                        $categoryQuery->where('name', 'ILIKE', '%'.$category.'%');
                    });
            });
        }

        $searchTerm = $this->extractSearchTerm($message, $intent);

        if ($searchTerm !== '') {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'ILIKE', '%'.$searchTerm.'%')
                    ->orWhere('category_text', 'ILIKE', '%'.$searchTerm.'%')
                    ->orWhereHas('mall', function ($mallQuery) use ($searchTerm) {
                        $mallQuery->where('name', 'ILIKE', '%'.$searchTerm.'%');
                    });
            });
        }

        if ($intent['type'] === 'top_rated') {
            $query->whereNotNull('rating')
                ->orderByDesc('rating');
        } else {
            $query->orderBy('name');
        }

        return $query->limit(10)
            ->get()
            ->map(function (Store $store) {
                return [
                    'storeName' => (string) $store->name,
                    'mallName' => (string) ($store->mall?->name ?? '-'),
                    'city' => (string) ($store->mall?->city?->name ?? '-'),
                    'province' => (string) ($store->mall?->city?->province?->name ?? '-'),
                    'category' => (string) ($store->category_text ?: $store->categories->pluck('name')->implode(', ')),
                    'floor' => (string) ($store->location ?? ''),
                    'address' => (string) ($store->mall?->address ?? ''),
                    'rating' => (string) ($store->rating ?? ''),
                    'status' => (string) ($store->status ?? ''),
                    'priceLevel' => '',
                ];
            })
            ->all();
    }

    /**
     * Remove common intent, location and category words from the message.
     *
     * @param array<string, string> $intent
     */
    protected function extractSearchTerm(string $message, array $intent): string
    {
        $text = mb_strtolower($message);

       $remove = [
        'cari', 'dimana', 'di mana', 'ada', 'toko', 'mall', 'mal',
        'rekomendasi', 'find', 'search', 'where', 'store', 'recommend',
        'terbaik', 'top', 'rating', 'populer', 'best', 'highest rated',
        'info', 'tentang', 'profil', 'about', 'profile',
        'daftar', 'kota', 'provinsi', 'city', 'province', 'list',
        'tolong', 'dong', 'please', 'in', 'at', 'the', 'yang',
        'apa saja', 'apa aja', 'yang ada',
        'restoran', 'restaurant', 'makan', 'food', 'dining',
        'cafe', 'coffee', 'baju', 'fashion', 'sepatu',
        'elektronik', 'electronics', 'hp', 'bioskop', 'nonton',
        'supermarket', 'clothes', 'shoes',
        ];

        foreach (['province', 'city', 'category', 'mall'] as $key) {
            if (isset($intent[$key])) {
                $remove[] = mb_strtolower($intent[$key]);
            }
        }

        foreach ($remove as $word) {
            $text = preg_replace(
                '/'.preg_quote($word, '/').'/u',
                ' ',
                $text
            ) ?? $text;
        }

        $text = preg_replace('/[^\p{L}\p{N}\s&-]/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * Build a rule-based response when Gemini is not configured.
     *
     * @param array<string, string> $intent
     * @param array<int, array<string, string>> $results
     */
    protected function formatReply(
        array $intent,
        array $results,
        string $locale = 'id'
    ): string {
        $english = $locale === 'en';

        if ($intent['type'] === 'budget') {
            return $english
                ? 'Price information is not available in the current store database, so I cannot provide a reliable budget estimate.'
                : 'Informasi harga belum tersedia di database toko, jadi aku belum bisa memberikan estimasi budget yang akurat.';
        }

        if ($intent['type'] === 'open_now') {
            return $english
                ? 'I cannot confirm which stores are currently open because structured opening-hour data is not yet available.'
                : 'Aku belum bisa memastikan toko yang sedang buka karena data jam operasional terstruktur belum tersedia.';
        }

        if ($results === []) {
            return $english
                ? 'Sorry, I could not find matching data. Try different keywords.'
                : 'Maaf, aku tidak menemukan data yang sesuai. Coba kata kunci lain.';
        }

        return match ($intent['type']) {
            'find_store' => ($english ? 'I found ' : 'Aku menemukan ')
                .count($results)
                .($english ? " stores:\n\n" : " toko:\n\n")
                .implode("\n", array_map(
                    fn (array $row) => '- '.$row['storeName']
                        .($english ? ' at ' : ' di ')
                        .$row['mallName'].' ('.$row['city'].')',
                    array_slice($results, 0, 5)
                )),

            'top_rated' => ($english ? "Top-rated stores:\n\n" : "Toko dengan rating tertinggi:\n\n")
                .implode("\n", array_map(
                    fn (array $row) => '- '.$row['storeName']
                        .' ('.$row['mallName'].', '.$row['city'].')'
                        .($row['rating'] !== '' ? ' — '.$row['rating'] : ''),
                    array_slice($results, 0, 5)
                )),

            'list_by_region' => ($english ? "Mall directory:\n\n" : "Daftar mall:\n\n")
                .implode("\n", array_map(
                    fn (array $row) => '- '.$row['mallName']
                        .' ('.$row['city'].', '.$row['province'].')',
                    array_slice($results, 0, 20)
                )),

            'mall_info' => $english
                ? $results[0]['mallName'].' is in '.$results[0]['city'].', '.$results[0]['province']
                    .($results[0]['address'] !== '' ? ' — '.$results[0]['address'] : '')
                : $results[0]['mallName'].' berada di '.$results[0]['city'].', '.$results[0]['province']
                    .($results[0]['address'] !== '' ? ' — '.$results[0]['address'] : ''),

            default => $english
                ? 'I found '.$results === [] ? '0 results.' : count($results).' results. Try asking a more specific question.'
                : 'Aku menemukan '.count($results).' hasil. Coba pertanyaannya lebih spesifik.',
        };
    }
}
