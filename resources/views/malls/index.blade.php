@extends('layouts.app')

@section('title', 'Daftar Mall Sumatera')

@section('content')
<div class="max-w-7xl mx-auto px-6">
    <div class="mb-12">
        <h1 data-i18n="page_malls_title" class="font-display text-5xl font-bold mb-3">Daftar Mall</h1>
        <p data-i18n="page_malls_description" class="text-gray-400">Seluruh pusat perbelanjaan di Pulau Sumatera</p>
    </div>

    <div class="glass rounded-2xl p-5 mb-8">
        <p class="text-sm text-gray-400 mb-3" data-i18n="filter_by_province">Filter berdasarkan provinsi atau kota</p>
        <div class="bubble-filter-row" role="group" aria-label="Filter mall">
            <button type="button" class="bubble-filter bubble-filter--active" data-mall-province="all" aria-pressed="true" data-i18n="filter_all">Semua</button>
            @foreach(collect($malls)->pluck('province')->filter()->unique()->sort() as $province)
            <button type="button" class="bubble-filter" data-mall-province="{{ $province }}" aria-pressed="false">{{ $province }}</button>
            @endforeach
        </div>
        <div class="bubble-filter-row mt-3" role="group" aria-label="Filter mall berdasarkan kota">
            <button type="button" class="bubble-filter bubble-filter--active" data-mall-city="all" aria-pressed="true" data-i18n="all_cities">Semua Kota</button>
            @foreach(collect($malls)->pluck('city')->filter()->unique()->sort() as $city)
            <button type="button" class="bubble-filter" data-mall-city="{{ $city }}" aria-pressed="false">{{ $city }}</button>
            @endforeach
        </div>
        <p class="text-xs text-gray-500 mt-3"><span data-i18n="showing">Menampilkan</span> <strong id="mall-filter-count">{{ count($malls) }}</strong> <span data-i18n="mall_items">mall</span></p>
    </div>

    <form action="{{ route('malls.compare') }}" method="GET" class="glass rounded-2xl p-6 mb-10">
        @if($errors->any())
        <p role="alert" class="mb-4 text-red-300">{{ $errors->first() }}</p>
        @endif
        <label data-i18n="compare_select_label" for="compare-malls" class="block text-sm font-medium mb-3">Bandingkan 2-3 mall</label>
        <div class="flex flex-col md:flex-row gap-3">
            <select id="compare-malls" name="mall_ids[]" multiple required size="3"
                    class="flex-1 glass bg-slate-950/60 p-3 rounded-xl border border-white/10">
                @foreach($malls as $mall)
                <option value="{{ $mall['id'] }}">{{ $mall['name'] }} — {{ $mall['city'] }}</option>
                @endforeach
            </select>
            <button class="bg-gradient-to-r from-neon-cyan to-neon-purple text-black px-6 py-3 rounded-xl font-semibold self-start">
                <span data-i18n="compare_button">Bandingkan</span>
            </button>
        </div>
        <p data-i18n="compare_help" class="text-xs text-gray-500 mt-2">Pilih 2 atau 3 mall (Ctrl/Cmd + klik).</p>
    </form>

    @foreach($grouped as $province => $malls)
    <div class="mb-12" data-mall-group>
        <h2 class="font-display text-2xl font-bold mb-6 flex items-center gap-3">
            <span class="w-1 h-8 bg-gradient-to-b from-neon-cyan to-neon-purple rounded-full"></span>
            {{ $province }}
        </h2>
        <div class="grid md:grid-cols-3 gap-6">
            @foreach($malls as $mall)
            <a href="{{ route('malls.show', ['id' => $mall['id']]) }}"
               class="glass rounded-2xl p-6 hover:border-neon-cyan/50 transition-all group block"
               data-mall-card data-province="{{ $mall['province'] }}" data-city="{{ $mall['city'] }}"
               typeof="schema:ShoppingCenter" resource="{{ $mall['mall'] }}">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-neon-cyan/20 to-neon-purple/20 flex items-center justify-center">
                        <svg class="w-6 h-6 text-neon-cyan" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <svg class="w-5 h-5 text-gray-500 group-hover:text-neon-cyan transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
                <h3 class="font-display text-xl font-bold mb-2" property="schema:name">{{ $mall['name'] }}</h3>
                <p class="text-sm text-gray-400">{{ $mall['city'] }}</p>
                @if(!empty($mall['desc']))
                <p class="text-sm text-gray-400 mt-2" property="schema:description">{{ Str::limit($mall['desc'], 100) }}</p>
                @endif
            </a>
            @endforeach
        </div>
    </div>
    @endforeach
    <div id="mall-filter-empty" class="glass rounded-2xl p-8 text-center text-gray-400" hidden data-i18n="mall_filter_empty">
        Tidak ada mall yang cocok dengan filter ini.
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    let province = '';
    let city = '';
    const normalize = value => value.trim().toLocaleLowerCase('id');
    const provinceButtons = document.querySelectorAll('[data-mall-province]');
    const cityButtons = document.querySelectorAll('[data-mall-city]');
    const cards = Array.from(document.querySelectorAll('[data-mall-card]'));
    const groups = document.querySelectorAll('[data-mall-group]');
    const empty = document.getElementById('mall-filter-empty');

    function update() {
        let visibleCount = 0;
        cards.forEach(card => {
            const matches = (!province || normalize(card.dataset.province) === normalize(province))
                && (!city || normalize(card.dataset.city) === normalize(city));
            card.hidden = !matches;
            if (matches) visibleCount++;
        });
        groups.forEach(group => {
            group.hidden = !group.querySelector('[data-mall-card]:not([hidden])');
        });
        document.getElementById('mall-filter-count').textContent = String(visibleCount);
        empty.hidden = visibleCount > 0;
    }

    provinceButtons.forEach(button => button.addEventListener('click', () => {
        province = button.dataset.mallProvince === 'all' ? '' : button.dataset.mallProvince;
        provinceButtons.forEach(item => {
            const active = item === button;
            item.classList.toggle('bubble-filter--active', active);
            item.setAttribute('aria-pressed', String(active));
        });
        if (city && !cards.some(card => normalize(card.dataset.city) === normalize(city)
            && normalize(card.dataset.province) === normalize(province))) {
            city = '';
            cityButtons.forEach(item => {
                const active = item.dataset.mallCity === 'all';
                item.classList.toggle('bubble-filter--active', active);
                item.setAttribute('aria-pressed', String(active));
            });
        }
        update();
    }));

    cityButtons.forEach(button => button.addEventListener('click', () => {
        city = button.dataset.mallCity === 'all' ? '' : button.dataset.mallCity;
        if (city) {
            province = '';
            provinceButtons.forEach(item => {
                const active = item.dataset.mallProvince === 'all';
                item.classList.toggle('bubble-filter--active', active);
                item.setAttribute('aria-pressed', String(active));
            });
        }
        cityButtons.forEach(item => {
            const active = item === button;
            item.classList.toggle('bubble-filter--active', active);
            item.setAttribute('aria-pressed', String(active));
        });
        update();
    }));
});
</script>
@endpush