<?php

namespace App\Console\Commands;

use App\Models\Mall;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[\Illuminate\Console\Attributes\Signature('malls:geocode {--limit=1 : Maximum number of malls to process; 0 means all}') ]
#[\Illuminate\Console\Attributes\Description('Find and save mall coordinates using OpenStreetMap Nominatim')]
class GeocodeMalls extends Command
{
    public function handle(): int
    {
        $limit = max(0, (int) $this->option('limit'));

        $malls = Mall::with('city.province')
            ->where(function ($query) {
                $query->whereNull('latitude')
                    ->orWhereNull('longitude');
            })
            ->orderBy('id')
            ->when($limit > 0, fn ($query) => $query->limit($limit))
            ->get();

        if ($malls->isEmpty()) {
            $this->info('All malls already have coordinates.');
            return self::SUCCESS;
        }

        $this->info("Found {$malls->count()} malls to process.");

        $processed = 0;
        $failed = 0;

        foreach ($malls as $mall) {
            $city = $mall->city?->name ?? '';
            $province = $mall->city?->province?->name ?? '';

            $parts = array_filter([
                $mall->name,
                $mall->address,
                $city,
                $province,
                'Indonesia',
            ]);

            $searchQuery = implode(', ', $parts);

            $this->newLine();
            $this->line("Searching: {$mall->name} ({$city})");

            try {
                $response = Http::acceptJson()
                    ->withUserAgent(config('services.osm.user_agent'))
                    ->timeout(20)
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'q' => $searchQuery,
                        'format' => 'jsonv2',
                        'limit' => 1,
                        'countrycodes' => 'id',
                    ]);

                if (! $response->successful()) {
                    $this->warn("Request failed: HTTP {$response->status()}");
                    $failed++;
                    sleep(1);
                    continue;
                }

                $result = $response->json()[0] ?? null;

                if (
                    ! is_array($result)
                    || ! isset($result['lat'], $result['lon'])
                    || ! is_numeric($result['lat'])
                    || ! is_numeric($result['lon'])
                ) {
                    $this->warn('No coordinates found.');
                    $failed++;
                    sleep(1);
                    continue;
                }

                $mall->update([
                    'latitude' => $result['lat'],
                    'longitude' => $result['lon'],
                ]);

                $this->info('Saved: '.$result['lat'].', '.$result['lon']);
                $this->line('OSM result: '.($result['display_name'] ?? 'Unknown'));

                $processed++;
            } catch (\Throwable $exception) {
                $this->error($exception->getMessage());
                $failed++;
            }

            // Nominatim public service: maximum one request per second.
            sleep(1);
        }

        $this->newLine();
        $this->info("Completed. Saved: {$processed}, Failed: {$failed}");

        return self::SUCCESS;
    }
}
