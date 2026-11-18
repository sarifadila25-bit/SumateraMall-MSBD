@extends('layouts.app')

@section('title', 'Hasil Pencarian')

@section('content')
<div class="max-w-7xl mx-auto px-6">
    <div class="mb-8">
        <h1 data-i18n="search_results_title" class="font-display text-4xl font-bold mb-2">Hasil Pencarian</h1>
        <p class="text-gray-400"><span data-i18n="search_results_found">Ditemukan</span> <strong class="text-neon-cyan">{{ count($results) }}</strong> <span data-i18n="search_results_count">hasil</span></p>
        @if($searchTerm !== '')
        <p class="text-sm text-gray-400 mt-2"><span data-i18n="search_results_for">Hasil untuk</span> <strong class="text-neon-gold">“{{ $searchTerm }}”</strong> <span data-i18n="fuzzy_search_note">— typo-friendly matching</span></p>
        @endif
    </div>

    @if(count($results) > 0)
    <div class="glass rounded-2xl p-5 mb-8">
        <p class="text-sm text-gray-400 mb-3" data-i18n="refine_results">Refine these results</p>
        <div class="bubble-filter-row mb-3" role="group" aria-label="Filter results by category">
            <button type="button" class="bubble-filter bubble-filter--active" data-result-category="" aria-pressed="true" data-i18n="filter_all">All categories</button>
            @foreach(collect($results)->pluck('category')->filter()->unique()->sort() as $category)
            <button type="button" class="bubble-filter" data-result-category="{{ $category }}" aria-pressed="false">{{ $category }}</button>
            @endforeach
        </div>
        <div class="bubble-filter-row" role="group" aria-label="Additional result filters">
            <button type="button" class="bubble-filter bubble-filter--active" data-result-city="" aria-pressed="true" data-i18n="all_cities">All cities</button>
            @foreach(collect($results)->pluck('city')->filter()->unique()->sort() as $city)
            <button type="button" class="bubble-filter" data-result-city="{{ $city }}" aria-pressed="false">{{ $city }}</button>
            @endforeach
            <button type="button" class="bubble-filter" data-result-rated="4" aria-pressed="false" data-i18n="filter_rated">★ 4.0+</button>
            <button type="button" class="bubble-filter" data-result-operational aria-pressed="false" data-i18n="filter_operational">Operational</button>
        </div>
    </div>
    @endif
    <p class="text-xs text-gray-500 mb-4"><span data-i18n="showing">Showing</span> <strong id="filtered-result-count">{{ count($results) }}</strong> <span data-i18n="search_results_count">results</span></p>

    <div class="grid md:grid-cols-3 gap-6" id="search-results-grid">
        @forelse($results as $r)
        <div class="glass rounded-2xl p-6 hover:border-neon-cyan/50 transition-all"
             data-result-card data-category="{{ $r['category'] }}" data-city="{{ $r['city'] }}"
             data-rating="{{ $r['rating'] ?? '' }}" data-status="{{ $r['status'] ?? '' }}">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="font-display font-bold text-lg mb-1">{{ $r['storeName'] }}</h3>
                    <p class="text-sm text-gray-400">{{ $r['mallName'] }}</p>
                </div>
                @if(!empty($r['rating']))
                <div class="flex items-center gap-1">
                    <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <span class="text-sm font-semibold">{{ $r['rating'] }}</span>
                </div>
                @endif
            </div>

            <div class="flex gap-2 flex-wrap mb-3">
                <span class="glass px-3 py-1 rounded-full text-xs">{{ $r['category'] }}</span>
                <span class="glass px-3 py-1 rounded-full text-xs">{{ $r['city'] }}</span>
            </div>

            @if(!empty($r['status']) && $r['status'] === 'OPERATIONAL')
            <div class="flex items-center gap-2 text-xs text-green-400">
                <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                <span data-i18n="operational">Operasional</span>
            </div>
            @endif
        </div>
        @empty
        <div class="col-span-3 glass rounded-2xl p-12 text-center">
            <p data-i18n="search_no_results" class="text-gray-400">Tidak ada hasil. Coba filter lain.</p>
        </div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    let category = '';
    let city = '';
    let minimumRating = 0;
    let operationalOnly = false;
    const cards = Array.from(document.querySelectorAll('[data-result-card]'));
    const chips = document.querySelectorAll('[data-result-category], [data-result-city], [data-result-rated], [data-result-operational]');
    const normalize = value => value.trim().toLocaleLowerCase('id');

    function updateResults() {
        let visible = 0;
        cards.forEach(card => {
            const matches = (!category || normalize(card.dataset.category) === normalize(category))
                && (!city || normalize(card.dataset.city) === normalize(city))
                && (!minimumRating || Number(card.dataset.rating) >= minimumRating)
                && (!operationalOnly || card.dataset.status === 'OPERATIONAL');
            card.hidden = !matches;
            if (matches) visible++;
        });
        document.getElementById('filtered-result-count').textContent = String(visible);
    }

    chips.forEach(chip => chip.addEventListener('click', () => {
        if (chip.hasAttribute('data-result-category')) category = chip.dataset.resultCategory;
        if (chip.hasAttribute('data-result-city')) city = chip.dataset.resultCity;
        if (chip.hasAttribute('data-result-rated')) {
            minimumRating = minimumRating === Number(chip.dataset.resultRated) ? 0 : Number(chip.dataset.resultRated);
        }
        if (chip.hasAttribute('data-result-operational')) operationalOnly = !operationalOnly;

        chips.forEach(item => {
            const active = (item.hasAttribute('data-result-category') && item.dataset.resultCategory === category)
                || (item.hasAttribute('data-result-city') && item.dataset.resultCity === city)
                || (item.hasAttribute('data-result-rated') && Number(item.dataset.resultRated) === minimumRating && minimumRating > 0)
                || (item.hasAttribute('data-result-operational') && operationalOnly);
            item.classList.toggle('bubble-filter--active', active);
            item.setAttribute('aria-pressed', String(active));
        });
        updateResults();
    }));
});
</script>
@endpush