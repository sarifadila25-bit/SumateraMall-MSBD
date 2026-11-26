<?php

namespace App\Http\Controllers;

use App\Services\OsmService;
use App\Services\SparqlService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;

class MapController extends Controller
{
    public function __construct(
        private readonly SparqlService $sparql,
        private readonly OsmService $osm,
    ) {}

    public function index(): View
    {
        $malls = $this->sparql->query(<<<'SPARQL'
PREFIX mm: <http://sumateramall.org/ontology#>
PREFIX schema: <http://schema.org/>
SELECT ?id ?name ?city ?province ?address ?lat ?lng
WHERE {
    ?mall a mm:Mall ; schema:name ?name ;
          mm:inCity ?cityRes ; mm:inProvince ?provRes .
    ?cityRes schema:name ?city .
    ?provRes schema:name ?province .
    OPTIONAL { ?mall schema:address ?address }
    OPTIONAL { ?mall schema:geo ?geo . ?geo schema:latitude ?lat ; schema:longitude ?lng }
    BIND(STRAFTER(STR(?mall), 'resource/Mall/') AS ?id)
}
ORDER BY ?province ?city ?name
SPARQL, 60);

        return view('map.index', compact('malls'));
    }

    public function nearby(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['sometimes', 'integer', 'between:250,3000'],
        ]);
        $latitude = round((float) $validated['lat'], 4);
        $longitude = round((float) $validated['lng'], 4);
        $radius = (int) ($validated['radius'] ?? 1500);
        $cacheKey = 'osm_nearby_'.md5(implode('|', [$latitude, $longitude, $radius]));

        try {
            $amenities = Cache::remember($cacheKey, 1800, fn (): array => $this->osm->nearbyAmenities($latitude, $longitude, $radius));
        } catch (ConnectionException|RequestException|RuntimeException $exception) {
            Log::warning('Nearby OpenStreetMap amenities are temporarily unavailable.', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Nearby amenities are temporarily unavailable. Please try again later.',
            ], 503);
        }

        return response()->json(['amenities' => $amenities]);
    }
}
