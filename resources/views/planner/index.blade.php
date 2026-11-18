@extends('layouts.app')

@section('title', 'Route Planner - Mall Hopping')

@section('content')
<div class="max-w-5xl mx-auto px-6">
    <div class="mb-8">
        <h1 data-i18n="page_route_title" class="font-display text-5xl font-bold mb-3">Route Planner</h1>
        <p data-i18n="route_description" class="text-gray-400">Rencanakan mall hopping optimal dengan estimasi budget</p>
    </div>

    @if(session('error'))
    <div class="glass rounded-xl p-4 mb-6 border border-red-500/50 text-red-400">
        {{ session('error') }}
    </div>
    @endif
    @if($errors->any())
    <div role="alert" class="glass rounded-xl p-4 mb-6 border border-red-500/50 text-red-300">{{ $errors->first() }}</div>
    @endif

    <form action="{{ route('planner.calculate') }}" method="POST" class="glass rounded-3xl p-8">
        @csrf
        <input type="hidden" name="locale" value="id" data-planner-locale>

        <div class="mb-6">
            <label data-i18n="route_start" class="block text-sm font-medium mb-2 text-gray-300">Lokasi awal (opsional; kosongkan untuk mulai dari mall pertama)</label>
            <div class="grid md:grid-cols-2 gap-4">
                <input type="number" step="any" min="-90" max="90" name="start_lat" value="{{ old('start_lat') }}"
                       class="glass bg-transparent p-3 rounded-xl border border-white/10 focus:border-neon-cyan/50 focus:outline-none"
                       placeholder="Latitude">
                <input type="number" step="any" min="-180" max="180" name="start_lng" value="{{ old('start_lng') }}"
                       class="glass bg-transparent p-3 rounded-xl border border-white/10 focus:border-neon-cyan/50 focus:outline-none"
                       placeholder="Longitude">
            </div>
            <button data-i18n="use_current_location" type="button" onclick="useCurrentLocation()" class="mt-2 text-sm text-neon-cyan hover:underline">
                Gunakan lokasi saya saat ini
            </button>
        </div>

        <div class="mb-6">
            <label data-i18n="route_stops" class="block text-sm font-medium mb-3 text-gray-300">Pilih Mall (2-8; alamat digunakan untuk rute)</label>
            <div class="bubble-filter-row mb-3" role="group" aria-label="Filter route stops by city">
                <button type="button" class="bubble-filter bubble-filter--active" data-route-city="" aria-pressed="true" data-i18n="all_cities">Semua Kota</button>
                @foreach(collect($malls)->pluck('city')->filter()->unique()->sort() as $city)
                <button type="button" class="bubble-filter" data-route-city="{{ $city }}" aria-pressed="false">{{ $city }}</button>
                @endforeach
            </div>
            <p class="text-xs text-gray-500 mb-3"><span data-i18n="showing">Menampilkan</span> <strong id="route-mall-count">{{ count($malls) }}</strong> <span data-i18n="mall_items">mall</span></p>
            <div class="grid md:grid-cols-2 gap-3 max-h-96 overflow-y-auto p-4 glass rounded-xl">
                @foreach($malls as $mall)
                <label data-route-mall data-city="{{ $mall['city'] }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-white/5 cursor-pointer">
                    <input type="checkbox" name="mall_ids[]" value="{{ $mall['id'] }}" data-route-stop
                           class="w-4 h-4 rounded border-white/20">
                    <div>
                        <div class="font-medium text-sm">{{ $mall['name'] }}</div>
                        <div class="text-xs text-gray-400">{{ $mall['city'] }}</div>
                    </div>
                </label>
                @endforeach
            </div>
        </div>

        <button type="submit"
                class="w-full bg-gradient-to-r from-neon-cyan to-neon-purple text-black py-4 rounded-xl font-semibold hover:shadow-2xl hover:shadow-neon-cyan/50 transition-all">
            <span data-i18n="route_submit">Hitung Rute Optimal</span>
        </button>
    </form>
</div>

@push('scripts')
<script>
function useCurrentLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            pos => {
                document.querySelector('[name=start_lat]').value = pos.coords.latitude;
                document.querySelector('[name=start_lng]').value = pos.coords.longitude;
            },
            () => alert(window.sumateraTranslate('geolocation_failed')),
            { enableHighAccuracy: true, timeout: 10000 }
        );
    } else {
        alert(window.sumateraTranslate('geolocation_unsupported'));
    }
}

document.querySelectorAll('[data-route-stop]').forEach(input => {
    input.addEventListener('change', () => {
        const selected = document.querySelectorAll('[data-route-stop]:checked');
        if (selected.length > 8) {
            input.checked = false;
            alert(window.sumateraTranslate('route_max_stops'));
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    let selectedCity = '';
    const normalize = value => value.trim().toLocaleLowerCase('id');
    const cityButtons = document.querySelectorAll('[data-route-city]');
    const mallOptions = Array.from(document.querySelectorAll('[data-route-mall]'));

    cityButtons.forEach(button => button.addEventListener('click', () => {
        selectedCity = button.dataset.routeCity;
        let visible = 0;
        mallOptions.forEach(option => {
            const matches = !selectedCity || normalize(option.dataset.city) === normalize(selectedCity);
            option.hidden = !matches;
            if (matches) visible++;
        });
        document.getElementById('route-mall-count').textContent = String(visible);
        cityButtons.forEach(item => {
            const active = item === button;
            item.classList.toggle('bubble-filter--active', active);
            item.setAttribute('aria-pressed', String(active));
        });
    }));
});
</script>
@endpush
@endsection