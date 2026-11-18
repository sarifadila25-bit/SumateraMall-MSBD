@extends('layouts.app')

@section('title', 'Pencarian Mall & Toko')

@section('content')
<div class="max-w-5xl mx-auto px-6">
    <div class="mb-12">
        <h1 data-i18n="page_search_title" class="font-display text-5xl font-bold mb-3">Pencarian Cerdas</h1>
        <p data-i18n="page_search_description" class="text-gray-400">Temukan toko dengan filter berbasis Web Semantik</p>
    </div>

    <form action="{{ route('search.filter') }}" method="POST" class="glass rounded-3xl p-8 mb-8">
        @csrf
        <div class="mb-6">
            <p class="text-sm font-medium text-gray-300 mb-3" data-i18n="quick_filters">Quick filters</p>
            <div class="bubble-filter-row" role="group" aria-label="Quick store filters">
                <button type="button" class="bubble-filter bubble-filter--active" data-search-category="" data-i18n="filter_all">All</button>
                @foreach($categories as $category)
                <button type="button" class="bubble-filter" data-search-category="{{ $category['id'] }}">{{ $category['name'] }}</button>
                @endforeach
                <button type="button" class="bubble-filter" data-search-open data-i18n="field_open_now">Buka sekarang</button>
                <button type="button" class="bubble-filter" data-search-rating="4.5">★ 4.5+</button>
            </div>
        </div>
        <div class="grid md:grid-cols-2 gap-6 mb-6">
            <div>
                <label data-i18n="field_city" class="block text-sm font-medium mb-2 text-gray-300">Kota</label>
                <select name="city" class="w-full glass bg-transparent p-3 rounded-xl border border-white/10 focus:border-neon-cyan/50 focus:outline-none">
                    <option value="" data-i18n="all_cities">Semua Kota</option>
                    @foreach($cities as $c)
                    <option value="{{ $c['id'] }}" {{ request('city') == $c['id'] ? 'selected' : '' }}>
                        {{ $c['name'] }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label data-i18n="field_category" class="block text-sm font-medium mb-2 text-gray-300">Kategori</label>
                <select name="category" class="w-full glass bg-transparent p-3 rounded-xl border border-white/10 focus:border-neon-cyan/50 focus:outline-none">
                    <option value="" data-i18n="all_categories">Semua Kategori</option>
                    @foreach($categories as $c)
                    <option value="{{ $c['id'] }}" {{ request('category') == $c['id'] ? 'selected' : '' }}>
                        {{ $c['name'] }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label data-i18n="field_mall" class="block text-sm font-medium mb-2 text-gray-300">Mall</label>
                <select name="mall" class="w-full glass bg-transparent p-3 rounded-xl border border-white/10 focus:border-neon-cyan/50 focus:outline-none">
                    <option value="" data-i18n="all_malls">Semua Mall</option>
                    @foreach($malls as $mall)
                    <option value="{{ $mall['id'] }}" {{ request('mall') == $mall['id'] ? 'selected' : '' }}>
                        {{ $mall['name'] }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label data-i18n="field_rating" class="block text-sm font-medium mb-2 text-gray-300">Rating Minimum</label>
                <select name="min_rating" class="w-full glass bg-transparent p-3 rounded-xl border border-white/10 focus:border-neon-cyan/50 focus:outline-none">
                    <option value="" data-i18n="all_ratings">Semua Rating</option>
                    <option value="4">4.0+</option>
                    <option value="4.5">4.5+</option>
                </select>
            </div>

            <div class="flex items-end">
                <label class="flex items-center gap-3 cursor-pointer glass p-3 rounded-xl w-full">
                    <input type="checkbox" name="open_now" value="1"
                           {{ request('open_now') ? 'checked' : '' }}
                           class="w-5 h-5 rounded">
                    <span data-i18n="field_open_now" class="font-medium">Buka Sekarang</span>
                </label>
            </div>
        </div>

        <div class="mb-6">
            <label data-i18n="field_store_search" class="block text-sm font-medium mb-2 text-gray-300">Cari nama toko</label>
            <div class="flex gap-2">
            <input type="search" name="search" value="{{ request('search') }}"
                   data-i18n-placeholder="field_store_search" placeholder="Cari nama toko"
                   class="flex-1 glass bg-transparent p-3 rounded-xl border border-white/10 focus:border-neon-cyan/50 focus:outline-none">
            <button type="button" id="voice-search" class="glass px-4 rounded-xl border border-white/10 hover:border-neon-cyan/50" aria-label="Search by voice" title="Search by voice">
                <span aria-hidden="true">🎙</span>
            </button>
            </div>
            <p id="voice-search-status" class="text-xs text-gray-500 mt-2" role="status" aria-live="polite"></p>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-gradient-to-r from-neon-cyan to-neon-purple text-black px-8 py-3 rounded-full font-semibold hover:shadow-2xl hover:shadow-neon-cyan/50 transition-all">
                <span data-i18n="search_button">Cari</span>
            </button>
            <a href="{{ route('search.index') }}" class="glass px-8 py-3 rounded-full font-semibold hover:bg-white/10 transition-all border border-white/20">
                <span data-i18n="reset_button">Reset</span>
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form[action="{{ route('search.filter') }}"]');
    const categorySelect = form?.querySelector('[name="category"]');
    const openNow = form?.querySelector('[name="open_now"]');
    const minRating = form?.querySelector('[name="min_rating"]');
    const chips = document.querySelectorAll('[data-search-category], [data-search-open], [data-search-rating]');

    chips.forEach(chip => chip.addEventListener('click', () => {
        if (chip.hasAttribute('data-search-category')) categorySelect.value = chip.dataset.searchCategory;
        if (chip.hasAttribute('data-search-open')) openNow.checked = !openNow.checked;
        if (chip.hasAttribute('data-search-rating')) minRating.value = chip.dataset.searchRating;

        chips.forEach(item => {
            const active = item === chip || (item.hasAttribute('data-search-open') && openNow.checked)
                || (item.hasAttribute('data-search-rating') && minRating.value === item.dataset.searchRating)
                || (item.hasAttribute('data-search-category') && categorySelect.value === item.dataset.searchCategory);
            item.classList.toggle('bubble-filter--active', active);
            item.setAttribute('aria-pressed', String(active));
        });

        form.requestSubmit();
    }));
});
</script>
@endpush