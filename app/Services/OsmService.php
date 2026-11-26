<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OsmService
{
    /**
     * @param  array<int, array<string, mixed>>  $stops
     */
    private function validateCoordinates(
        array $stops,
        ?float $startLat,
        ?float $startLng
    ): void {
        foreach ($stops as $index => $stop) {
            $lat = $stop['lat'] ?? null;
            $lng = $stop['lng'] ?? null;

            if (
                ! is_numeric($lat) ||
                ! is_numeric($lng) ||
                (float) $lat < -90 ||
                (float) $lat > 90 ||
                (float) $lng < -180 ||
                (float) $lng > 180
            ) {
                throw new RuntimeException(
                    'Koordinat mall '.($stop['name'] ?? '#'.($index + 1)).' tidak tersedia atau tidak valid.'
                );
            }
        }

        if (($startLat === null) !== ($startLng === null)) {
            throw new RuntimeException('Koordinat lokasi awal tidak lengkap.');
        }

        if (
            $startLat !== null &&
            ($startLat < -90 || $startLat > 90 ||
             $startLng < -180 || $startLng > 180)
        ) {
            throw new RuntimeException('Koordinat lokasi awal tidak valid.');
        }
    }

    /**
     * @param  array<int, array{id: string, name: string, city: string, lat: string|float, lng: string|float}>  $stops
     * @return array<string, mixed>
     */
    public function planRoute(array $stops, ?float $startLat, ?float $startLng): array
    {
        if (count($stops) < 2) {
            throw new RuntimeException('Choose at least two malls to plan a route.');
        }

        $this->validateCoordinates($stops, $startLat, $startLng);

        $originIsFirstMall = $startLat === null || $startLng === null;
        $origin = $originIsFirstMall
            ? ['lat' => (float) $stops[0]['lat'], 'lng' => (float) $stops[0]['lng'], 'name' => $stops[0]['name']]
            : ['lat' => $startLat, 'lng' => $startLng, 'name' => 'Starting location'];
        $targetStops = $originIsFirstMall ? array_values(array_slice($stops, 1)) : array_values($stops);
        $orderedStops = $targetStops;
        $source = 'estimated';
        $notice = 'Road routing is unavailable; order and travel times are straight-line estimates.';

        try {
            if (count($targetStops) > 1) {
                $orderedStops = $this->optimizeStops($origin, $targetStops);
            }
            $route = $this->getRoadRoute($origin, $orderedStops);
            $source = 'openstreetmap';
            $notice = null;
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('OpenStreetMap route lookup failed; using a local estimate.', [
                'message' => $exception->getMessage(),
            ]);
            $orderedStops = $this->nearestNeighbourOrder($origin, $targetStops);
            $route = $this->estimateRoute($origin, $orderedStops);
        } catch (RuntimeException $exception) {
            Log::warning('OpenStreetMap route response was unusable; using a local estimate.', [
                'message' => $exception->getMessage(),
            ]);
            $orderedStops = $this->nearestNeighbourOrder($origin, $targetStops);
            $route = $this->estimateRoute($origin, $orderedStops);
        }

        return [
            'origin' => $origin,
            'malls' => $originIsFirstMall ? array_merge([$stops[0]], $orderedStops) : $orderedStops,
            'legs' => $route['legs'],
            'totalDistance' => $route['distance'],
            'totalDuration' => $route['duration'],
            'routeCoordinates' => $route['coordinates'],
            'source' => $source,
            'notice' => $notice,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws ConnectionException
     */
    public function nearbyAmenities(float $latitude, float $longitude, int $radius = 1500): array
    {
        $query = <<<OVERPASS
[out:json][timeout:20];
(
  nwr["amenity"="place_of_worship"]["religion"="muslim"](around:{$radius},{$latitude},{$longitude});
  nwr["building"="mosque"](around:{$radius},{$latitude},{$longitude});
  nwr["amenity"="atm"](around:{$radius},{$latitude},{$longitude});
  nwr["amenity"="toilets"](around:{$radius},{$latitude},{$longitude});
);
out center tags;
OVERPASS;

        $data = Http::timeout(25)
            ->withUserAgent((string) config('services.osm.user_agent'))
            ->asForm()
            ->post((string) config('services.osm.overpass_url'), ['data' => $query])
            ->throw()
            ->json();

        if (! is_array($data) || ! isset($data['elements']) || ! is_array($data['elements'])) {
            throw new RuntimeException('Overpass returned an invalid amenities response.');
        }

        $amenities = [];
        foreach ($data['elements'] as $element) {
            if (! is_array($element)) {
                continue;
            }
            $tags = $element['tags'] ?? [];
            if (! is_array($tags)) {
                continue;
            }
            $center = is_array($element['center'] ?? null) ? $element['center'] : [];
            $latitudeValue = $element['lat'] ?? $center['lat'] ?? null;
            $longitudeValue = $element['lon'] ?? $center['lon'] ?? null;

            if (! is_numeric($latitudeValue) || ! is_numeric($longitudeValue)) {
                continue;
            }

            $amenities[] = [
                'id' => ($element['type'] ?? 'node').'/'.($element['id'] ?? ''),
                'name' => $tags['name'] ?? 'Unnamed place',
                'type' => $this->amenityType($tags),
                'lat' => (float) $latitudeValue,
                'lng' => (float) $longitudeValue,
            ];
        }

        return $amenities;
    }

    /**
     * @param  array{lat: float, lng: float, name: string}  $origin
     * @param  array<int, array<string, mixed>>  $stops
     * @return array<int, array<string, mixed>>
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    private function optimizeStops(array $origin, array $stops): array
    {
        $coordinates = array_merge(
            [$origin],
            array_map(fn (array $stop): array => ['lat' => (float) $stop['lat'], 'lng' => (float) $stop['lng']], $stops),
        );
        $path = implode(';', array_map(fn (array $point): string => $point['lng'].','.$point['lat'], $coordinates));
        $nodes = range(0, count($stops));
        $table = Http::timeout(12)
            ->withUserAgent((string) config('services.osm.user_agent'))
            ->get(rtrim((string) config('services.osm.osrm_url'), '/').'/table/v1/driving/'.$path, [
                'sources' => implode(';', $nodes),
                'destinations' => implode(';', $nodes),
                'annotations' => 'duration',
            ])
            ->throw()
            ->json();

        if (! is_array($table)) {
            throw new RuntimeException('OSRM returned an invalid duration table.');
        }

        $durations = $table['durations'] ?? null;
        if (($table['code'] ?? null) !== 'Ok'
            || ! is_array($durations)
            || count($durations) !== count($stops) + 1) {
            throw new RuntimeException('OSRM could not build a complete route duration table.');
        }
        foreach ($durations as $row) {
            if (! is_array($row) || count($row) !== count($stops) + 1) {
                throw new RuntimeException('OSRM returned an incomplete duration matrix.');
            }
            foreach ($row as $duration) {
                if ($duration !== null && (! is_numeric($duration) || $duration < 0)) {
                    throw new RuntimeException('OSRM returned invalid route durations.');
                }
            }
        }

        $indexes = $this->optimalStopIndexes($durations, count($stops));

        return array_map(fn (int $index): array => $stops[$index - 1], $indexes);
    }

    /**
     * @param  array<int, array<int, float|int|null>>  $durationMatrix
     * @return array<int, int>
     */
    private function optimalStopIndexes(array $durationMatrix, int $stopCount): array
    {
        $allIndexes = range(0, $stopCount - 1);
        $costs = [];
        $previous = [];

        foreach ($allIndexes as $index) {
            $mask = 1 << $index;
            $costs[$mask][$index] = is_numeric($durationMatrix[0][$index + 1] ?? null)
                ? (float) $durationMatrix[0][$index + 1]
                : INF;
            $previous[$mask][$index] = null;
        }

        for ($mask = 1; $mask < (1 << $stopCount); $mask++) {
            foreach ($allIndexes as $last) {
                if (! isset($costs[$mask][$last])) {
                    continue;
                }

                foreach ($allIndexes as $next) {
                    if (($mask & (1 << $next)) !== 0) {
                        continue;
                    }

                    $nextMask = $mask | (1 << $next);
                    $legDuration = $durationMatrix[$last + 1][$next + 1] ?? INF;
                    $candidate = $costs[$mask][$last] + $legDuration;
                    if ($candidate < ($costs[$nextMask][$next] ?? INF)) {
                        $costs[$nextMask][$next] = $candidate;
                        $previous[$nextMask][$next] = $last;
                    }
                }
            }
        }

        $mask = (1 << $stopCount) - 1;
        $last = array_keys($costs[$mask] ?? []);
        if ($last === []) {
            throw new RuntimeException('OSRM did not provide enough data to optimize the selected malls.');
        }

        $current = array_reduce($last, fn (?int $best, int $candidate): int => $best === null
            || $costs[$mask][$candidate] < $costs[$mask][$best] ? $candidate : $best);
        $ordered = [];
        while ($current !== null) {
            $ordered[] = $current + 1;
            $parent = $previous[$mask][$current] ?? null;
            $mask ^= 1 << $current;
            $current = $parent;
        }

        return array_reverse($ordered);
    }

    /**
     * @param  array{lat: float, lng: float, name: string}  $origin
     * @param  array<int, array<string, mixed>>  $stops
     * @return array{distance: int, duration: int, legs: array<int, array<string, mixed>>, coordinates: array<int, array{0: float, 1: float}>}
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    private function getRoadRoute(array $origin, array $stops): array
    {
        $points = array_merge(
            [$origin],
            array_map(fn (array $stop): array => ['lat' => (float) $stop['lat'], 'lng' => (float) $stop['lng'], 'name' => $stop['name']], $stops),
        );
        $path = implode(';', array_map(fn (array $point): string => $point['lng'].','.$point['lat'], $points));
        $data = Http::timeout(15)
            ->withUserAgent((string) config('services.osm.user_agent'))
            ->get(rtrim((string) config('services.osm.osrm_url'), '/').'/route/v1/driving/'.$path, [
                'overview' => 'full',
                'geometries' => 'geojson',
                'steps' => 'false',
            ])
            ->throw()
            ->json();
        if (! is_array($data)) {
            throw new RuntimeException('OSRM returned an invalid route document.');
        }

        $route = $data['routes'][0] ?? null;
        if (($data['code'] ?? null) !== 'Ok'
            || ! is_array($route)
            || ! is_numeric($route['distance'] ?? null)
            || ! is_numeric($route['duration'] ?? null)
            || ! is_array($route['legs'] ?? null)
            || count($route['legs']) !== count($stops)) {
            throw new RuntimeException('OSRM could not find a drivable route for these locations.');
        }

        $legs = [];
        foreach ($route['legs'] ?? [] as $index => $leg) {
            if (! is_array($leg) || ! is_numeric($leg['distance'] ?? null) || ! is_numeric($leg['duration'] ?? null)) {
                throw new RuntimeException('OSRM returned invalid route leg data.');
            }
            $legs[] = [
                'distance' => (int) round($leg['distance'] ?? 0),
                'duration' => (int) round($leg['duration'] ?? 0),
                'distance_text' => $this->formatDistance((float) ($leg['distance'] ?? 0)),
                'duration_text' => $this->formatDuration((float) ($leg['duration'] ?? 0)),
                'start_address' => $points[$index]['name'],
                'end_address' => $points[$index + 1]['name'],
            ];
        }

        $geometry = is_array($route['geometry'] ?? null) ? $route['geometry'] : [];
        $coordinates = $geometry['coordinates'] ?? [];
        if (! is_array($coordinates)) {
            throw new RuntimeException('OSRM returned invalid route geometry.');
        }
        foreach ($coordinates as $coordinate) {
            if (! is_array($coordinate) || ! is_numeric($coordinate[0] ?? null) || ! is_numeric($coordinate[1] ?? null)) {
                throw new RuntimeException('OSRM returned invalid route coordinates.');
            }
        }

        return [
            'distance' => (int) round($route['distance']),
            'duration' => (int) round($route['duration']),
            'legs' => $legs,
            'coordinates' => array_map(fn (array $coordinate): array => [(float) $coordinate[1], (float) $coordinate[0]], $coordinates),
        ];
    }

    /**
     * @param  array{lat: float, lng: float, name: string}  $origin
     * @param  array<int, array<string, mixed>>  $stops
     * @return array<string, mixed>
     */
    private function estimateRoute(array $origin, array $stops): array
    {
        $points = array_merge([$origin], array_map(
            fn (array $stop): array => ['lat' => (float) $stop['lat'], 'lng' => (float) $stop['lng'], 'name' => $stop['name']],
            $stops,
        ));
        $legs = [];
        $distance = 0;
        $duration = 0;

        foreach (array_slice($points, 1) as $index => $point) {
            $legDistance = (int) round($this->haversineDistance($points[$index], $point) * 1000 * 1.3);
            $legDuration = (int) round($legDistance / 1000 / 25 * 3600);
            $distance += $legDistance;
            $duration += $legDuration;
            $legs[] = [
                'distance' => $legDistance,
                'duration' => $legDuration,
                'distance_text' => $this->formatDistance($legDistance),
                'duration_text' => $this->formatDuration($legDuration),
                'start_address' => $points[$index]['name'],
                'end_address' => $point['name'],
            ];
        }

        return [
            'distance' => $distance,
            'duration' => $duration,
            'legs' => $legs,
            'coordinates' => array_map(fn (array $point): array => [$point['lat'], $point['lng']], $points),
        ];
    }

    /**
     * @param  array{lat: float, lng: float, name: string}  $origin
     * @param  array<int, array<string, mixed>>  $stops
     * @return array<int, array<string, mixed>>
     */
    private function nearestNeighbourOrder(array $origin, array $stops): array
    {
        $ordered = [];
        $remaining = array_values($stops);
        $current = $origin;

        while ($remaining !== []) {
            usort($remaining, fn (array $left, array $right): int => $this->haversineDistance($current, $left) <=> $this->haversineDistance($current, $right));
            $current = array_shift($remaining);
            $ordered[] = $current;
        }

        return $ordered;
    }

    /**
     * @param  array{lat: float, lng: float}  $first
     * @param  array{lat: float, lng: float}  $second
     */
    private function haversineDistance(array $first, array $second): float
    {
        $latitudeDelta = deg2rad($second['lat'] - $first['lat']);
        $longitudeDelta = deg2rad($second['lng'] - $first['lng']);
        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($first['lat'])) * cos(deg2rad($second['lat'])) * sin($longitudeDelta / 2) ** 2;

        return 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * @param  array<string, string>  $tags
     */
    private function amenityType(array $tags): string
    {
        if (($tags['amenity'] ?? null) === 'atm') {
            return 'ATM';
        }

        if (($tags['amenity'] ?? null) === 'toilets') {
            return 'Restroom';
        }

        return 'Mosque';
    }

    private function formatDistance(float $metres): string
    {
        return $metres >= 1000 ? number_format($metres / 1000, 1).' km' : (int) round($metres).' m';
    }

    private function formatDuration(float $seconds): string
    {
        $minutes = (int) round($seconds / 60);

        return $minutes >= 60
            ? floor($minutes / 60).'h '.($minutes % 60).'m'
            : $minutes.' min';
    }
}
