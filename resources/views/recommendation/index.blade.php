@extends('layouts.app')

@section('title', 'Rekomendasi Toko')

@section('content')
<div class="max-w-7xl mx-auto px-6">
    <div class="mb-8">
        <h1 data-i18n="page_recommend_title" class="font-display text-5xl font-bold mb-3">Rekomendasi Semantik</h1>
        <p data-i18n="page_recommend_description" class="text-gray-400">Rekomendasi berbasis RDF graph traversal</p>
    </div>

    <form action="{{ route('recommendation.index') }}" method="GET" class="glass rounded-3xl p-6 mb-8">
        <p class="text-sm text-gray-400 mb-3" data-i18n="filter_category">Kategori</p>
        <div class="bubble-filter-row mb-4" role="group" aria-label="Filter recommendations by category">
            <button type="button" class="bubble-filter {{ request('category') ? '' : 'bubble-filter--active' }}" data-rec-category="" aria-pressed="{{ request('category') ? 'false' : 'true' }}" data-i18n="filter_all">Semua</button>
            @foreach($categories as $category)
            <button type="button" class="bubble-filter {{ request('category') === $category['id'] ? 'bubble-filter--active' : '' }}" data-rec-category="{{ $category['id'] }}" aria-pressed="{{ request('category') === $category['id'] ? 'true' : 'false' }}">{{ $category['name'] }}</button>
            @endforeach
        </div>
        <p class="text-sm text-gray-400 mb-3" data-i18n="filter_rating">Rating minimum</p>
        <div class="bubble-filter-row mb-4" role="group" aria-label="Filter recommendations by minimum rating">
            <button type="button" class="bubble-filter {{ request('min_rating', '4.0') === '4.0' ? 'bubble-filter--active' : '' }}" data-rec-rating="4.0" aria-pressed="{{ request('min_rating', '4.0') === '4.0' ? 'true' : 'false' }}">★ 4.0+</button>
            <button type="button" class="bubble-filter {{ request('min_rating') === '4.5' ? 'bubble-filter--active' : '' }}" data-rec-rating="4.5" aria-pressed="{{ request('min_rating') === '4.5' ? 'true' : 'false' }}">★ 4.5+</button>
        </div>
        <div class="grid md:grid-cols-3 gap-4">
            <select name="category" class="glass bg-transparent p-3 rounded-xl border border-white/10 focus:border-neon-cyan/50 focus:outline-none">
                <option data-i18n="all_categories" value="">Semua Kategori</option>
                @foreach($categories as $category)
                <option value="{{ $category['id'] }}" @selected(request('category') === $category['id'])>{{ $category['name'] }}</option>
                @endforeach
            </select>
            <select name="min_rating" class="glass bg-transparent p-3 rounded-xl border border-white/10 focus:border-neon-cyan/50 focus:outline-none">
                <option value="4.0" @selected(request('min_rating', '4.0') === '4.0')>Rating 4.0+</option>
                <option value="4.5" @selected(request('min_rating') === '4.5')>Rating 4.5+</option>
            </select>
            <button type="submit" class="bg-gradient-to-r from-neon-cyan to-neon-purple text-black px-6 py-3 rounded-xl font-semibold">
                <span data-i18n="recommend_button">Cari Rekomendasi</span>
            </button>
        </div>
    </form>

    @if(isset($recommendations) && count($recommendations) > 0)
    <div class="glass rounded-2xl p-5 mb-6">
        <p class="text-sm text-gray-400 mb-3" data-i18n="refine_results">Persempit hasil</p>
        <div class="bubble-filter-row" role="group" aria-label="Refine recommendations by city">
            <button type="button" class="bubble-filter bubble-filter--active" data-recommend-city="" aria-pressed="true" data-i18n="all_cities">Semua kota</button>
            @foreach(collect($recommendations)->pluck('city')->filter()->unique()->sort() as $city)
            <button type="button" class="bubble-filter" data-recommend-city="{{ $city }}" aria-pressed="false">{{ $city }}</button>
            @endforeach
        </div>
        <div class="bubble-filter-row mt-2" role="group" aria-label="Refine recommendations by price level">
            <button type="button" class="bubble-filter bubble-filter--active" data-recommend-price="" aria-pressed="true" data-i18n="filter_any_price">Semua harga</button>
            @foreach(collect($recommendations)->pluck('priceLevel')->filter()->unique()->sort() as $price)
            <button type="button" class="bubble-filter" data-recommend-price="{{ $price }}" aria-pressed="false">Rp {{ $price }} / 4</button>
            @endforeach
        </div>
    </div>
    <p class="text-xs text-gray-500 mb-3"><span data-i18n="showing">Menampilkan</span> <strong id="recommendation-count">{{ count($recommendations) }}</strong> <span data-i18n="search_results_count">hasil</span></p>
    <div class="grid md:grid-cols-3 gap-6">
        @foreach($recommendations as $rec)
        <div class="glass rounded-2xl p-6 hover:border-neon-cyan/50 transition-all"
             data-recommendation-card data-city="{{ $rec['city'] }}" data-price="{{ $rec['priceLevel'] ?? '' }}">
            <div class="flex justify-between items-start mb-3">
                <h3 class="font-display font-bold text-lg">{{ $rec['storeName'] }}</h3>
                @if(!empty($rec['rating']))
                <div class="flex items-center gap-1">
                    <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <span class="text-sm font-semibold">{{ $rec['rating'] }}</span>
                </div>
                @endif
            </div>
            <p class="text-sm text-gray-400 mb-1">{{ $rec['mallName'] }}</p>
            <p class="text-xs text-gray-500 mb-3">{{ $rec['city'] }}</p>
            <div class="flex gap-2">
                <span class="glass px-3 py-1 rounded-full text-xs">{{ $rec['category'] }}</span>
                @if(!empty($rec['priceLevel']))
                <span class="glass px-3 py-1 rounded-full text-xs">Rp {{ str_repeat('$', $rec['priceLevel']) }}</span>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="glass rounded-2xl p-12 text-center">
        <p data-i18n="recommend_empty" class="text-gray-400">Pilih filter untuk melihat rekomendasi toko terbaik.</p>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form[action="{{ route('recommendation.index') }}"]');
    const categorySelect = form?.querySelector('[name="category"]');
    const ratingSelect = form?.querySelector('[name="min_rating"]');
    form?.querySelectorAll('[data-rec-category], [data-rec-rating]').forEach(button => {
        button.addEventListener('click', () => {
            if (button.hasAttribute('data-rec-category')) categorySelect.value = button.dataset.recCategory;
            if (button.hasAttribute('data-rec-rating')) ratingSelect.value = button.dataset.recRating;
            form.requestSubmit();
        });
    });

    let city = '';
    let price = '';
    const normalize = value => value.trim().toLocaleLowerCase('id');
    const cards = Array.from(document.querySelectorAll('[data-recommendation-card]'));
    const refiners = document.querySelectorAll('[data-recommend-city], [data-recommend-price]');
    refiners.forEach(button => button.addEventListener('click', () => {
        if (button.hasAttribute('data-recommend-city')) city = button.dataset.recommendCity;
        if (button.hasAttribute('data-recommend-price')) price = button.dataset.recommendPrice;
        let visible = 0;
        cards.forEach(card => {
            const matches = (!city || normalize(card.dataset.city) === normalize(city))
                && (!price || card.dataset.price === price);
            card.hidden = !matches;
            if (matches) visible++;
        });
        document.getElementById('recommendation-count').textContent = String(visible);
        refiners.forEach(item => {
            const active = (item.hasAttribute('data-recommend-city') && item.dataset.recommendCity === city)
                || (item.hasAttribute('data-recommend-price') && item.dataset.recommendPrice === price);
            item.classList.toggle('bubble-filter--active', active);
            item.setAttribute('aria-pressed', String(active));
        });
    }));
});
</script>
@endpush