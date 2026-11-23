@extends('layouts.app')

@section('title', 'SumateraMall — Mall Terdekat yang Buka')
@section('og_title', 'SumateraMall — Mall Terdekat yang Buka')
@section('og_description', 'Temukan mall yang buka dan cari toko favorit di seluruh Sumatera.')

@section('content')
<div class="mall-landing">
    <div class="mall-landing__inner">
        <section class="mall-landing__hero">
            <div class="mall-landing__intro">
                <span class="mall-landing__eyebrow" data-i18n="landing_eyebrow">YOUR SUMATRA SHOPPING GUIDE</span>
                <h1 data-i18n="landing_title" class="mall-landing__title">Temukan mall yang pas untuk hari ini.</h1>
                <p data-i18n="landing_description" class="mall-landing__description">
                    Lihat pusat belanja yang sedang buka, lalu temukan toko favorit Anda di dalamnya.
                </p>
                <div class="mall-landing__trust">
                    <span class="mall-landing__trust-dot"></span>
                    <span data-i18n="landing_coverage">10 provinsi di Sumatera</span>
                </div>
            </div>

            <form action="{{ route('search.filter') }}" method="GET" class="mall-landing__search">
                <div class="mall-landing__search-heading">
                    <span class="mall-landing__search-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <circle cx="11" cy="11" r="7" stroke-width="2"></circle>
                            <path d="m16 16 4 4" stroke-width="2" stroke-linecap="round"></path>
                        </svg>
                    </span>
                    <div>
                        <h2 data-i18n="landing_search_title">Cari toko favorit</h2>
                        <p data-i18n="landing_search_description">Telusuri toko di mall pilihan Anda</p>
                    </div>
                </div>
                <label class="sr-only" for="landing-store-search" data-i18n="field_store_search">Cari nama toko</label>
                <input id="landing-store-search" type="search" name="search" required
                       data-i18n-placeholder="landing_search_placeholder"
                       placeholder="Contoh: kopi, fashion, buku">
                <label class="sr-only" for="landing-store-mall" data-i18n="field_mall">Mall</label>
                <select id="landing-store-mall" name="mall">
                    <option value="" data-i18n="landing_any_mall">Semua mall</option>
                    @foreach($malls as $mall)
                    <option value="{{ $mall['mall'] }}">{{ $mall['name'] }} — {{ $mall['city'] }}</option>
                    @endforeach
                </select>
                <button type="submit" class="mall-landing__search-button">
                    <span data-i18n="landing_search_button">Cari toko</span>
                    <span aria-hidden="true">→</span>
                </button>
            </form>
        </section>

        <section class="mall-landing__open-section" aria-labelledby="open-malls-heading">
            <div class="mall-landing__section-heading">
                <div>
                    <span class="mall-landing__eyebrow mall-landing__eyebrow--blue" data-i18n="landing_open_eyebrow">READY WHEN YOU ARE</span>
                    <h2 id="open-malls-heading" data-i18n="landing_open_heading">Mall yang buka sekarang</h2>
                    <p data-i18n="landing_open_description">Pilih kota untuk melihat pilihan di sekitar Anda.</p>
                    <p class="mall-landing__result-count">
                        <strong id="mall-open-count">{{ count($openMalls) }}</strong>
                        <span data-i18n="landing_open_count">mall buka</span>
                    </p>
                </div>
                <div class="mall-landing__location-tools">
                    <label class="sr-only" for="mall-city-filter" data-i18n="field_city">Kota</label>
                    <select id="mall-city-filter">
                        <option value="" data-i18n="landing_all_cities">Semua kota</option>
                        @foreach($cities as $city)
                        <option value="{{ $city }}">{{ $city }}</option>
                        @endforeach
                    </select>
                    <button id="mall-use-location" type="button" class="mall-landing__location-button">
                        <span aria-hidden="true">⌖</span>
                        <span data-i18n="landing_use_location">Gunakan lokasi saya</span>
                    </button>
                </div>
            </div>

            <p class="mall-landing__location-note" id="mall-location-status" role="status" aria-live="polite"
               data-i18n="landing_location_note">
                Lokasi terdekat ditentukan berdasarkan kota; koordinat mall belum tersedia.
            </p>
            <div class="bubble-filter-row mall-landing__city-bubbles" role="group" aria-label="Filter open malls by city">
                <button type="button" class="bubble-filter bubble-filter--active" data-home-city="" aria-pressed="true" data-i18n="landing_all_cities">Semua kota</button>
                @foreach($cities as $city)
                <button type="button" class="bubble-filter" data-home-city="{{ $city }}" aria-pressed="false">{{ $city }}</button>
                @endforeach
            </div>

            @if($dataUnavailable)
            <div class="mall-landing__empty" role="alert">
                <h3 data-i18n="landing_data_unavailable">Data mall sementara tidak dapat dimuat.</h3>
                <p data-i18n="landing_data_retry">Silakan coba lagi beberapa saat lagi.</p>
            </div>
            @elseif($dataEmpty)
            <div class="mall-landing__empty" role="status">
                <h3 data-i18n="landing_no_data">Data mall belum tersedia.</h3>
                <p data-i18n="landing_connect_data">Hubungkan dataset RDF ke Fuseki untuk menampilkan mall yang buka.</p>
            </div>
            @else
            <div class="mall-landing__grid" id="open-malls-grid">
                @foreach($openMalls as $mall)
                <a href="{{ route('malls.show', ['id' => $mall['id']]) }}"
                   class="mall-landing__card" data-open-mall data-city="{{ $mall['city'] }}">
                    
                    <div class="mall-landing__card-art" data-art="{{ $loop->index % 4 }}">
                        @if(!empty($mall['image']))
                            <img
                                src="{{ $mall['image'] }}"
                                alt="Foto {{ $mall['name'] }}"
                                class="w-full h-full object-cover"
                                loading="lazy"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                            >
                            <div class="mall-landing__fallback" style="display:none;">
                                <svg class="mall-landing__building" viewBox="0 0 100 76" aria-hidden="true">
                                    <path d="M12 72V31h24v41M36 72V12h30v60M66 72V26h22v46M5 72h90" />
                                    <path d="M20 40h8m-8 9h8m-8 9h8m16-36h7m8 0h7M44 34h7m8 0h7M44 45h7m8 0h7M44 56h7m8 0h7m7-20h7m-7 11h7m-7 11h7" />
                                </svg>
                            </div>
                        @else
                            <svg class="mall-landing__building" viewBox="0 0 100 76" aria-hidden="true">
                                <path d="M12 72V31h24v41M36 72V12h30v60M66 72V26h22v46M5 72h90" />
                                <path d="M20 40h8m-8 9h8m-8 9h8m16-36h7m8 0h7M44 34h7m8 0h7M44 45h7m8 0h7M44 56h7m8 0h7m7-20h7m-7 11h7m-7 11h7" />
                            </svg>
                        @endif

                        <span class="mall-landing__card-city">{{ $mall['city'] }}</span>

                        <span class="mall-landing__open-badge">
                            <span></span>
                            <span data-i18n="landing_open_badge">Buka sekarang</span>
                        </span>
                    </div>

                    <div class="mall-landing__card-body">
                        <div class="mall-landing__card-title">
                            <h3>{{ $mall['name'] }}</h3>
                            <span aria-hidden="true">↗</span>
                        </div>
                        <p class="mall-landing__card-location">{{ $mall['city'] }} <span>·</span> {{ $mall['province'] }}</p>
                        <div class="mall-landing__card-footer">
                            <span class="mall-landing__hours-icon" aria-hidden="true">◷</span>
                            <span>{{ $mall['hours'] }}</span>
                            <span class="mall-landing__details" data-i18n="landing_view_details">Lihat detail</span>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>

            <div class="mall-landing__empty" id="mall-no-results" hidden>
                <h3 data-i18n="landing_no_open">Belum ada mall buka yang cocok di kota ini.</h3>
                <p data-i18n="landing_choose_another_city">Pilih kota lain untuk melihat pilihan yang tersedia.</p>
            </div>

            @if(count($openMalls) === 0)
            <div class="mall-landing__empty" id="mall-no-open">
                <h3 data-i18n="landing_no_open_anywhere">Tidak ada jadwal mall buka yang tersedia saat ini.</h3>
                <p data-i18n="landing_hours_note">Status buka dihitung dari jam operasional RDF dan mungkin belum mencakup jadwal hari khusus.</p>
            </div>
            @else
            <button type="button" id="mall-show-more" class="mall-landing__show-more" hidden
                    data-i18n="landing_show_more">Lihat semua mall yang buka</button>
            <p class="mall-landing__hours-note" data-i18n="landing_hours_note">
                Status buka dihitung dari jam operasional RDF dan mungkin belum mencakup jadwal hari khusus.
            </p>
            @endif
            @endif
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const cityFilter = document.getElementById('mall-city-filter');
    const cityBubbles = document.querySelectorAll('[data-home-city]');
    const locationButton = document.getElementById('mall-use-location');
    const locationStatus = document.getElementById('mall-location-status');
    const cards = Array.from(document.querySelectorAll('[data-open-mall]'));
    const emptyState = document.getElementById('mall-no-results');
    const showMoreButton = document.getElementById('mall-show-more');
    const openCount = document.getElementById('mall-open-count');
    let showAll = false;

    if (!cityFilter) return;

    const normalize = value => value.trim().toLocaleLowerCase('id');
    const refreshCards = () => {
        const selectedCity = normalize(cityFilter.value);
        const matchingCards = cards.filter(card => !selectedCity || normalize(card.dataset.city) === selectedCity);
        const visibleCards = showAll ? matchingCards : matchingCards.slice(0, 6);

        cards.forEach(card => {
            card.hidden = !visibleCards.includes(card);
        });
        if (emptyState) emptyState.hidden = matchingCards.length !== 0;
        if (openCount) openCount.textContent = String(matchingCards.length);
        if (showMoreButton) {
            showMoreButton.hidden = matchingCards.length <= visibleCards.length;
            showMoreButton.textContent = window.sumateraTranslate(showAll ? 'landing_show_less' : 'landing_show_more');
        }
    };

    cityFilter.addEventListener('change', () => {
        showAll = false;
        cityBubbles.forEach(button => {
            const active = normalize(button.dataset.homeCity) === normalize(cityFilter.value);
            button.classList.toggle('bubble-filter--active', active);
            button.setAttribute('aria-pressed', String(active));
        });
        refreshCards();
        locationStatus.textContent = cityFilter.value
            ? window.sumateraTranslate('landing_city_selected').replace(':city', cityFilter.value)
            : window.sumateraTranslate('landing_location_note');
    });

    cityBubbles.forEach(button => button.addEventListener('click', () => {
        cityFilter.value = button.dataset.homeCity;
        cityFilter.dispatchEvent(new Event('change'));
    }));

    showMoreButton?.addEventListener('click', () => {
        showAll = !showAll;
        refreshCards();
    });

    locationButton?.addEventListener('click', async () => {
        if (!navigator.geolocation) {
            locationStatus.textContent = window.sumateraTranslate('landing_geolocation_unsupported');
            return;
        }

        locationButton.disabled = true;
        locationStatus.textContent = window.sumateraTranslate('landing_location_loading');

        try {
            const position = await new Promise((resolve, reject) => {
                navigator.geolocation.getCurrentPosition(
                    resolve,
                    () => reject(new Error(window.sumateraTranslate('landing_location_failed'))),
                    { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 }
                );
            });
            const maps = await window.loadGoogleMaps();
            const geocoder = new maps.Geocoder();
            const { results } = await geocoder.geocode({
                location: { lat: position.coords.latitude, lng: position.coords.longitude },
                region: 'ID'
            });
            const components = (results || []).flatMap(result => result.address_components || []);
            const cityComponent = components.find(component => component.types.includes('locality'))
                || components.find(component => component.types.includes('administrative_area_level_2'));

            if (!cityComponent) throw new Error(window.sumateraTranslate('landing_city_not_found'));

            const cityName = normalize(cityComponent.long_name);
            const options = Array.from(cityFilter.options).slice(1);
            const cityOption = options.find(option => normalize(option.value) === cityName)
                || options.find(option => cityName.includes(normalize(option.value)) || normalize(option.value).includes(cityName));

            if (!cityOption) throw new Error(window.sumateraTranslate('landing_city_not_found'));

            cityFilter.value = cityOption.value;
            cityFilter.dispatchEvent(new Event('change'));
        } catch (error) {
            locationStatus.textContent = error.message || window.sumateraTranslate('landing_location_failed');
        } finally {
            locationButton.disabled = false;
        }
    });

    refreshCards();
});
</script>
@endpush
