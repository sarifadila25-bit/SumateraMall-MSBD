<?php

namespace App\Http\Controllers;

use App\Models\ChatLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ChatbotController extends Controller
{
    /**
     * @var array<string, mixed>
     */
    private array $questionCatalog;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $ttlDataset = null;

    public function __construct()
    {
        $catalog = File::json(resource_path('data/chatbot-questions.json'));

        if (! is_array($catalog)
            || ! is_array($catalog['intents'] ?? null)
            || ! is_array($catalog['categories'] ?? null)
            || ! is_array($catalog['suggestions'] ?? null)) {
            throw new RuntimeException('The chatbot question catalog is invalid.');
        }

        $this->questionCatalog = $catalog;
    }

    public function history(Request $request): JsonResponse
    {
        $history = $request->user()
            ->chatLogs()
            ->orderByDesc('id')
            ->limit(50)
            ->get([
                'id',
                'user_message',
                'bot_reply',
                'created_at',
            ])
            ->reverse()
            ->values()
            ->map(fn (ChatLog $chat) => [
                'message' => $chat->user_message,
                'reply' => $chat->bot_reply,
                'created_at' => $chat->created_at?->format('d/m/Y H:i'),
            ]);

        return response()->json([
            'history' => $history,
        ]);
    }

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

        try {
            $results = $this->getResults($intent, $message);
        } catch (\JsonException|RuntimeException $exception) {
            Log::warning('SumateraMall chatbot could not read the TTL dataset.', [
                'exception' => $exception->getMessage(),
                'intent' => $intent['type'],
            ]);

            return response()->json([
                'message' => $locale === 'en'
                    ? 'Mall data is unavailable. Please try again later.'
                    : 'Data mall tidak tersedia. Silakan coba lagi nanti.',
            ], 503);
        }

        $reply = $this->formatReply($intent, $results, $locale);

        ChatLog::create([
            'user_id' => $request->user()?->id,
            'session_id' => $sessionId,
            'user_message' => $message,
            'bot_reply' => $reply,
            'intent_detected' => $intent['type'],
            'sparql_query_used' => null,
        ]);

        return response()->json([
            'reply' => $reply,
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

        foreach ($this->questionCatalog['intents'] ?? [] as $type => $keywords) {
            if (! is_string($type) || ! is_array($keywords)) {
                continue;
            }

            foreach ($keywords as $keyword) {
                if (is_string($keyword) && str_contains($text, mb_strtolower($keyword))) {
                    $intent['type'] = $type;
                    break 2;
                }
            }
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

        foreach ($this->questionCatalog['categories'] ?? [] as $category => $keywords) {
            if (! is_string($category) || ! is_array($keywords)) {
                continue;
            }

            foreach ($keywords as $keyword) {
                if (is_string($keyword) && str_contains($text, mb_strtolower($keyword))) {
                    $intent['category'] = $category;
                    break 2;
                }
            }
        }

        if ($intent['type'] === 'unknown' && isset($intent['category'])) {
            $intent['type'] = 'find_store';
        } elseif ($intent['type'] === 'list_by_region' && isset($intent['category'])) {
            $intent['type'] = 'find_store';
        }

        $malls = [
            'focal point medan' => 'Medan Focal Point Mall',
            'medan focal point mall' => 'Medan Focal Point Mall',
            'focal point' => 'Medan Focal Point Mall',
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
     * Retrieve records from the generated JSON export of the complete TTL dataset.
     *
     * @param  array<string, string>  $intent
     * @return array<int, array<string, mixed>>
     */
    protected function getResults(array $intent, string $message): array
    {
        if (in_array($intent['type'], ['budget', 'nearest_mall', 'nearest_store', 'open_now', 'unknown'], true)) {
            return [];
        }

        if ($intent['type'] === 'mall_info'
            && ! isset($intent['mall'])
            && ! isset($intent['city'])
            && ! isset($intent['province'])
            && $this->extractMallSearchTerm($message, $intent) === '') {
            return [];
        }

        $data = $this->loadTtlDataset();
        $provinceNames = [];

        foreach ($data['provinces'] as $province) {
            if (is_array($province) && is_string($province['resource_uri'] ?? null)) {
                $provinceNames[$province['resource_uri']] = (string) ($province['name'] ?? '');
            }
        }

        $cities = [];

        foreach ($data['cities'] as $city) {
            if (! is_array($city) || ! is_string($city['resource_uri'] ?? null)) {
                continue;
            }

            $cities[$city['resource_uri']] = [
                'name' => (string) ($city['name'] ?? ''),
                'province' => $provinceNames[$city['province_uri'] ?? ''] ?? '',
            ];
        }

        $categoryNames = [];

        foreach ($data['categories'] as $category) {
            if (is_array($category) && is_string($category['resource_uri'] ?? null)) {
                $categoryNames[$category['resource_uri']] = (string) ($category['name'] ?? '');
            }
        }

        $storeCategories = [];

        foreach ($data['store_categories'] as $storeCategory) {
            if (! is_array($storeCategory)
                || ! is_string($storeCategory['store_uri'] ?? null)
                || ! is_string($storeCategory['category_uri'] ?? null)) {
                continue;
            }

            $categoryName = $categoryNames[$storeCategory['category_uri']] ?? '';

            if ($categoryName !== '') {
                $storeCategories[$storeCategory['store_uri']][] = $categoryName;
            }
        }

        $mallSearchTerm = $intent['mall'] ?? $this->extractMallSearchTerm($message, $intent);
        $storeSearchTerm = $this->extractSearchTerm($message, $intent);
        $isCafeSearch = preg_match('/\b(?:cafe|café|kafe|coffee|kopi)\b/iu', $message) === 1;
        $isTopRated = $intent['type'] === 'top_rated';
        $results = [];

        foreach ($data['malls'] as $mall) {
            if (! is_array($mall) || ! is_string($mall['resource_uri'] ?? null)) {
                continue;
            }

            $city = $cities[$mall['city_uri'] ?? ''] ?? ['name' => '', 'province' => ''];
            $mallName = (string) ($mall['name'] ?? '');
            $mallRecord = [
                'mallName' => $mallName,
                'city' => $city['name'],
                'province' => $city['province'],
                'address' => (string) ($mall['address'] ?? ''),
                'hours' => (string) ($mall['opening_hours'] ?? ''),
                'website' => (string) ($mall['website'] ?? ''),
                'floors' => $mall['floors'] ?? null,
                'area' => (string) ($mall['area'] ?? ''),
                'mallCategory' => (string) ($mall['category'] ?? ''),
                'mallDescription' => (string) ($mall['description'] ?? ''),
            ];

            if (isset($intent['city']) && mb_strtolower($city['name']) !== mb_strtolower($intent['city'])) {
                continue;
            }

            if (isset($intent['province']) && mb_strtolower($city['province']) !== mb_strtolower($intent['province'])) {
                continue;
            }

            if ($mallSearchTerm !== '' && ! $this->containsText($mallName, $mallSearchTerm)) {
                continue;
            }

            $mallStores = [];

            foreach ($data['stores'] as $store) {
                if (! is_array($store) || ($store['mall_uri'] ?? null) !== $mall['resource_uri']) {
                    continue;
                }

                $storeName = (string) ($store['name'] ?? '');
                $categoryParts = array_values(array_unique(array_filter(array_merge(
                    [(string) ($store['category_text'] ?? '')],
                    $storeCategories[$store['resource_uri'] ?? ''] ?? [],
                ))));
                $category = implode(', ', $categoryParts);

                if (isset($intent['category'])) {
                    if ($intent['category'] === 'F&B') {
                        $matchesFoodCategory = $isCafeSearch
                            ? preg_match('/\bcafe\b|\bcafé\b|\bkafe\b|\bcoffee\b|\bkopi\b/iu', $category.' '.$storeName)
                            : preg_match('/\bf\s*&\s*b\b|food|beverage|restaurant|cafe|coffee|kuliner/iu', $category);

                        if (! $matchesFoodCategory) {
                            continue;
                        }
                    } elseif (! $this->containsText($category, $intent['category'])) {
                        continue;
                    }
                }

                if (in_array($intent['type'], ['find_store', 'top_rated'], true)
                    && $storeSearchTerm !== ''
                    && ! $this->containsText($storeName, $storeSearchTerm)
                    && ! $this->containsText($category, $storeSearchTerm)
                    && ! $this->containsText((string) ($store['description'] ?? ''), $storeSearchTerm)) {
                    continue;
                }

                $rating = $store['rating'] ?? null;

                if ($isTopRated && ! is_numeric($rating)) {
                    continue;
                }

                $mallStores[] = $mallRecord + [
                    'storeName' => $storeName,
                    'category' => $category,
                    'floor' => (string) ($store['location'] ?? ''),
                    'phone' => (string) ($store['phone'] ?? ''),
                    'rating' => is_numeric($rating) ? (float) $rating : null,
                    'status' => (string) ($store['status'] ?? ''),
                    'storeHours' => (string) ($store['opening_hours'] ?? ''),
                    'storeDescription' => (string) ($store['description'] ?? ''),
                ];
            }

            if ($intent['type'] === 'list_by_region') {
                $results[] = $mallRecord;

                continue;
            }

            if ($intent['type'] === 'mall_info') {
                $results = array_merge($results, $mallStores === [] ? [$mallRecord] : $mallStores);

                continue;
            }

            if (in_array($intent['type'], ['find_store', 'top_rated'], true)) {
                $results = array_merge($results, $mallStores);
            }
        }

        if ($isTopRated) {
            usort($results, fn (array $first, array $second): int => $second['rating'] <=> $first['rating']);
        }

        return $results;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadTtlDataset(): array
    {
        if ($this->ttlDataset !== null) {
            return $this->ttlDataset;
        }

        $path = storage_path('app/rdf_import.json');

        if (! File::exists($path)) {
            throw new RuntimeException('The generated TTL JSON file is missing: '.$path);
        }

        $dataset = File::json($path);
        $requiredSections = ['provinces', 'cities', 'malls', 'stores', 'categories', 'store_categories'];

        if (! is_array($dataset)) {
            throw new RuntimeException('The generated TTL JSON file is invalid.');
        }

        foreach ($requiredSections as $section) {
            if (! is_array($dataset[$section] ?? null)) {
                throw new RuntimeException("The generated TTL JSON is missing the {$section} section.");
            }
        }

        return $this->ttlDataset = $dataset;
    }

    private function containsText(string $value, string $search): bool
    {
        $normalizedValue = preg_replace('/\s+/u', ' ', mb_strtolower($value)) ?? mb_strtolower($value);
        $normalizedSearch = preg_replace('/\s+/u', ' ', mb_strtolower($search)) ?? mb_strtolower($search);

        return str_contains(trim($normalizedValue), trim($normalizedSearch));
    }

    /**
     * Remove common intent, location and category words from the message.
     *
     * @param  array<string, string>  $intent
     */
    protected function extractSearchTerm(string $message, array $intent): string
    {
        $text = mb_strtolower($message);

        $remove = [
            'a', 'an', 'cari', 'dimana', 'di mana', 'di', 'ada', 'toko',
            'mall', 'mal', 'rekomendasi', 'find', 'search', 'where',
            'store', 'recommend', 'terbaik', 'top', 'rating', 'populer',
            'best', 'highest rated', 'info', 'tentang', 'profil', 'about',
            'profile', 'daftar', 'kota', 'provinsi', 'city', 'province',
            'list', 'tolong', 'dong', 'please', 'in', 'at', 'the', 'yang',
            'apa saja', 'apa aja', 'yang ada', 'sekitar', 'sekitaran',
            'near', 'nearby', 'around', 'terdekat', 'paling dekat',
            'dengan saya', 'from me', 'my location', 'restoran', 'restaurant',
            'makan', 'food', 'dining', 'focal point', 'cafe', 'café', 'coffee',
            'kafe', 'kopi', 'food and beverage', 'food & beverage',
            'baju', 'fashion', 'sepatu', 'elektronik', 'electronics',
            'hp', 'bioskop', 'nonton', 'supermarket', 'clothes', 'shoes',
        ];

        foreach (['province', 'city', 'category', 'mall'] as $key) {
            if (isset($intent[$key])) {
                $remove[] = mb_strtolower($intent[$key]);
            }
        }

        foreach ($remove as $word) {
            $text = preg_replace(
                '/\b'.preg_quote($word, '/').'\b/iu',
                ' ',
                $text
            ) ?? $text;
        }

        $text = preg_replace('/[^\p{L}\p{N}\s&-]/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * Find a mall name in the location part of a question, including names not listed as aliases.
     *
     * @param  array<string, string>  $intent
     */
    protected function extractMallSearchTerm(string $message, array $intent): string
    {
        if (isset($intent['mall'])) {
            return mb_strtolower($intent['mall']);
        }

        if (! preg_match(
            '/\b(?:around|near|inside|within|in|at|di|dalam|sekitar(?:an)?|about|of|tentang|mengenai)\s+(.+)$/iu',
            $message,
            $matches
        )) {
            return '';
        }

        return $this->extractSearchTerm($matches[1], $intent);
    }

    /**
     * Build a deterministic response from the matching TTL records.
     *
     * @param  array<string, string>  $intent
     * @param  array<int, array<string, mixed>>  $results
     */
    protected function formatReply(
        array $intent,
        array $results,
        string $locale = 'id'
    ): string {
        $english = $locale === 'en';

        if ($results === []) {
            if ($intent['type'] === 'open_now') {
                return $english
                    ? 'Opening hours are not available in the current TTL dataset, so I cannot confirm which malls are open now.'
                    : 'Data TTL saat ini belum mencatat jam operasional, jadi saya belum bisa memastikan mall yang sedang buka.';
            }

            if ($intent['type'] === 'top_rated') {
                return $english
                    ? 'Ratings are not available in the current TTL dataset, so I cannot rank stores by rating.'
                    : 'Data rating belum tersedia di dataset TTL saat ini, jadi toko belum bisa diurutkan berdasarkan rating.';
            }

            if ($intent['type'] === 'budget') {
                return $english
                    ? 'Price information is not available in the current TTL dataset, so I cannot estimate a shopping budget.'
                    : 'Data harga belum tersedia di dataset TTL saat ini, jadi saya belum bisa menghitung estimasi anggaran.';
            }

            if ($intent['type'] === 'unknown') {
                return $english
                    ? 'I can answer mall lists by city or province, mall details, and store searches by mall or category. The TTL data does not include coordinates for nearest-place searches. Try one of the suggested questions.'
                    : 'Saya bisa menjawab daftar mall per kota/provinsi, detail mall, serta pencarian toko berdasarkan mall atau kategori. Data TTL belum memiliki koordinat untuk menghitung lokasi terdekat. Coba salah satu contoh pertanyaan.';
            }

            if (in_array($intent['type'], ['nearest_mall', 'nearest_store'], true)) {
                return $english
                    ? 'The TTL JSON dataset does not contain mall or store coordinates, so I cannot calculate which place is nearest.'
                    : 'File JSON dari TTL belum memiliki koordinat mall atau toko, jadi saya belum bisa menghitung lokasi yang paling dekat.';
            }

            return $english
                ? 'I could not find matching information in the TTL dataset. Try a mall name, category, city, or province.'
                : 'Saya tidak menemukan informasi yang cocok di data TTL. Coba nama mall, kategori, kota, atau provinsi.';
        }

        return match ($intent['type']) {
            'find_store' => ($english ? 'I found ' : 'Saya menemukan ')
                .count($results)
                .($english
                    ? (count($results) === 1 ? " store:\n\n" : " stores:\n\n")
                    : " toko:\n\n")
                .implode("\n", array_map(
                    fn (array $row) => '- '.($row['storeName'] ?? '-')
                        .($english ? ' at ' : ' di ')
                        .($row['mallName'] ?? '-')
                        .' ('.($row['city'] ?? '-').')'
                        .(! empty($row['category']) ? ' — '.$row['category'] : '')
                        .(! empty($row['floor']) ? ($english ? ' — floor ' : ' — lantai ').$row['floor'] : '')
                        .(! empty($row['storeHours']) ? ($english ? ' — hours ' : ' — jam ').$row['storeHours'] : '')
                        .(! empty($row['phone']) ? ($english ? ' — phone ' : ' — telepon ').$row['phone'] : '')
                        .(isset($row['rating']) ? ' — '.($english ? 'rating ' : 'rating ').$row['rating'] : ''),
                    $results
                )),

            'top_rated' => ($english ? 'Stores with recorded ratings, highest first:' : 'Toko dengan rating yang tercatat, diurutkan tertinggi:')
                ."\n\n"
                .implode("\n", array_map(
                    fn (array $row) => '- '.($row['storeName'] ?? '-')
                        .($english ? ' at ' : ' di ')
                        .($row['mallName'] ?? '-')
                        .' — '.($row['rating'] ?? '-'),
                    $results
                )),

            'list_by_region' => ($english ? "Mall directory:\n\n" : "Daftar mall:\n\n")
                .implode("\n", array_map(
                    fn (array $row) => '- '.($row['mallName'] ?? '-')
                        .' ('.($row['city'] ?? '-')
                        .', '.($row['province'] ?? '-').')'
                        .(! empty($row['address']) ? ' — '.$row['address'] : '')
                        .(! empty($row['hours']) ? ($english ? ' — hours ' : ' — jam buka ').$row['hours'] : ''),
                    $results
                )),

            'mall_info' => $this->formatMallDetails($results, $english),

            default => $english
                ? 'I can answer questions about malls by city/province, mall details, and store searches by mall or category.'
                : 'Saya bisa menjawab daftar mall per kota/provinsi, detail mall, serta pencarian toko berdasarkan mall atau kategori.',
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $results
     */
    private function formatMallDetails(array $results, bool $english): string
    {
        $malls = [];

        foreach ($results as $row) {
            $name = $row['mallName'] ?? '-';
            $malls[$name] ??= [
                'mallName' => $name,
                'city' => $row['city'] ?? '-',
                'province' => $row['province'] ?? '-',
                'address' => $row['address'] ?? '',
                'hours' => $row['hours'] ?? '',
                'website' => $row['website'] ?? '',
                'floors' => $row['floors'] ?? null,
                'area' => $row['area'] ?? '',
                'mallCategory' => $row['mallCategory'] ?? '',
                'categories' => [],
                'stores' => [],
            ];

            if (! empty($row['category'])) {
                foreach (explode(',', $row['category']) as $category) {
                    $category = trim($category);

                    if ($category !== '') {
                        $malls[$name]['categories'][$category] = $category;
                    }
                }
            }

            if (! empty($row['storeName'])) {
                $malls[$name]['stores'][$row['storeName']] = $row['storeName'];
            }
        }

        return implode("\n\n", array_map(function (array $mall) use ($english): string {
            $lines = [$mall['mallName'].' — '.$mall['city'].', '.$mall['province']];

            foreach ([
                'address' => $english ? 'Address' : 'Alamat',
                'hours' => $english ? 'Opening hours' : 'Jam buka',
                'website' => $english ? 'Website' : 'Situs',
                'area' => $english ? 'Area' : 'Luas',
                'mallCategory' => $english ? 'Mall type' : 'Jenis mall',
            ] as $key => $label) {
                if ($mall[$key] !== '') {
                    $lines[] = $label.': '.$mall[$key];
                }
            }

            if ($mall['floors'] !== null && $mall['floors'] !== '') {
                $lines[] = ($english ? 'Floors' : 'Jumlah lantai').': '.$mall['floors'];
            }

            $lines[] = ($english ? 'Stores in dataset' : 'Toko dalam dataset').': '.count($mall['stores']);
            $lines[] = ($english ? 'Categories' : 'Kategori').': '
                .($mall['categories'] === [] ? ($english ? 'not recorded' : 'belum tercatat') : implode(', ', $mall['categories']));

            if ($mall['stores'] !== []) {
                $lines[] = ($english ? 'Tenant names' : 'Nama tenant').': '.implode(', ', array_keys($mall['stores']));
            }

            return implode("\n", $lines);
        }, array_values($malls)));
    }
}
