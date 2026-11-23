<?php

namespace App\Http\Controllers;

use App\Models\Mall;
use Carbon\Carbon;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $now = Carbon::now('Asia/Jakarta');

        $malls = Mall::with(['city.province'])
            ->orderBy('name')
            ->get()
            ->map(function (Mall $mall) use ($now): array {
                $hours = $mall->opening_hours;

                return [
                    'id' => $mall->id,
                    'mall' => $mall->resource_uri,
                    'name' => $mall->name,
                    'city' => $mall->city?->name ?? '-',
                    'province' => $mall->city?->province?->name ?? '-',
                    'address' => $mall->address,
                    'hours' => $hours,
                    'is_open' => !empty($hours)
                        && $this->isOpenNow($hours, $now),
                ];
            });

        $dataUnavailable = false;
        $dataEmpty = $malls->isEmpty();

        return view('home', [
            'openMalls' => $malls->filter(
                fn (array $mall): bool => $mall['is_open']
            )->values(),
            'malls' => $malls->values(),
            'cities' => $malls->pluck('city')
                ->filter(fn ($city) => $city !== '-')
                ->unique()
                ->sort()
                ->values(),
            'dataUnavailable' => $dataUnavailable,
            'dataEmpty' => $dataEmpty,
        ]);
    }

    private function isOpenNow(string $hours, Carbon $now): bool
    {
        if (preg_match_all(
            '/(?<!\d)(\d{1,2})\s*[.:,]\s*(\d{2})(?!\d)/u',
            $hours,
            $matches,
            PREG_SET_ORDER
        ) < 2) {
            return false;
        }

        $openingHour = (int) $matches[0][1];
        $openingMinute = (int) $matches[0][2];
        $closingHour = (int) $matches[1][1];
        $closingMinute = (int) $matches[1][2];

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
            $closesAt = 24 * 60;
        }

        if ($opensAt === $closesAt) {
            return false;
        }

        return $opensAt < $closesAt
            ? $currentTime >= $opensAt && $currentTime < $closesAt
            : $currentTime >= $opensAt || $currentTime < $closesAt;
    }
}
