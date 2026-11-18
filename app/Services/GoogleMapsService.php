<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleMapsService
{
    /**
     * @param  array<int, array{id: string, name: string, city: string, province?: string, address?: string, lat?: string|float, lng?: string|float}>  $stops
     * @return array<string, mixed>
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    public function planRoute(array $stops, ?float $startLat, ?float $startLng, string $locale = 'id'): array
    {
        if (count($stops) < 2) {
            throw new RuntimeException('Choose at least two malls to plan a route.');
        }
        if (blank(config('services.google_maps.server_key'))) {
            throw new RuntimeException('Google Maps Routes API server key is not configured.');
        }

        $startsAtMall = $startLat === null || $startLng === null;
        if ($startsAtMall) {
            $origin = $this->addressWaypoint($stops[0]);
            $mallStops = array_values(array_slice($stops, 1));
            $destinationStop = array_pop($mallStops);
        } else {
            $origin = ['location' => ['latLng' => ['latitude' => $startLat, 'longitude' => $startLng]]];
            $mallStops = array_values($stops);
            $destinationStop = array_pop($mallStops);
        }

        $intermediates = array_map(fn (array $stop): array => $this->addressWaypoint($stop), $mallStops);
        $destination = $this->addressWaypoint($destinationStop);
        $body = [
            'origin' => $origin,
            'destination' => $destination,
            'intermediates' => $intermediates,
            'travelMode' => 'DRIVE',
            'languageCode' => $locale === 'en' ? 'en-US' : 'id',
            'units' => 'METRIC',
        ];
        if (count($intermediates) > 1) {
            $body['optimizeWaypointOrder'] = true;
        }

        $response = Http::timeout(20)
            ->withHeaders([
                'X-Goog-Api-Key' => (string) config('services.google_maps.server_key'),
                'X-Goog-FieldMask' => implode(',', [
                    'routes.distanceMeters',
                    'routes.duration',
                    'routes.polyline.encodedPolyline',
                    'routes.legs.distanceMeters',
                    'routes.legs.duration',
                    'routes.legs.startLocation',
                    'routes.legs.endLocation',
                    'routes.optimizedIntermediateWaypointIndex',
                ]),
            ])
            ->post((string) config('services.google_maps.routes_url'), $body)
            ->throw();
        $data = $response->json();
        $route = is_array($data) ? ($data['routes'][0] ?? null) : null;

        if (! is_array($route)
            || ! is_numeric($route['distanceMeters'] ?? null)
            || ! is_string($route['duration'] ?? null)
            || ! is_array($route['legs'] ?? null)
            || count($route['legs']) !== ($startsAtMall ? count($stops) - 1 : count($stops))) {
            throw new RuntimeException('Google Routes API returned incomplete route data.');
        }

        $middleOrder = $route['optimizedIntermediateWaypointIndex'] ?? null;
        if ($middleOrder === null) {
            if (count($intermediates) > 1) {
                throw new RuntimeException('Google Routes API omitted the optimized waypoint order.');
            }
            $middleOrder = array_keys($intermediates);
        }
        if (! is_array($middleOrder)
            || ! array_is_list($middleOrder)
            || count($middleOrder) !== count($intermediates)
            || count(array_filter($middleOrder, 'is_int')) !== count($intermediates)
            || count(array_unique($middleOrder)) !== count($intermediates)
            || array_diff($middleOrder, array_keys($intermediates)) !== []) {
            throw new RuntimeException('Google Routes API returned an invalid optimized waypoint order.');
        }

        $ordered = array_map(fn (int $index): array => $mallStops[$index], $middleOrder);
        $ordered[] = $destinationStop;
        $orderedMalls = $startsAtMall ? array_merge([$stops[0]], $ordered) : $ordered;
        $legs = [];
        $mallCoordinates = [];

        foreach ($route['legs'] as $index => $leg) {
            if (! is_array($leg)
                || ! is_numeric($leg['distanceMeters'] ?? null)
                || ! is_string($leg['duration'] ?? null)) {
                throw new RuntimeException('Google Routes API returned invalid leg data.');
            }

            $duration = $this->durationSeconds($leg['duration']);
            $legs[] = [
                'distance' => (int) round((float) $leg['distanceMeters']),
                'duration' => $duration,
                'distance_text' => $this->formatDistance((float) $leg['distanceMeters']),
                'duration_text' => $this->formatDuration($duration),
                'start_address' => $startsAtMall
                    ? ($index === 0 ? $stops[0]['name'] : $orderedMalls[$index]['name'])
                    : ($index === 0 ? 'Starting location' : $orderedMalls[$index - 1]['name']),
                'end_address' => $orderedMalls[$startsAtMall ? $index + 1 : $index]['name'],
            ];

            $location = $leg['endLocation']['latLng'] ?? null;
            if (is_array($location)
                && is_numeric($location['latitude'] ?? null)
                && is_numeric($location['longitude'] ?? null)) {
                $mallCoordinates[] = [(float) $location['latitude'], (float) $location['longitude']];
            } else {
                $mallCoordinates[] = null;
            }
        }

        if ($startsAtMall) {
            $firstLocation = $route['legs'][0]['startLocation']['latLng'] ?? null;
            if (is_array($firstLocation)
                && is_numeric($firstLocation['latitude'] ?? null)
                && is_numeric($firstLocation['longitude'] ?? null)) {
                $orderedMalls[0]['lat'] = (float) $firstLocation['latitude'];
                $orderedMalls[0]['lng'] = (float) $firstLocation['longitude'];
            }
            foreach ($orderedMalls as $index => &$mall) {
                $coordinate = $mallCoordinates[$index - 1] ?? null;
                if ($index > 0 && $coordinate !== null) {
                    $mall['lat'] = $coordinate[0];
                    $mall['lng'] = $coordinate[1];
                }
            }
            unset($mall);
        } else {
            foreach ($orderedMalls as $index => &$mall) {
                $coordinate = $mallCoordinates[$index] ?? null;
                if ($coordinate !== null) {
                    $mall['lat'] = $coordinate[0];
                    $mall['lng'] = $coordinate[1];
                }
            }
            unset($mall);
        }

        $polyline = $route['polyline']['encodedPolyline'] ?? null;
        if (! is_string($polyline) || $polyline === '') {
            throw new RuntimeException('Google Routes API returned no route polyline.');
        }

        return [
            'origin' => $startsAtMall ? $stops[0] : ['name' => 'Starting location', 'lat' => $startLat, 'lng' => $startLng],
            'malls' => $orderedMalls,
            'legs' => $legs,
            'totalDistance' => (int) round((float) $route['distanceMeters']),
            'totalDuration' => $this->durationSeconds($route['duration']),
            'routeCoordinates' => $this->decodePolyline($polyline),
            'source' => 'google_maps',
            'notice' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $stop
     * @return array<string, string>
     */
    private function addressWaypoint(array $stop): array
    {
        $address = trim((string) ($stop['address'] ?? ''));
        if ($address === '') {
            $address = implode(', ', array_filter([
                $stop['name'] ?? '',
                $stop['city'] ?? '',
                $stop['province'] ?? '',
                'Sumatra, Indonesia',
            ]));
        }

        return ['address' => $address];
    }

    private function durationSeconds(string $duration): int
    {
        if (! preg_match('/\A(\d+(?:\.\d+)?)s\z/', $duration, $matches)) {
            throw new RuntimeException('Google Routes API returned an invalid duration.');
        }

        return (int) round((float) $matches[1]);
    }

    /**
     * @return array<int, array{0: float, 1: float}>
     */
    private function decodePolyline(string $encoded): array
    {
        $coordinates = [];
        $index = 0;
        $latitude = 0;
        $longitude = 0;
        $length = strlen($encoded);

        while ($index < $length) {
            $latitude += $this->decodePolylineValue($encoded, $index);
            $longitude += $this->decodePolylineValue($encoded, $index);
            $coordinates[] = [$latitude / 100000, $longitude / 100000];
        }

        if ($coordinates === []) {
            throw new RuntimeException('Google Routes API returned an invalid route polyline.');
        }

        return $coordinates;
    }

    private function decodePolylineValue(string $encoded, int &$index): int
    {
        $result = 0;
        $shift = 0;
        $length = strlen($encoded);

        do {
            if ($index >= $length) {
                throw new RuntimeException('Google Routes API returned a truncated route polyline.');
            }
            $value = ord($encoded[$index++]) - 63;
            if ($value < 0 || $value > 63) {
                throw new RuntimeException('Google Routes API returned an invalid route polyline.');
            }
            $result |= ($value & 0x1F) << $shift;
            $shift += 5;
        } while ($value >= 0x20);

        return ($result & 1) ? ~($result >> 1) : ($result >> 1);
    }

    private function formatDistance(float $metres): string
    {
        return $metres >= 1000 ? number_format($metres / 1000, 1).' km' : (int) round($metres).' m';
    }

    private function formatDuration(int $seconds): string
    {
        $minutes = (int) round($seconds / 60);

        return $minutes >= 60
            ? floor($minutes / 60).'h '.($minutes % 60).'m'
            : $minutes.' min';
    }
}
