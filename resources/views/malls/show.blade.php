@extends('layouts.app')

@section('title', $mall['name'])
@section('og_title', $mall['name'])
@section('og_description', $mall['desc'] ?? 'Mall di '.$mall['city'])
@section('og_type', 'place')

@section('content')
<div class="max-w-6xl mx-auto px-6" typeof="schema:ShoppingCenter"
     resource="{{ $mall['resource_uri'] }}">
    <div class="glass rounded-3xl p-8 mb-8">
        <h1 property="schema:name" class="font-display text-5xl font-bold mb-3">{{ $mall['name'] }}</h1>
        <div class="flex gap-3 mb-5">
            <span class="glass px-4 py-1 rounded-full text-sm">{{ $mall['city'] }}</span>
            <span class="glass px-4 py-1 rounded-full text-sm">{{ $mall['province'] }}</span>
        </div>
        @if(!empty($mall['desc']))
        <p class="text-gray-300 text-lg" property="schema:description">{{ $mall['desc'] }}</p>
        @endif
        @if(!empty($mall['address']))
        <p class="text-gray-400 mt-3">{{ $mall['address'] }}</p>
        @endif
    </div>

    @if((!empty($mall['lat']) && !empty($mall['lng'])) || !empty($mall['address']))
    <div class="glass rounded-3xl p-6 mb-8">
        <h2 data-i18n="location" class="font-display text-2xl font-bold mb-4">Lokasi</h2>
        <div id="mall-map" class="rounded-2xl" style="height: 400px;"></div>
        <p id="mall-map-status" class="text-sm text-gray-400 mt-3" role="status" aria-live="polite"></p>
        <a class="inline-block text-sm text-neon-cyan mt-3 hover:underline"
           href="https://www.google.com/maps/search/?api=1&amp;query={{ urlencode(($mall['address'] ?? $mall['name']).', '.$mall['city'].', '.$mall['province'].', Indonesia') }}"
           target="_blank" rel="noopener noreferrer" data-i18n="maps_open">Buka di Google Maps</a>
        <button data-i18n="nearby_button" type="button" id="load-nearby" class="glass mt-4 px-5 py-3 rounded-xl hover:border-neon-cyan/50">
            Cari masjid, ATM, dan toilet di sekitar
        </button>
        <p id="nearby-status" class="text-sm text-gray-400 mt-3" role="status" aria-live="polite"></p>
        <div id="nearby-results" class="grid md:grid-cols-2 gap-3 mt-4"></div>
    </div>
    @endif
    @if(!empty($mall['hours']) || !empty($mall['website']) || !empty($mall['floors']))
    <div class="glass rounded-3xl p-6 mb-8 grid md:grid-cols-3 gap-4">
        @if(!empty($mall['hours']))
        <div><p data-i18n="mall_hours" class="text-sm text-gray-400">Jam operasional</p><p class="mt-1">{{ $mall['hours'] }}</p></div>
        @endif
        @if(!empty($mall['website']))
        <div><p data-i18n="mall_website" class="text-sm text-gray-400">Situs web</p><a class="mt-1 text-neon-cyan hover:underline" href="{{ $mall['website'] }}" target="_blank" rel="noopener noreferrer">{{ $mall['website'] }}</a></div>
        @endif
        @if(!empty($mall['floors']))
        <div><p class="text-sm text-gray-400">Floors</p><p class="mt-1">{{ $mall['floors'] }}</p></div>
        @endif
    </div>
    @endif

    <div class="glass rounded-3xl p-8 mb-8">
        <h2 data-i18n="accessibility" class="font-display text-2xl font-bold mb-4">Aksesibilitas</h2>
        <div class="grid md:grid-cols-3 gap-4">
            @foreach(['wheelchair' => 'Akses kursi roda', 'parking' => 'Parkir', 'elevator' => 'Elevator'] as $key => $label)
            <div class="glass rounded-xl p-4">
                <p data-i18n="accessibility_{{ $key }}" class="text-sm text-gray-400">{{ $label }}</p>
                @if(!isset($mall[$key]))
                    <p data-i18n="not_recorded" class="mt-1">Informasi belum tersedia</p>
                @elseif(in_array(strtolower($mall[$key]), ['true', '1', 'yes'], true))
                    <p data-i18n="available" class="mt-1 text-green-300">Tersedia</p>
                @else
                    <p data-i18n="not_recorded" class="mt-1 text-amber-200">Tidak tercatat</p>
                @endif
            </div>
            @endforeach
        </div>
        <p data-i18n="accessibility_note" class="text-xs text-gray-500 mt-4">Informasi aksesibilitas hanya ditampilkan jika tersedia di RDF; status yang tidak tercatat tidak dianggap tidak tersedia.</p>
    </div>

    <div class="glass rounded-3xl p-8">
        <div class="flex justify-between items-center mb-6">
            <h2 data-i18n="stores_in_mall" class="font-display text-2xl font-bold">Toko di Mall Ini</h2>
            <span class="glass px-4 py-2 rounded-full text-sm">{{ count($stores) }} <span data-i18n="store_count">toko</span></span>
        </div>
        
@if(count($stores) > 0)
    @php($categories = collect($stores)->pluck('category')->filter()->unique()->sort())
    @php($priceLevels = collect($stores)->pluck('priceLevel')->filter()->unique()->sort())

    <div class="glass rounded-2xl p-4 mb-5">
        <p class="text-xs text-gray-400 mb-2" data-i18n="filter_category">Kategori</p>
        <div class="bubble-filter-row mb-3" role="group" aria-label="Filter stores by category">
            <button type="button" data-store-category="" class="bubble-filter bubble-filter--active" aria-pressed="true" data-i18n="filter_all">Semua</button>
            @foreach($categories as $category)
                <button type="button" data-store-category="{{ $category }}" class="bubble-filter" aria-pressed="false">{{ $category }}</button>
            @endforeach
        </div>

        <p class="text-xs text-gray-400 mb-2" data-i18n="filter_rating_price">Rating dan kisaran harga</p>
        <div class="bubble-filter-row" role="group" aria-label="Filter stores by rating and price">
            <button type="button" data-store-rating="0" class="bubble-filter bubble-filter--active" aria-pressed="true" data-i18n="filter_any_rating">Semua rating</button>
            <button type="button" data-store-rating="4" class="bubble-filter" aria-pressed="false">★ 4.0+</button>
            <button type="button" data-store-rating="4.5" class="bubble-filter" aria-pressed="false">★ 4.5+</button>
            <button type="button" data-store-price="" class="bubble-filter bubble-filter--active" aria-pressed="true" data-i18n="filter_any_price">Semua harga</button>
            @foreach($priceLevels as $priceLevel)
                <button type="button" data-store-price="{{ $priceLevel }}" class="bubble-filter" aria-pressed="false">Rp {{ $priceLevel }} / 4</button>
            @endforeach
        </div>

        <p class="text-xs text-gray-500 mt-3">
            <span data-i18n="showing">Menampilkan</span>
            <strong id="store-filter-count">{{ count($stores) }}</strong>
            <span data-i18n="store_count">toko</span>
        </p>
    </div>

    <div class="grid md:grid-cols-2 gap-4" id="stores-container">
        @foreach($stores as $store)
            <article class="glass rounded-xl p-5 hover:border-neon-cyan/50 transition-all store-item"
                     data-category="{{ $store['category'] }}"
                     data-rating="{{ $store['rating'] ?? '' }}"
                     data-price-level="{{ $store['priceLevel'] ?? '' }}"
                     typeof="schema:Store">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h3 class="font-display font-bold text-lg" property="schema:name">{{ $store['storeName'] }}</h3>
                        <p class="text-sm text-gray-400">{{ $store['category'] }}</p>
                    </div>
                    @if(!empty($store['rating']))
                        <span class="text-sm font-semibold">★ {{ $store['rating'] }}</span>
                    @endif
                </div>

                @if(!empty($store['status']))
                    <p class="mt-3 text-xs {{ $store['status'] === 'OPERATIONAL' ? 'text-green-400' : 'text-red-400' }}">
                        @if($store['status'] === 'OPERATIONAL')
                            <span data-i18n="operational">Operasional</span>
                        @else
                            {{ $store['status'] }}
                        @endif
                    </p>
                @endif

                @if(!empty($store['floor']))
                    <p class="text-xs text-gray-400 mt-2">
                        <span data-i18n="store_floor">Lantai</span>: {{ $store['floor'] }}
                    </p>
                @endif

                @if(!empty($store['priceLevel']))
                    <p class="text-xs text-gray-400 mt-2">Price level {{ $store['priceLevel'] }} / 4</p>
                @endif
            </article>
        @endforeach
    </div>
@else
    <div class="glass rounded-2xl p-8 text-center">
        <p class="text-gray-400" data-i18n="stores_empty">
            Belum ada data toko untuk mall ini.
        </p>
    </div>
@endif
        <div class="glass rounded-2xl p-4 mb-5">
        <p class="text-xs text-gray-400 mb-2" data-i18n="filter_category">Kategori</p>
        <div class="bubble-filter-row mb-3" role="group" aria-label="Filter stores by category">
            <button type="button" data-store-category="" class="bubble-filter bubble-filter--active" aria-pressed="true" data-i18n="filter_all">Semua</button>
            @foreach($categories as $category)
            <button type="button" data-store-category="{{ $category }}" class="bubble-filter" aria-pressed="false">{{ $category }}</button>
            @endforeach
        </div>
        <p class="text-xs text-gray-400 mb-2" data-i18n="filter_rating_price">Rating dan kisaran harga</p>
        <div class="bubble-filter-row" role="group" aria-label="Filter stores by rating and price">
            <button type="button" data-store-rating="0" class="bubble-filter bubble-filter--active" aria-pressed="true" data-i18n="filter_any_rating">Semua rating</button>
            <button type="button" data-store-rating="4" class="bubble-filter" aria-pressed="false">★ 4.0+</button>
            <button type="button" data-store-rating="4.5" class="bubble-filter" aria-pressed="false">★ 4.5+</button>
            <button type="button" data-store-price="" class="bubble-filter bubble-filter--active" aria-pressed="true" data-i18n="filter_any_price">Semua harga</button>
            @foreach($priceLevels as $priceLevel)
            <button type="button" data-store-price="{{ $priceLevel }}" class="bubble-filter" aria-pressed="false">Rp {{ $priceLevel }} / 4</button>
            @endforeach
        </div>
        <p class="text-xs text-gray-500 mt-3"><span data-i18n="showing">Menampilkan</span> <strong id="store-filter-count">{{ count($stores) }}</strong> <span data-i18n="store_count">toko</span></p>
        </div>

        <div class="grid md:grid-cols-2 gap-4" id="stores-container">
            @forelse($stores as $store)
            <article class="glass rounded-xl p-5 hover:border-neon-cyan/50 transition-all store-item"
                     data-category="{{ $store['category'] }}" data-rating="{{ $store['rating'] ?? '' }}"
                     data-price-level="{{ $store['priceLevel'] ?? '' }}" typeof="schema:Store">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h3 class="font-display font-bold text-lg" property="schema:name">{{ $store['storeName'] }}</h3>
                        <p class="text-sm text-gray-400">{{ $store['category'] }}</p>
                    </div>
                    @if(!empty($store['rating']))
                    <span class="text-sm font-semibold">★ {{ $store['rating'] }}</span>
                    @endif
                </div>
                @if(!empty($store['status']))
                <p class="mt-3 text-xs {{ $store['status'] === 'OPERATIONAL' ? 'text-green-400' : 'text-red-400' }}">
                    @if($store['status'] === 'OPERATIONAL')
                    <span data-i18n="operational">Operasional</span>
                    @else
                    {{ $store['status'] }}
                    @endif
                </p>
                @endif
                @if(!empty($store['floor']))
                <p class="text-xs text-gray-400 mt-2"><span data-i18n="store_floor">Lantai</span>: {{ $store['floor'] }}</p>
                @endif
                @if(!empty($store['priceLevel']))
                <p class="text-xs text-gray-400 mt-2">Price level {{ $store['priceLevel'] }} / 4</p>
                @endif
            </article>
            @empty
            <p data-i18n="stores_empty" class="text-gray-400">Belum ada data toko untuk mall ini.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    let category = '';
    let minimumRating = 0;
    let priceLevel = '';
    const buttons = document.querySelectorAll('[data-store-category], [data-store-rating], [data-store-price]');
    const stores = Array.from(document.querySelectorAll('.store-item'));

    function updateStores() {
        let visible = 0;
        stores.forEach(store => {
            const matches = (!category || store.dataset.category === category)
                && (!minimumRating || Number(store.dataset.rating) >= minimumRating)
                && (!priceLevel || store.dataset.priceLevel === priceLevel);
            store.hidden = !matches;
            if (matches) visible++;
        });
        document.getElementById('store-filter-count').textContent = String(visible);
    }

    buttons.forEach(button => button.addEventListener('click', () => {
        if (button.hasAttribute('data-store-category')) category = button.dataset.storeCategory;
        if (button.hasAttribute('data-store-rating')) {
            const selectedRating = Number(button.dataset.storeRating);
            minimumRating = minimumRating === selectedRating ? 0 : selectedRating;
        }
        if (button.hasAttribute('data-store-price')) {
            priceLevel = priceLevel === button.dataset.storePrice ? '' : button.dataset.storePrice;
        }

        buttons.forEach(item => {
            const active = (item.hasAttribute('data-store-category') && item.dataset.storeCategory === category)
                || (item.hasAttribute('data-store-rating') && Number(item.dataset.storeRating) === minimumRating)
                || (item.hasAttribute('data-store-price') && item.dataset.storePrice === priceLevel);
            item.classList.toggle('bubble-filter--active', active);
            item.setAttribute('aria-pressed', String(active));
        });
        updateStores();
    }));

    updateStores();
});

@if((!empty($mall['lat']) && !empty($mall['lng'])) || !empty($mall['address']))
const mapElement = document.getElementById('mall-map');
const mapStatus = document.getElementById('mall-map-status');
const initialLat = {{ Illuminate\Support\Js::from($mall['lat'] ?? '') }};
const initialLng = {{ Illuminate\Support\Js::from($mall['lng'] ?? '') }};
const mallAddress = {{ Illuminate\Support\Js::from(($mall['address'] ?? $mall['name']).', '.$mall['city'].', '.$mall['province'].', Indonesia') }};
const mallName = {{ Illuminate\Support\Js::from($mall['name']) }};
let mallMap;
let mallPosition = initialLat !== '' && initialLng !== ''
    ? { lat: Number(initialLat), lng: Number(initialLng) }
    : null;

async function getMallPosition() {
    if (mallPosition) return mallPosition;
    const maps = await window.loadGoogleMaps();
    const { results } = await new google.maps.Geocoder().geocode({ address: mallAddress, region: 'ID' });
    if (!results?.length) throw new Error(window.sumateraTranslate('maps_location_failed'));
    mallPosition = results[0].geometry.location;
    return mallPosition;
}

async function initializeMallMap() {
    if (!window.sumateraGoogleMapsKey) {
        mapStatus.textContent = window.sumateraTranslate('maps_key_missing');
        return;
    }
    try {
        const maps = await window.loadGoogleMaps();
        await maps.importLibrary('maps');
        const position = await getMallPosition();
        mallMap = new maps.Map(mapElement, { center: position, zoom: 16 });
        new google.maps.Marker({ map: mallMap, position, title: mallName });
    } catch (error) {
        mapStatus.textContent = error.message || window.sumateraTranslate('maps_load_failed');
    }
}

document.addEventListener('DOMContentLoaded', initializeMallMap);

document.getElementById('load-nearby').addEventListener('click', async event => {
    const button = event.currentTarget;
    button.disabled = true;
    const status = document.getElementById('nearby-status');
    status.textContent = window.sumateraTranslate('nearby_loading');
    try {
        const position = await getMallPosition();
        const latitude = typeof position.lat === 'function' ? position.lat() : position.lat;
        const longitude = typeof position.lng === 'function' ? position.lng() : position.lng;
        const query = new URLSearchParams({ lat: latitude, lng: longitude });
        const response = await fetch(`{{ route('map.nearby') }}?${query}`);
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || window.sumateraTranslate('nearby_unavailable'));
        const results = document.getElementById('nearby-results');
        results.replaceChildren();
        data.amenities.forEach(amenity => {
            const item = document.createElement('div');
            item.className = 'glass rounded-xl p-4';
            const name = document.createElement('strong');
            name.textContent = amenity.name;
            const type = document.createElement('p');
            type.className = 'text-sm text-gray-400';
            type.textContent = amenity.type;
            item.append(name, type);
            results.append(item);
            if (mallMap) {
                new google.maps.Marker({
                    map: mallMap,
                    position: { lat: Number(amenity.lat), lng: Number(amenity.lng) },
                    title: `${amenity.name} (${amenity.type})`
                });
            }
        });
        status.textContent = data.amenities.length
            ? window.sumateraTranslate('nearby_found').replace(':count', data.amenities.length)
            : window.sumateraTranslate('nearby_none');
    } catch (error) {
        status.textContent = error.message || window.sumateraTranslate('nearby_failed');
    } finally {
        button.disabled = false;
    }
});
@endif
</script>
@endpush
