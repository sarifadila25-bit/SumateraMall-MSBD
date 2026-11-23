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

        $imageFiles = collect(\Illuminate\Support\Facades\File::files(
    public_path('images/malls')
));

$imageAliases = [
    'Centre Point Medan' => 'centre-point',
    'Internasional Plaza' => 'international-plaza',
    'Mall Ciputra Seraya' => 'mall-ciputra-seraya-pekanbaru',
    'Mal SKA' => 'mall-ska-pekanbaru',
    'Mal Pekanbaru' => 'mal-pekanbaru',
    'OPI Mall' => 'OPI-Mall-Palembang',
    'The Plaza Millenium' => 'plaza-millenium-medan',
    'Yuki Simpang Raya' => 'yuki-simpang-raya-medna',
    'Mall Kapuk' => 'mall-kapuk-jambi',
    'Plaza Citra Pekanbaru' => 'plaza-citra-pekanbari',
    'Plaza Lotus' => 'plaza-lotus-lampung',
    'Sidimpuan City Walk' => 'sidempuan-city-walks',
    'The Central Plaza' => 'the-central-plaza-pekanbaru',
    'Medan Focal Point Mall' => 'medan-focal-point',
    'Lucky Plaza' => 'lucky-plaza-batam',
];

        $malls = Mall::with(['city.province'])
            ->orderBy('name')
            ->get()
            ->map(function (Mall $mall) use ($now, $imageFiles, $imageAliases): array {
                $hours = $mall->opening_hours;

                $imageName = $imageAliases[$mall->name]
    ?? \Illuminate\Support\Str::slug($mall->name);

$imageFile = $imageFiles->first(function ($file) use ($imageName) {
    return strtolower(pathinfo($file->getFilename(), PATHINFO_FILENAME))
        === strtolower($imageName);
});

$mallImage = $imageFile
    ? asset('images/malls/'.$imageFile->getFilename())
    : null;

                return [
                    'image' => $mallImage,
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
