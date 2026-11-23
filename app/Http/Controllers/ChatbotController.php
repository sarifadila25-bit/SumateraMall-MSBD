<?php

namespace App\Http\Controllers;

use App\Models\ChatLog;
use App\Services\GeminiService;
use App\Services\SparqlService;
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
        private readonly SparqlService $sparql,
    ) {}

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
                : 'AI belum dikonfigurasi; jawaban ini menggunakan aturan pencarian.';
        }

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
            'makan' => 'F&B',
            'restoran' => 'F&B',
            'cafe' => 'F&B',
            'coffee' => 'F&B',
            'baju' => 'Fashion',
            'fashion' => 'Fashion',
            'sepatu' => 'Fashion',
            'elektronik' => 'Electronics',
            'hp' => 'Electronics',
            'bioskop' => 'Entertainment',
            'nonton' => 'Entertainment',
            'supermarket' => 'Supermarket',
            'restaurant' => 'F&B',
            'food' => 'F&B',
            'dining' => 'F&B',
            'clothes' => 'Fashion',
            'shoes' => 'Fashion',
            'electronics' => 'Electronics',
            'phone' => 'Electronics',
            'cinema' => 'Entertainment',
            'movie' => 'Entertainment',
            'kafe' => 'F&B',
            'kopi' => 'F&B',    
        ];

        foreach ($categories as $keyword => $category) {
            if (str_contains($text, $keyword)) {
                $intent['category'] = $category;
                break;
            }
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
     * Retrieve records from Apache Jena Fuseki using SPARQL.
     *
     * @param array<string, string> $intent
     * @return array<int, array<string, string>>
     */
   
protected function getResults(array $intent, string $message): array
{
    if (in_array($intent['type'], ['budget', 'unknown', 'top_rated'], true)) {
        return [];
    }

    $filters = [];

    if (isset($intent['city'])) {
        $city = $this->sparql->literal($intent['city']);
$filters[] = 'FILTER(LCASE(STR(?city)) = LCASE('.$city.'))'; 
    }

    if (isset($intent['province'])) {
        $province = $this->sparql->literal($intent['province']);
        $filters[] = 'FILTER(CONTAINS(LCASE(STR(?province)), LCASE('.$province.')))'; 
    }

    if (isset($intent['mall'])) {
        $mall = $this->sparql->literal($intent['mall']);
        $filters[] = 'FILTER(CONTAINS(LCASE(STR(?mallName)), LCASE('.$mall.')))'; 
    }

    $filterQuery = implode("\n", $filters);

    $mallPattern = '
        ?mall a mall:Mall ;
              mall:namaMall ?mallName ;
              mall:beradaDiKota ?cityRes .

        ?cityRes mall:namaKota ?city .

        OPTIONAL {
            ?cityRes mall:kotaTerletakDiProvinsi ?provinceRes .
            ?provinceRes mall:namaProvinsi ?province .
        }
    ';

    // Mall yang sedang buka.
    if ($intent['type'] === 'open_now') {
        $sparql = <<<SPARQL
PREFIX mall: <http://www.example.org/mall#>

SELECT DISTINCT ?mallName ?city ?province ?hours
WHERE {
    {$mallPattern}
    ?mall mall:jamBuka ?hours .
    {$filterQuery}
}
ORDER BY ?mallName
LIMIT 100
SPARQL;

        $malls = $this->sparql->query($sparql, 0);

        $currentMinutes = ((int) now('Asia/Jakarta')->format('H')) * 60
            + (int) now('Asia/Jakarta')->format('i');

        return array_values(array_filter(
            $malls,
            fn (array $mall): bool => $this->isMallOpen(
                (string) ($mall['hours'] ?? ''),
                $currentMinutes
            )
        ));
    }

    // Daftar mall berdasarkan kota atau provinsi.
    if ($intent['type'] === 'list_by_region') {
        $sparql = <<<SPARQL
PREFIX mall: <http://www.example.org/mall#>

SELECT DISTINCT ?mallName ?city ?province
WHERE {
    {$mallPattern}
    {$filterQuery}
}
ORDER BY ?mallName
LIMIT 30
SPARQL;

        return $this->sparql->query($sparql, 0);
    }

    // Informasi mall.
    if ($intent['type'] === 'mall_info') {
        if (!isset($intent['mall']) && !isset($intent['city'])) {
            return [];
        }

        $sparql = <<<SPARQL
PREFIX mall: <http://www.example.org/mall#>

SELECT DISTINCT ?mallName ?city ?province ?address
WHERE {
    {$mallPattern}

    OPTIONAL {
        ?mall mall:berlokasiDi ?address .
    }

    {$filterQuery}
}
ORDER BY ?mallName
LIMIT 1
SPARQL;

        return $this->sparql->query($sparql, 0);
    }

    // Pencarian toko berdasarkan nama, mall, kota, dan kategori.
    if (isset($intent['category'])) {
        $category = $intent['category'];

        if ($category === 'F&B') {
            $filters[] = 'FILTER(REGEX(STR(?category), "F\\\\s*&\\\\s*B|food|restaurant", "i"))';
        } else {
            $categoryLiteral = $this->sparql->literal($category);
            $filters[] = 'FILTER(CONTAINS(LCASE(STR(?category)), LCASE('.$categoryLiteral.')))';
        }
    }

    $searchTerm = $this->extractSearchTerm($message, $intent);

    if ($searchTerm !== '') {
        $term = $this->sparql->literal($searchTerm);

        $filters[] = 'FILTER(
            CONTAINS(LCASE(STR(?storeName)), LCASE('.$term.')) ||
            CONTAINS(LCASE(STR(?category)), LCASE('.$term.'))
        )';
    }

    $filterQuery = implode("\n", $filters);

    $sparql = <<<SPARQL
PREFIX mall: <http://www.example.org/mall#>

SELECT DISTINCT ?storeName ?mallName ?city ?province ?category
WHERE {
    ?store a mall:Toko ;
           mall:namaToko ?storeName ;
           mall:kategoriToko ?category ;
           mall:beradaDiMall ?mall .

    {$mallPattern}

    {$filterQuery}
}
ORDER BY ?storeName
LIMIT 10
SPARQL;

    return $this->sparql->query($sparql, 0);
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
            'a', 'an', 'cari', 'dimana', 'di mana', 'di', 'ada', 'toko',
            'mall', 'mal', 'rekomendasi', 'find', 'search', 'where',
            'store', 'recommend', 'terbaik', 'top', 'rating', 'populer',
            'best', 'highest rated', 'info', 'tentang', 'profil', 'about',
            'profile', 'daftar', 'kota', 'provinsi', 'city', 'province',
            'list', 'tolong', 'dong', 'please', 'in', 'at', 'the', 'yang',
            'apa saja', 'apa aja', 'yang ada', 'restoran', 'restaurant',
            'makan', 'food', 'dining', 'focal point', 'cafe', 'coffee',
            'kafe', 'kopi', 'restoran', 'restaurant', 'makan', 'food', 'dining', 'focal point',
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
     * Build a rule-based response when Gemini is not configured.
     *
     * @param array<string, string> $intent
     * @param array<int, array<string, string>> $results
     */

    protected function isMallOpen(string $hours, int $currentMinutes): bool
{
    $hours = trim($hours);

    $matched = preg_match(
        '/(\d{1,2})\s*[.,:]\s*(\d{2})\s*(?:[-–—]|\s+)\s*(\d{1,2})\s*[.,:]\s*(\d{2})/',
        $hours,
        $matches
    );

    if (!$matched) {
        return false;
    }

    $open = ((int) $matches[1] * 60) + (int) $matches[2];
    $close = ((int) $matches[3] * 60) + (int) $matches[4];

    if ($open >= 1440 || $close >= 1440) {
        return false;
    }

    if ($close <= $open) {
        $close += 1440;
    }

    if ($currentMinutes < $open && $close > 1440) {
        $currentMinutes += 1440;
    }

    return $currentMinutes >= $open && $currentMinutes < $close;
}

    protected function formatReply(
        array $intent,
        array $results,
        string $locale = 'id'
    ): string {
        $english = $locale === 'en';

        if ($intent['type'] === 'open_now') {
    if ($results === []) {
        return $english
            ? 'I could not find any malls confirmed to be open right now in the available data.'
            : 'Aku tidak menemukan mall yang terkonfirmasi sedang buka berdasarkan data jam operasional yang tersedia.';
    }

    return ($english
        ? "Malls currently open:\n\n"
        : "Mall yang sedang buka:\n\n")
        .implode("\n", array_map(
            fn (array $row) => '- '.($row['mallName'] ?? '-')
                .' ('.($row['city'] ?? '-').')'
                .' — '.($row['hours'] ?? '-'),
            array_slice($results, 0, 20)
        ));
}

        if ($intent['type'] === 'open_now') {
            return $english
                ? 'I cannot confirm which stores are currently open because structured opening-hour data is not yet available.'
                : 'Aku belum bisa memastikan toko yang sedang buka karena data jam operasional terstruktur belum tersedia.';
        }

        if ($results === []) {
            if ($intent['type'] === 'top_rated') {
                return $english
                    ? 'Rating information is not available yet, so I cannot determine the highest-rated stores.'
                    : 'Maaf, data rating toko belum tersedia. Jadi, aku belum bisa menentukan toko dengan rating tertinggi.';
            }

            return $english
                ? 'Sorry, I could not find matching data. Try different keywords.'
                : 'Maaf, aku tidak menemukan data yang sesuai. Coba kata kunci lain.';
        }

        return match ($intent['type']) {
            'find_store' => ($english ? 'I found ' : 'Aku menemukan ')
                .count($results)
                .($english
                    ? (count($results) === 1 ? " store:\n\n" : " stores:\n\n")
                    : " toko:\n\n")
                .implode("\n", array_map(
                    fn (array $row) => '- '.($row['storeName'] ?? '-')
                        .($english ? ' at ' : ' di ')
                        .($row['mallName'] ?? '-')
                        .' ('.($row['city'] ?? '-').')',
                    array_slice($results, 0, 5)
                )),

            'top_rated' => ($english ? "Top-rated stores:\n\n" : "Toko dengan rating tertinggi:\n\n")
                .implode("\n", array_map(
                    fn (array $row) => '- '.($row['storeName'] ?? '-')
                        .' ('.($row['mallName'] ?? '-')
                        .', '.($row['city'] ?? '-').')'
                        .(!empty($row['rating']) ? ' — '.$row['rating'] : ''),
                    array_slice($results, 0, 5)
                )),

            'list_by_region' => ($english ? "Mall directory:\n\n" : "Daftar mall:\n\n")
                .implode("\n", array_map(
                    fn (array $row) => '- '.($row['mallName'] ?? '-')
                        .' ('.($row['city'] ?? '-')
                        .', '.($row['province'] ?? '-').')',
                    array_slice($results, 0, 20)
                )),

            'mall_info' => $english
                ? ($results[0]['mallName'] ?? '-').' is in '
                    .($results[0]['city'] ?? '-').', '
                    .($results[0]['province'] ?? '-')
                    .(!empty($results[0]['address']) ? ' — '.$results[0]['address'] : '')
                : ($results[0]['mallName'] ?? '-').' berada di '
                    .($results[0]['city'] ?? '-').', '
                    .($results[0]['province'] ?? '-')
                    .(!empty($results[0]['address']) ? ' — '.$results[0]['address'] : ''),

            default => $english
                ? 'I found '.count($results).' results. Try asking a more specific question.'
                : 'Aku menemukan '.count($results).' hasil. Coba pertanyaannya lebih spesifik.',
        };
    }
}
