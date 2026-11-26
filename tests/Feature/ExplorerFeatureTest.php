
<?php

use App\Models\ChatLog;
use App\Models\City;
use App\Models\Mall;
use App\Models\Province;
use App\Models\Store;
use App\Models\User;
use App\Services\GoogleMapsService;
use App\Services\OsmService;
use App\Services\SparqlService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function chatbotTestDataset(): array
{
    return [
        'provinces' => [[
            'name' => 'Sumatera Utara',
            'resource_uri' => 'province:sumatera-utara',
        ]],
        'cities' => [[
            'name' => 'Medan',
            'province_uri' => 'province:sumatera-utara',
            'resource_uri' => 'city:medan',
        ]],
        'malls' => [[
            'name' => 'Sun Plaza',
            'city_uri' => 'city:medan',
            'address' => 'Jl. Zainul Arifin',
            'opening_hours' => '10.00 - 22.00',
            'floors' => 4,
            'category' => 'Pusat Perbelanjaan',
            'area' => '107.373',
            'website' => 'https://example.test/sun-plaza',
            'description' => null,
            'resource_uri' => 'mall:sun-plaza',
        ]],
        'stores' => [
            [
                'name' => 'DJournal Coffee',
                'mall_uri' => 'mall:sun-plaza',
                'category_text' => 'F&B, Beverage, Cafe, Coffee',
                'location' => 'L3',
                'description' => null,
                'status' => null,
                'opening_hours' => null,
                'phone' => null,
                'rating' => null,
                'resource_uri' => 'store:djournal',
            ],
            [
                'name' => 'Food House',
                'mall_uri' => 'mall:sun-plaza',
                'category_text' => 'F&B, Food, Indonesian Food',
                'location' => 'L4',
                'description' => null,
                'status' => null,
                'opening_hours' => null,
                'phone' => null,
                'rating' => null,
                'resource_uri' => 'store:food-house',
            ],
        ],
        'categories' => [],
        'store_categories' => [],
    ];
}

function useChatbotTestDataset(?array $dataset = null, bool $loadDataset = true): void
{
    File::shouldReceive('json')
        ->once()
        ->with(resource_path('data/chatbot-questions.json'))
        ->andReturn(json_decode(file_get_contents(resource_path('data/chatbot-questions.json')), true));

    if (! $loadDataset) {
        return;
    }

    File::shouldReceive('exists')
        ->once()
        ->with(storage_path('app/rdf_import.json'))
        ->andReturnTrue();
    File::shouldReceive('json')
        ->once()
        ->with(storage_path('app/rdf_import.json'))
        ->andReturn($dataset ?? chatbotTestDataset());
}

test('the homepage shows open malls and provides a store search scoped to a mall', function () {
    $this->travelTo(Carbon::parse('2026-11-14 15:00:00', 'Asia/Jakarta'));

    $province = Province::create([
        'name' => 'Sumatera Utara',
        'resource_uri' => 'http://sumateramall.org/resource/Province/sumatera_utara',
    ]);

    $city = City::create([
        'province_id' => $province->id,
        'name' => 'Medan',
        'resource_uri' => 'http://sumateramall.org/resource/City/medan',
    ]);

    Mall::create([
        'city_id' => $city->id,
        'name' => 'Open Plaza',
        'opening_hours' => '10.00 - 22.00',
        'source_namespace' => 'sumateramall',
        'resource_uri' => 'http://sumateramall.org/resource/Mall/open_plaza',
    ]);

    Mall::create([
        'city_id' => $city->id,
        'name' => 'Closed Plaza',
        'opening_hours' => '09.00 - 14.00',
        'source_namespace' => 'sumateramall',
        'resource_uri' => 'http://sumateramall.org/resource/Mall/closed_plaza',
    ]);

    Http::fake([
        '*' => Http::response([
            'results' => ['bindings' => [
                [
                    'mall' => ['value' => 'http://sumateramall.org/resource/Mall/open_plaza'],
                    'name' => ['value' => 'Open Plaza'],
                    'city' => ['value' => 'Medan'],
                    'province' => ['value' => 'Sumatera Utara'],
                    'hours' => ['value' => '10.00 - 22.00'],
                ],
                [
                    'mall' => ['value' => 'http://sumateramall.org/resource/Mall/closed_plaza'],
                    'name' => ['value' => 'Closed Plaza'],
                    'city' => ['value' => 'Medan'],
                    'province' => ['value' => 'Sumatera Utara'],
                    'hours' => ['value' => '09.00 - 14.00'],
                ],
            ]],
        ], 200),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Open Plaza')
        ->assertDontSee('<h3>Closed Plaza</h3>', false)
        ->assertSee('name="search"', false)
        ->assertSee('name="mall"', false)
        ->assertSee('mall-city-filter', false)
        ->assertSee('SumateraMall Assistant')
        ->assertSee('data-theme-toggle', false);
});

test('the shared navigation only includes home, team, and typo-tolerant store search', function () {
    Http::fake([
        '*' => Http::response(['results' => ['bindings' => []]], 200),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('href="'.route('team').'"', false)
        ->assertSee('id="site-search"', false)
        ->assertSee('name="search"', false)
        ->assertDontSee('href="'.route('malls.index').'"', false)
        ->assertDontSee('href="'.route('planner.index').'"', false);
});

test('the landing store search applies the selected mall filter', function () {
    $province = Province::create([
        'name' => 'Sumatera Utara',
        'resource_uri' => 'http://sumateramall.org/resource/Province/sumatera_utara',
    ]);

    $city = City::create([
        'province_id' => $province->id,
        'name' => 'Medan',
        'resource_uri' => 'http://sumateramall.org/resource/City/medan',
    ]);

    $mall = Mall::create([
        'city_id' => $city->id,
        'name' => 'Sun Plaza',
        'source_namespace' => 'sumateramall',
        'resource_uri' => 'http://sumateramall.org/resource/Mall/sun_plaza',
    ]);

    Store::create([
        'mall_id' => $mall->id,
        'name' => 'Coffee House',
        'category_text' => 'Cafe',
        'source_namespace' => 'sumateramall',
        'resource_uri' => 'http://sumateramall.org/resource/Store/coffee_house',
    ]);

    Http::fake([
        '*' => Http::response([
            'results' => ['bindings' => [[
                'storeName' => ['value' => 'Coffee House'],
                'mallName' => ['value' => 'Sun Plaza'],
                'city' => ['value' => 'Medan'],
                'category' => ['value' => 'Cafe'],
            ]]],
        ], 200),
    ]);

    $mall = 'http://sumateramall.org/resource/Mall/sun_plaza';

    $this->get('/search/filter?search=Coffee&mall='.urlencode($mall))
        ->assertOk()
        ->assertSee('Coffee House')
        ->assertSee('Sun Plaza');
});

test('store search returns relevant results when the query contains a typo', function () {
    $province = Province::create([
        'name' => 'Sumatera Utara',
        'resource_uri' => 'http://sumateramall.org/resource/Province/sumatera_utara',
    ]);

    $city = City::create([
        'province_id' => $province->id,
        'name' => 'Medan',
        'resource_uri' => 'http://sumateramall.org/resource/City/medan',
    ]);

    $mall = Mall::create([
        'city_id' => $city->id,
        'name' => 'Sun Plaza',
        'source_namespace' => 'sumateramall',
        'resource_uri' => 'http://sumateramall.org/resource/Mall/sun_plaza',
    ]);

    Store::create([
        'mall_id' => $mall->id,
        'name' => 'Coffee House',
        'category_text' => 'Cafe',
        'rating' => 4.8,
        'status' => 'OPERATIONAL',
        'source_namespace' => 'sumateramall',
        'resource_uri' => 'http://sumateramall.org/resource/Store/coffee_house',
    ]);

    Store::create([
        'mall_id' => $mall->id,
        'name' => 'Pizza Corner',
        'category_text' => 'Restaurant',
        'rating' => 4.9,
        'status' => 'OPERATIONAL',
        'source_namespace' => 'sumateramall',
        'resource_uri' => 'http://sumateramall.org/resource/Store/pizza_corner',
    ]);

    Http::fake([
        '*' => Http::response([
            'results' => ['bindings' => [
                [
                    'store' => ['value' => 'store/coffee'],
                    'storeName' => ['value' => 'Coffee House'],
                    'mallName' => ['value' => 'Sun Plaza'],
                    'city' => ['value' => 'Medan'],
                    'category' => ['value' => 'Cafe'],
                    'rating' => ['value' => '4.8'],
                    'status' => ['value' => 'OPERATIONAL'],
                ],
                [
                    'store' => ['value' => 'store/pizza'],
                    'storeName' => ['value' => 'Pizza Corner'],
                    'mallName' => ['value' => 'Sun Plaza'],
                    'city' => ['value' => 'Medan'],
                    'category' => ['value' => 'Restaurant'],
                    'rating' => ['value' => '4.9'],
                    'status' => ['value' => 'OPERATIONAL'],
                ],
            ]],
        ], 200),
    ]);

    $this->get('/search/filter?search=cofee')
        ->assertOk()
        ->assertSee('Coffee House')
        ->assertDontSee('Pizza Corner')
        ->assertSee('cofee');
});

test('the mall map lists address-based locations without requiring stored coordinates', function () {
    Http::fake([
        '*' => Http::response([
            'results' => ['bindings' => [[
                'id' => ['value' => 'sun_plaza'],
                'name' => ['value' => 'Sun Plaza'],
                'city' => ['value' => 'Medan'],
                'province' => ['value' => 'Sumatera Utara'],
                'address' => ['value' => 'Jl. Zainul Arifin'],
            ]]],
        ], 200),
    ]);

    $this->get('/map')
        ->assertOk()
        ->assertSee('Sun Plaza')
        ->assertSee('data-locate-mall', false)
        ->assertSee('google.com/maps/search', false);
});

test('mall details reject non-http website schemes from RDF', function () {
    $province = Province::create([
        'name' => 'Sumatera Utara',
        'resource_uri' => 'http://sumateramall.org/resource/Province/sumatera_utara',
    ]);

    $city = City::create([
        'province_id' => $province->id,
        'name' => 'Medan',
        'resource_uri' => 'http://sumateramall.org/resource/City/medan',
    ]);

    Mall::create([
        'city_id' => $city->id,
        'name' => 'Sun Plaza',
        'address' => 'Jl. Zainul Arifin',
        'website' => 'javascript:alert(1)',
        'source_namespace' => 'sumateramall',
        'resource_uri' => 'http://sumateramall.org/resource/Mall/sun_plaza',
    ]);

    Store::create([
        'mall_id' => Mall::first()->id,
        'name' => 'Cafe Example',
        'category_text' => 'Food & Beverage',
        'source_namespace' => 'sumateramall',
        'resource_uri' => 'http://sumateramall.org/resource/Store/cafe_example',
    ]);

    Http::fake([
        '*' => Http::response([
            'results' => ['bindings' => [[
                'name' => ['value' => 'Sun Plaza'],
                'city' => ['value' => 'Medan'],
                'province' => ['value' => 'Sumatera Utara'],
                'address' => ['value' => 'Jl. Zainul Arifin'],
                'website' => ['value' => 'javascript:alert(1)'],
                'storeName' => ['value' => 'Cafe Example'],
                'category' => ['value' => 'Food & Beverage'],
            ]]],
        ], 200),
    ]);

    $this->get('/malls/sun_plaza')
        ->assertOk()
        ->assertSee('mall-map', false)
        ->assertSee('Cafe Example')
        ->assertDontSee('javascript:');
});

test('invalid resource filters are rejected before reaching Fuseki', function () {
    Http::fake();

    $this->postJson('/search/filter', [
        'city' => 'http://sumateramall.org/resource/City/x> } UNION { ?s ?p ?o',
    ])->assertUnprocessable();

    Http::assertNothingSent();
});

test('the chatbot answers cafe questions from the TTL JSON without calling an AI or Fuseki', function () {
    useChatbotTestDataset();

    $this->postJson('/chatbot', ['message' => 'cafe sekitaran Sun Plaza'])
        ->assertOk()
        ->assertJsonPath('results_count', 1)
        ->assertJsonPath('intent.type', 'find_store')
        ->assertJsonPath('reply', "Saya menemukan 1 toko:\n\n- DJournal Coffee di Sun Plaza (Medan) — F&B, Beverage, Cafe, Coffee — lantai L3");

    expect(ChatLog::query()->count())->toBe(1);

    Http::assertNothingSent();
});

test('the chatbot answers English store searches from JSON with available details', function () {
    useChatbotTestDataset();

    $this->postJson('/chatbot', [
        'message' => 'find a cafe in Sun Plaza',
        'locale' => 'en',
    ])->assertOk()
        ->assertJsonPath('intent.type', 'find_store')
        ->assertJsonPath('reply', "I found 1 store:\n\n- DJournal Coffee at Sun Plaza (Medan) — F&B, Beverage, Cafe, Coffee — floor L3");
});

test('the chatbot does not use Gemini when credentials are configured', function () {
    config(['services.gemini.api_key' => 'test-gemini-key']);
    useChatbotTestDataset();

    $this->postJson('/chatbot', [
        'message' => 'find a cafe in Sun Plaza',
        'locale' => 'en',
    ])->assertOk()
        ->assertJsonPath('results_count', 1)
        ->assertJsonPath('intent.type', 'find_store');

    Http::assertNothingSent();
});

test('the chatbot returns mall details and city directories from the TTL JSON', function () {
    useChatbotTestDataset();

    $this->postJson('/chatbot', ['message' => 'info lengkap tentang Sun Plaza'])
        ->assertOk()
        ->assertSeeText('Jl. Zainul Arifin')
        ->assertSeeText('Jumlah lantai: 4')
        ->assertSeeText('DJournal Coffee');

    $this->postJson('/chatbot', ['message' => 'malls in Medan', 'locale' => 'en'])
        ->assertOk()
        ->assertJsonPath('results_count', 1)
        ->assertJsonPath('intent.city', 'Medan')
        ->assertSeeText('Sun Plaza');
});

test('nearest questions explain that the TTL JSON has no coordinates without requesting browser location', function () {
    useChatbotTestDataset(loadDataset: false);

    $this->postJson('/chatbot', ['message' => 'mall terdekat dari saya'])
        ->assertOk()
        ->assertJsonPath('intent.type', 'nearest_mall')
        ->assertJsonPath('reply', 'File JSON dari TTL belum memiliki koordinat mall atau toko, jadi saya belum bisa menghitung lokasi yang paling dekat.');

    Http::assertNothingSent();
});

test('invalid TTL JSON data returns a clear service error', function () {
    useChatbotTestDataset(['malls' => []]);

    $this->postJson('/chatbot', ['message' => 'daftar mall di Medan'])
        ->assertServiceUnavailable()
        ->assertJsonPath('message', 'Data mall tidak tersedia. Silakan coba lagi nanti.');
});

test('mall comparison accepts two known resource ids and returns comparison data', function () {
    Http::fake([
        '*' => Http::response([
            'results' => [
                'bindings' => [
                    [
                        'mall' => ['value' => 'http://sumateramall.org/resource/Mall/sun_plaza'],
                        'name' => ['value' => 'Sun Plaza'],
                        'city' => ['value' => 'Medan'],
                        'province' => ['value' => 'Sumatera Utara'],
                        'storeCount' => ['value' => '5'],
                        'categoryCount' => ['value' => '3'],
                    ],
                    [
                        'mall' => ['value' => 'http://sumateramall.org/resource/Mall/deli_park_mall'],
                        'name' => ['value' => 'Deli Park Mall'],
                        'city' => ['value' => 'Medan'],
                        'province' => ['value' => 'Sumatera Utara'],
                        'storeCount' => ['value' => '8'],
                        'categoryCount' => ['value' => '4'],
                    ],
                ],
            ],
        ], 200),
    ]);

    $this->get('/malls/compare?mall_ids[]=sun_plaza&mall_ids[]=deli_park_mall')
        ->assertOk()
        ->assertSee('Sun Plaza')
        ->assertSee('Deli Park Mall');

    Http::assertSent(function ($request): bool {
        $queryString = parse_url($request->url(), PHP_URL_QUERY) ?? '';
        parse_str($queryString, $parameters);

        return isset($parameters['query'])
            && preg_match('/\bUNION\b/i', $parameters['query']) === 1
            && str_contains(
                $parameters['query'],
                'BIND(<http://sumateramall.org/resource/Mall/sun_plaza> AS ?mall)',
            )
            && ! str_contains($parameters['query'], '<<http://');
    });
});

test('SPARQL literals safely quote user-provided text', function () {
    expect(app(SparqlService::class)->literal('mall "one" \\ two'))
        ->toBe('"mall \\"one\\" \\\\ two"');
});

test('OSRM duration tables optimize the order of mall visits', function () {
    Http::fake(function ($request) {
        if (str_contains($request->url(), '/table/')) {
            return Http::response([
                'code' => 'Ok',
                'durations' => [
                    [0, 100, 1],
                    [100, 0, 1],
                    [1, 1, 0],
                ],
            ]);
        }

        return Http::response([
            'code' => 'Ok',
            'routes' => [[
                'distance' => 2100,
                'duration' => 1800,
                'legs' => [
                    ['distance' => 1100, 'duration' => 900],
                    ['distance' => 1000, 'duration' => 900],
                ],
                'geometry' => ['coordinates' => [[98.6, 3.5], [98.7, 3.6], [98.8, 3.7]]],
            ]],
        ]);
    });

    $route = app(OsmService::class)->planRoute([
        ['id' => 'a', 'name' => 'Mall A', 'city' => 'Medan', 'lat' => 3.6, 'lng' => 98.7],
        ['id' => 'b', 'name' => 'Mall B', 'city' => 'Medan', 'lat' => 3.7, 'lng' => 98.8],
    ], 3.5, 98.6);

    expect(array_column($route['malls'], 'id'))->toBe(['b', 'a'])
        ->and($route['source'])->toBe('openstreetmap')
        ->and($route['totalDistance'])->toBe(2100)
        ->and(count($route['legs']))->toBe(2);
});

test('unusable OSRM data returns a clearly labeled local route estimate', function () {
    Http::fake(['*' => Http::response('not-json', 200)]);

    $route = app(OsmService::class)->planRoute([
        ['id' => 'a', 'name' => 'Mall A', 'city' => 'Medan', 'lat' => 3.6, 'lng' => 98.7],
        ['id' => 'b', 'name' => 'Mall B', 'city' => 'Medan', 'lat' => 3.7, 'lng' => 98.8],
    ], 3.5, 98.6);

    expect($route['source'])->toBe('estimated')
        ->and($route['notice'])->toContain('straight-line estimates')
        ->and($route['totalDistance'])->toBeGreaterThan(0);
});

test('Google Maps plans routes from addresses and reports the visit order', function () {
    config(['services.google_maps.server_key' => 'test-maps-key']);

    Http::fake([
        '*' => Http::response([
            'routes' => [[
                'distanceMeters' => 12000,
                'duration' => '1200s',
                'polyline' => ['encodedPolyline' => '_p~iF~ps|U_ulLnnqC'],
                'legs' => [[
                    'distanceMeters' => 12000,
                    'duration' => '1200s',
                    'startLocation' => ['latLng' => ['latitude' => 3.5, 'longitude' => 98.6]],
                    'endLocation' => ['latLng' => ['latitude' => 3.6, 'longitude' => 98.7]],
                ]],
            ]],
        ], 200),
    ]);

    $route = app(GoogleMapsService::class)->planRoute([
        ['id' => 'mall_a', 'name' => 'Mall A', 'city' => 'Medan', 'province' => 'North Sumatra', 'address' => 'One Street'],
        ['id' => 'mall_b', 'name' => 'Mall B', 'city' => 'Medan', 'province' => 'North Sumatra', 'address' => 'Two Street'],
    ], null, null);

    expect(array_column($route['malls'], 'id'))->toBe(['mall_a', 'mall_b'])
        ->and($route['legs'][0]['end_address'])->toBe('Mall B')
        ->and($route['source'])->toBe('google_maps')
        ->and(count($route['routeCoordinates']))->toBe(2);

    Http::assertSent(fn ($request): bool => $request->hasHeader('X-Goog-Api-Key', 'test-maps-key')
        && str_contains($request->body(), '"address":"One Street"')
        && str_contains($request->body(), '"address":"Two Street"'));
});

test('Google Maps uses the optimized intermediate mall order', function () {
    config(['services.google_maps.server_key' => 'test-maps-key']);

    Http::fake([
        '*' => Http::response([
            'routes' => [[
                'distanceMeters' => 24000,
                'duration' => '2400s',
                'polyline' => ['encodedPolyline' => '_p~iF~ps|U_ulLnnqC'],
                'optimizedIntermediateWaypointIndex' => [1, 0, 2],
                'legs' => array_fill(0, 4, [
                    'distanceMeters' => 8000,
                    'duration' => '800s',
                    'startLocation' => ['latLng' => ['latitude' => 3.5, 'longitude' => 98.6]],
                    'endLocation' => ['latLng' => ['latitude' => 3.6, 'longitude' => 98.7]],
                ]),
            ]],
        ], 200),
    ]);

    $route = app(GoogleMapsService::class)->planRoute([
        ['id' => 'mall_a', 'name' => 'Mall A', 'city' => 'Medan'],
        ['id' => 'mall_b', 'name' => 'Mall B', 'city' => 'Medan'],
        ['id' => 'mall_c', 'name' => 'Mall C', 'city' => 'Medan'],
        ['id' => 'mall_d', 'name' => 'Mall D', 'city' => 'Medan'],
    ], 3.5, 98.6);

    expect(array_column($route['malls'], 'id'))->toBe(['mall_b', 'mall_a', 'mall_c', 'mall_d'])
        ->and(count($route['legs']))->toBe(4);

    Http::assertSent(fn ($request): bool => str_contains($request->body(), '"optimizeWaypointOrder":true'));
});

test('Google Maps rejects a route that omits its optimized waypoint order', function () {
    config(['services.google_maps.server_key' => 'test-maps-key']);

    Http::fake([
        '*' => Http::response([
            'routes' => [[
                'distanceMeters' => 32000,
                'duration' => '3200s',
                'polyline' => ['encodedPolyline' => '_p~iF~ps|U_ulLnnqC'],
                'legs' => array_fill(0, 4, [
                    'distanceMeters' => 8000,
                    'duration' => '800s',
                ]),
            ]],
        ], 200),
    ]);

    expect(fn () => app(GoogleMapsService::class)->planRoute([
        ['id' => 'mall_a', 'name' => 'Mall A', 'city' => 'Medan'],
        ['id' => 'mall_b', 'name' => 'Mall B', 'city' => 'Medan'],
        ['id' => 'mall_c', 'name' => 'Mall C', 'city' => 'Medan'],
        ['id' => 'mall_d', 'name' => 'Mall D', 'city' => 'Medan'],
    ], 3.5, 98.6))->toThrow(RuntimeException::class, 'omitted the optimized waypoint order');
});

test('route planning resolves selected mall addresses and renders the Google route', function () {
    config(['services.google_maps.server_key' => 'test-maps-key']);

    Http::fake(function ($request) {
        if (str_contains($request->url(), 'computeRoutes')) {
            return Http::response([
                'routes' => [[
                    'distanceMeters' => 12000,
                    'duration' => '1200s',
                    'polyline' => ['encodedPolyline' => '_p~iF~ps|U_ulLnnqC'],
                    'legs' => [[
                        'distanceMeters' => 12000,
                        'duration' => '1200s',
                        'startLocation' => ['latLng' => ['latitude' => 3.5, 'longitude' => 98.6]],
                        'endLocation' => ['latLng' => ['latitude' => 3.6, 'longitude' => 98.7]],
                    ]],
                ]],
            ], 200);
        }

        return Http::response([
            'results' => ['bindings' => [
                [
                    'id' => ['value' => 'mall_a'],
                    'name' => ['value' => 'Mall A'],
                    'city' => ['value' => 'Medan'],
                    'province' => ['value' => 'Sumatera Utara'],
                    'address' => ['value' => 'One Street'],
                ],
                [
                    'id' => ['value' => 'mall_b'],
                    'name' => ['value' => 'Mall B'],
                    'city' => ['value' => 'Medan'],
                    'province' => ['value' => 'Sumatera Utara'],
                    'address' => ['value' => 'Two Street'],
                ],
            ]],
        ]);
    });

    $this->post('/planner/calculate', [
        'mall_ids' => ['mall_a', 'mall_b'],
        'locale' => 'en',
    ])->assertOk()
        ->assertSee('Google Maps Routes API')
        ->assertSee('Mall A')
        ->assertSee('Mall B')
        ->assertSee('google-directions-link', false);
});

test('route planner falls back to OSRM when Google Maps fails', function () {
    config([
        'services.google_maps.server_key' => 'test-maps-key',
        'services.osm.osrm_url' => 'https://router.project-osrm.org',
    ]);

    $province = Province::create([
        'name' => 'Sumatera Utara',
        'resource_uri' => 'http://sumateramall.org/resource/Province/sumatera_utara',
    ]);

    $city = City::create([
        'province_id' => $province->id,
        'name' => 'Medan',
        'resource_uri' => 'http://sumateramall.org/resource/City/medan',
    ]);

    foreach ([
        ['mall_a', 'Mall A', 3.6, 98.7],
        ['mall_b', 'Mall B', 3.7, 98.8],
    ] as [$id, $name, $lat, $lng]) {
        Mall::create([
            'city_id' => $city->id,
            'name' => $name,
            'latitude' => $lat,
            'longitude' => $lng,
            'source_namespace' => 'sumateramall',
            'resource_uri' => 'http://www.example.org/mall#'.$id,
        ]);
    }

    Http::fake(function ($request) {
        if (str_contains($request->url(), 'computeRoutes')) {
            return Http::response(['error' => 'Google Maps unavailable'], 503);
        }

        if (str_contains($request->url(), '/route/v1/driving/')) {
            return Http::response([
                'code' => 'Ok',
                'routes' => [[
                    'distance' => 2100,
                    'duration' => 1800,
                    'legs' => [
                        ['distance' => 2100, 'duration' => 1800],
                    ],
                    'geometry' => [
                        'coordinates' => [
                            [98.7, 3.6],
                            [98.8, 3.7],
                        ],
                    ],
                ]],
            ]);
        }

        return Http::response([
            'results' => ['bindings' => [
                [
                    'id' => ['value' => 'mall_a'],
                    'name' => ['value' => 'Mall A'],
                    'city' => ['value' => 'Medan'],
                    'province' => ['value' => 'Sumatera Utara'],
                    'address' => ['value' => 'One Street'],
                ],
                [
                    'id' => ['value' => 'mall_b'],
                    'name' => ['value' => 'Mall B'],
                    'city' => ['value' => 'Medan'],
                    'province' => ['value' => 'Sumatera Utara'],
                    'address' => ['value' => 'Two Street'],
                ],
            ]],
        ]);
    });

    $response = $this->post('/planner/calculate', [
        'mall_ids' => ['mall_a', 'mall_b'],
        'locale' => 'en',
    ]);

    $response->assertOk()
        ->assertSee('Mall A')
        ->assertSee('Mall B')
        ->assertSessionHas('planner.itinerary.source', 'openstreetmap')
        ->assertSessionHas('planner.itinerary.notice')
        ->assertSessionHas('planner.itinerary.malls', function ($malls) {
            return array_column($malls, 'id') === ['mall_a', 'mall_b'];
        });

    Http::assertSent(fn ($request): bool => str_contains($request->url(), '/route/v1/driving/')
    );
});

test('route planner shows an error when Google Maps fails and mall coordinates are missing', function () {
    config(['services.google_maps.server_key' => 'test-maps-key']);

    $province = Province::create([
        'name' => 'Sumatera Utara',
        'resource_uri' => 'http://sumateramall.org/resource/Province/sumatera_utara',
    ]);

    $city = City::create([
        'province_id' => $province->id,
        'name' => 'Medan',
        'resource_uri' => 'http://sumateramall.org/resource/City/medan',
    ]);

    Mall::create([
        'city_id' => $city->id,
        'name' => 'Mall A',
        'latitude' => 3.6,
        'longitude' => 98.7,
        'source_namespace' => 'sumateramall',
        'resource_uri' => 'http://www.example.org/mall#mall_a',
    ]);

    Mall::create([
        'city_id' => $city->id,
        'name' => 'Mall B',
        'source_namespace' => 'sumateramall',
        'resource_uri' => 'http://www.example.org/mall#mall_b',
    ]);

    Http::fake(function ($request) {
        if (str_contains($request->url(), 'computeRoutes')) {
            return Http::response(['error' => 'Google Maps unavailable'], 503);
        }

        return Http::response([
            'results' => ['bindings' => [
                [
                    'id' => ['value' => 'mall_a'],
                    'name' => ['value' => 'Mall A'],
                    'city' => ['value' => 'Medan'],
                    'province' => ['value' => 'Sumatera Utara'],
                    'address' => ['value' => 'One Street'],
                ],
                [
                    'id' => ['value' => 'mall_b'],
                    'name' => ['value' => 'Mall B'],
                    'city' => ['value' => 'Medan'],
                    'province' => ['value' => 'Sumatera Utara'],
                    'address' => ['value' => 'Two Street'],
                ],
            ]],
        ]);
    });

    $this->from('/planner')
        ->post('/planner/calculate', [
            'mall_ids' => ['mall_a', 'mall_b'],
            'locale' => 'en',
        ])
        ->assertRedirect('/planner')
        ->assertSessionHasErrors('mall_ids');

    Http::assertNotSent(fn ($request): bool => str_contains($request->url(), '/route/v1/driving/')
    );
});

test('route planner preserves the selected mall order when RDF returns a different order', function () {
    config(['services.google_maps.server_key' => 'test-maps-key']);

    Http::fake(function ($request) {
        if (str_contains($request->url(), 'computeRoutes')) {
            return Http::response([
                'routes' => [[
                    'distanceMeters' => 12000,
                    'duration' => '1200s',
                    'polyline' => ['encodedPolyline' => '_p~iF~ps|U_ulLnnqC'],
                    'legs' => [[
                        'distanceMeters' => 12000,
                        'duration' => '1200s',
                        'startLocation' => [
                            'latLng' => ['latitude' => 3.5, 'longitude' => 98.6],
                        ],
                        'endLocation' => [
                            'latLng' => ['latitude' => 3.6, 'longitude' => 98.7],
                        ],
                    ]],
                ]],
            ]);
        }

        // RDF sengaja mengembalikan Mall B lebih dulu.
        return Http::response([
            'results' => ['bindings' => [
                [
                    'id' => ['value' => 'mall_b'],
                    'name' => ['value' => 'Mall B'],
                    'city' => ['value' => 'Medan'],
                    'province' => ['value' => 'Sumatera Utara'],
                    'address' => ['value' => 'Two Street'],
                ],
                [
                    'id' => ['value' => 'mall_a'],
                    'name' => ['value' => 'Mall A'],
                    'city' => ['value' => 'Medan'],
                    'province' => ['value' => 'Sumatera Utara'],
                    'address' => ['value' => 'One Street'],
                ],
            ]],
        ]);
    });

    $response = $this->post('/planner/calculate', [
        'mall_ids' => ['mall_a', 'mall_b'],
        'locale' => 'en',
    ]);

    $response->assertOk()
        ->assertSessionHas('planner.itinerary.malls', function ($malls) {
            return array_column($malls, 'id') === ['mall_a', 'mall_b'];
        });

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'computeRoutes')
        && str_contains($request->body(), '"address":"One Street"')
        && str_contains($request->body(), '"address":"Two Street"')
        && strpos($request->body(), 'One Street') < strpos($request->body(), 'Two Street')
    );
});

test('nearby amenities are returned from OpenStreetMap with mapped labels', function () {
    $cacheKey = 'osm_nearby_'.md5(implode('|', [3.5, 98.6, 1500]));
    Cache::forget($cacheKey);

    Http::fake([
        '*' => Http::response([
            'elements' => [
                [
                    'type' => 'node',
                    'id' => 42,
                    'lat' => 3.501,
                    'lon' => 98.601,
                    'tags' => ['amenity' => 'atm', 'name' => 'ATM Example'],
                ],
            ],
        ], 200),
    ]);

    $this->getJson('/map/nearby?lat=3.5&lng=98.6')
        ->assertOk()
        ->assertJsonPath('amenities.0.name', 'ATM Example')
        ->assertJsonPath('amenities.0.type', 'ATM');
});

test('the budget estimator interpolates available RDF price levels', function () {
    Http::fake(function ($request) {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $queryParameters);
        $sparql = $queryParameters['query'] ?? '';

        if (str_contains($sparql, 'AVG(')) {
            return Http::response([
                'results' => ['bindings' => [[
                    'averagePriceLevel' => ['value' => '2.5'],
                    'pricedStores' => ['value' => '4'],
                ]]],
            ], 200);
        }

        return Http::response([
            'results' => ['bindings' => [[
                'id' => ['value' => 'http://sumateramall.org/resource/Mall/sun_plaza'],
                'name' => ['value' => 'Sun Plaza'],
                'city' => ['value' => 'Medan'],
            ]]],
        ], 200);
    });

    $this->get('/budget/estimate?mall=http%3A%2F%2Fsumateramall.org%2Fresource%2FMall%2Fsun_plaza&people=2')
        ->assertOk()
        ->assertSee('Rp 225.000')
        ->assertSeeText('4 toko');
});

test('the calendar export uses the route stored in the session', function () {
    $this->withSession([
        'planner.itinerary' => [
            'plannedAt' => '2026-11-14T09:00:00+07:00',
            'source' => 'openstreetmap',
            'totalDistance' => 2100,
            'totalDuration' => 1800,
            'notice' => null,
            'malls' => [
                ['id' => 'sun_plaza', 'name' => 'Sun Plaza', 'city' => 'Medan'],
                ['id' => 'deli_park_mall', 'name' => 'Deli Park Mall', 'city' => 'Medan'],
            ],
        ],
    ])->get('/planner/export/ics')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
        ->assertSee('SUMMARY:Visit Sun Plaza', false)
        ->assertSee('SUMMARY:Visit Deli Park Mall', false);
});

test('the PDF export downloads the stored itinerary', function () {
    $this->withSession([
        'planner.itinerary' => [
            'plannedAt' => '2026-11-14T09:00:00+07:00',
            'source' => 'openstreetmap',
            'totalDistance' => 2100,
            'totalDuration' => 1800,
            'notice' => null,
            'malls' => [
                ['id' => 'sun_plaza', 'name' => 'Sun Plaza', 'city' => 'Medan'],
                ['id' => 'deli_park_mall', 'name' => 'Deli Park Mall', 'city' => 'Medan'],
            ],
        ],
    ])->get('/planner/export/pdf')
        ->assertOk()
        ->assertDownload('sumateramall-itinerary.pdf')
        ->assertHeader('content-type', 'application/pdf');
});

test('chat history is only accessible to its owner', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    ChatLog::create([
        'user_id' => $owner->id,
        'session_id' => 'owner-session',
        'user_message' => 'Cari cafe di Sun Plaza',
        'bot_reply' => 'Aku menemukan Cafe Example.',
        'intent_detected' => 'find_store',
        'sparql_query_used' => null,
    ]);

    ChatLog::create([
        'user_id' => $otherUser->id,
        'session_id' => 'other-session',
        'user_message' => 'Pesan rahasia akun lain',
        'bot_reply' => 'Jawaban akun lain',
        'intent_detected' => 'unknown',
        'sparql_query_used' => null,
    ]);

    $this->getJson(route('chatbot.history'))
        ->assertUnauthorized();

    $this->actingAs($owner)
        ->getJson(route('chatbot.history'))
        ->assertOk()
        ->assertJsonCount(1, 'history')
        ->assertJsonPath('history.0.message', 'Cari cafe di Sun Plaza')
        ->assertDontSee('Pesan rahasia akun lain');
});
