@extends('layouts.app')

@section('title', 'Hasil Route Planner')

@section('content')
<div class="max-w-5xl mx-auto px-6">
    <div class="mb-8">
        <h1 data-i18n="route_result_title" class="font-display text-5xl font-bold mb-3">Rute Optimal Anda</h1>
        <p class="text-gray-400"><span data-i18n="route_through">Sumber rute:</span> Google Maps Routes API.</p>
    </div>

    @if($notice)
    <div data-i18n="route_estimate_notice" class="glass rounded-xl p-4 mb-6 border border-amber-400/40 text-amber-200">{{ $notice }}</div>
    @endif

    <div class="grid md:grid-cols-3 gap-6 mb-8">
        <div class="glass rounded-2xl p-6 text-center">
            <div class="font-display text-3xl font-bold gradient-text mb-2">
                {{ number_format($totalDistance / 1000, 1) }} km
            </div>
            <div data-i18n="route_total_distance" class="text-sm text-gray-400">Total Jarak</div>
        </div>
        <div class="glass rounded-2xl p-6 text-center">
            <div class="font-display text-3xl font-bold gradient-text mb-2">
                {{ floor($totalDuration / 3600) }} <span data-i18n="hours">jam</span> {{ floor(($totalDuration % 3600) / 60) }} <span data-i18n="minutes">menit</span>
            </div>
            <div data-i18n="route_total_duration" class="text-sm text-gray-400">Total Waktu</div>
        </div>
        <div class="glass rounded-2xl p-6 text-center">
            <div class="font-display text-3xl font-bold gradient-text mb-2">
                {{ count($malls) }}
            </div>
            <div data-i18n="route_malls_visited" class="text-sm text-gray-400">Mall Dikunjungi</div>
        </div>
    </div>

    <div class="glass rounded-3xl p-8 mb-8">
        <h2 data-i18n="route_visit_order" class="font-display text-2xl font-bold mb-6">Urutan Kunjungan</h2>
        <div id="route-map" class="mb-6 rounded-xl" style="height: 380px;"></div>
        <p id="route-map-status" class="text-sm text-gray-400 mb-4" role="status" aria-live="polite"></p>
        <div class="space-y-4">
            @foreach($malls as $index => $mall)
            <div class="flex items-center gap-4 glass rounded-xl p-4">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-neon-cyan to-neon-purple flex items-center justify-center font-bold text-black">
                    {{ $index + 1 }}
                </div>
                <div class="flex-1">
                    <h3 class="font-display font-bold">{{ $mall['name'] }}</h3>
                    <p class="text-sm text-gray-400">{{ $mall['city'] }}</p>
                </div>
                @php($legIndex = $startIsMall ? $index - 1 : $index)
                @if($legIndex >= 0 && isset($legs[$legIndex]))
                <div class="text-right">
                    <div class="text-sm text-neon-cyan">{{ $legs[$legIndex]['distance_text'] }}</div>
                    <div class="text-xs text-gray-400">{{ $legs[$legIndex]['duration_text'] }}</div>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    <div class="flex gap-4">
        <a id="google-directions-link" href="#" target="_blank" rel="noopener noreferrer"
           class="glass px-6 py-3 rounded-xl font-semibold hover:bg-white/10 transition-all border border-white/20"
           data-i18n="google_directions">Buka rute di Google Maps</a>
        <a href="{{ route('planner.index') }}" class="glass px-6 py-3 rounded-xl font-semibold hover:bg-white/10 transition-all border border-white/20">
            <span data-i18n="route_new">Buat Rute Baru</span>
        </a>
        <a data-i18n="route_export_pdf" href="{{ route('planner.export.pdf') }}" class="glass px-6 py-3 rounded-xl font-semibold hover:bg-white/10 transition-all border border-white/20">Unduh PDF</a>
        <a data-i18n="route_export_ics" href="{{ route('planner.export.ics') }}" class="glass px-6 py-3 rounded-xl font-semibold hover:bg-white/10 transition-all border border-white/20">Unduh Calendar (.ics)</a>
    </div>
</div>

@push('scripts')
<script>
const routePoints = @js($routeCoordinates);
const routeMalls = @js($malls);
const routeMapStatus = document.getElementById('route-map-status');

const originForDirections = {{ Illuminate\Support\Js::from($startIsMall
    ? (($origin['address'] ?? $origin['name']).', '.($origin['city'] ?? '').', Indonesia')
    : $origin['lat'].','.$origin['lng']) }};
const destinationForDirections = {{ Illuminate\Support\Js::from(
    (($malls[count($malls) - 1]['address'] ?? $malls[count($malls) - 1]['name']).', '.$malls[count($malls) - 1]['city'].', Indonesia')
) }};
const directionParameters = new URLSearchParams({
    api: '1',
    origin: originForDirections,
    destination: destinationForDirections,
    travelmode: 'driving'
});
const routeWaypoints = routeMalls.slice(0, -1).slice(@js($startIsMall ? 1 : 0));
if (routeWaypoints.length) {
    directionParameters.set('waypoints', routeWaypoints.map(mall =>
        `${mall.address || mall.name}, ${mall.city}, Indonesia`
    ).join('|'));
}
document.getElementById('google-directions-link').href =
    `https://www.google.com/maps/dir/?${directionParameters.toString()}`;

async function renderRouteMap() {
    if (!window.sumateraGoogleMapsKey) {
        routeMapStatus.textContent = window.sumateraTranslate('maps_key_missing');
        return;
    }
    try {
        const maps = await window.loadGoogleMaps();
        await maps.importLibrary('maps');
        const map = new maps.Map(document.getElementById('route-map'), {
            center: routePoints[0] ? { lat: routePoints[0][0], lng: routePoints[0][1] } : { lat: 0.5, lng: 101 },
            zoom: 7
        });
        const route = new google.maps.Polyline({
            path: routePoints.map(([lat, lng]) => ({ lat, lng })),
            geodesic: true,
            strokeColor: '#bd8f2f',
            strokeOpacity: 0.9,
            strokeWeight: 5
        });
        route.setMap(map);
        const bounds = new google.maps.LatLngBounds();
        routePoints.forEach(point => bounds.extend({ lat: point[0], lng: point[1] }));
        routeMalls.forEach((mall, index) => {
            if (!Number.isFinite(Number(mall.lat)) || !Number.isFinite(Number(mall.lng))) return;
            const position = { lat: Number(mall.lat), lng: Number(mall.lng) };
            bounds.extend(position);
            new google.maps.Marker({ map, position, title: `${index + 1}. ${mall.name}` });
        });
        if (routePoints.length) map.fitBounds(bounds, 24);
    } catch (error) {
        routeMapStatus.textContent = error.message || window.sumateraTranslate('maps_load_failed');
    }
}

document.addEventListener('DOMContentLoaded', renderRouteMap);
</script>
@endpush
@endsection