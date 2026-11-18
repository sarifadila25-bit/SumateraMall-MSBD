@extends('layouts.app')

@section('title', 'Estimasi Budget')

@section('content')
<div class="max-w-4xl mx-auto px-6">
    <div class="mb-8">
        <h1 data-i18n="page_budget_title" class="font-display text-5xl font-bold mb-3">Estimasi Budget</h1>
        <p data-i18n="page_budget_description" class="text-gray-400">Perkiraan belanja per orang berdasarkan price level toko yang tersedia.</p>
    </div>

    @if($errors->any())
    <div class="glass rounded-xl p-4 mb-6 border border-red-500/50 text-red-300">{{ $errors->first() }}</div>
    @endif

    <form action="{{ route('budget.estimate') }}" method="GET" class="glass rounded-3xl p-8">
        <div class="grid md:grid-cols-3 gap-5">
            <div class="md:col-span-2">
                <label data-i18n="field_mall" for="budget-mall" class="block text-sm mb-2">Mall</label>
                <div class="bubble-filter-row mb-3" role="group" aria-label="Filter mall choices by city">
                    <button type="button" class="bubble-filter bubble-filter--active" data-budget-city="" aria-pressed="true" data-i18n="all_cities">Semua Kota</button>
                    @foreach(collect($malls)->pluck('city')->filter()->unique()->sort() as $city)
                    <button type="button" class="bubble-filter" data-budget-city="{{ $city }}" aria-pressed="false">{{ $city }}</button>
                    @endforeach
                </div>
                <select id="budget-mall" name="mall" required class="w-full glass bg-slate-950/60 p-3 rounded-xl border border-white/10">
                    <option data-i18n="choose_mall" value="">Pilih mall</option>
                    @foreach($malls as $mall)
                    <option value="{{ $mall['id'] }}" data-city="{{ $mall['city'] }}" @selected(request('mall') === $mall['id'])>{{ $mall['name'] }} — {{ $mall['city'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label data-i18n="field_people" for="budget-people" class="block text-sm mb-2">Jumlah orang</label>
                <input id="budget-people" type="number" name="people" min="1" max="20" value="{{ request('people', 1) }}" required class="w-full glass bg-transparent p-3 rounded-xl border border-white/10">
            </div>
            <div class="md:col-span-3">
                <label data-i18n="budget_fallback_label" for="budget-level" class="block text-sm mb-2">Price level fallback jika data toko belum memiliki level harga</label>
                <select id="budget-level" name="price_level" class="glass bg-slate-950/60 p-3 rounded-xl border border-white/10">
                    <option data-i18n="budget_level_1" value="1">Level 1 — hemat (Rp30.000/orang)</option>
                    <option data-i18n="budget_level_2" value="2" selected>Level 2 — menengah (Rp75.000/orang)</option>
                    <option data-i18n="budget_level_3" value="3">Level 3 — premium (Rp150.000/orang)</option>
                    <option data-i18n="budget_level_4" value="4">Level 4 — tinggi (Rp300.000/orang)</option>
                </select>
            </div>
        </div>
        <button data-i18n="estimate_button" class="mt-6 bg-gradient-to-r from-neon-cyan to-neon-purple text-black px-7 py-3 rounded-full font-semibold">Hitung estimasi</button>
    </form>

    @if($estimate)
    <section class="glass rounded-3xl p-8 mt-8" aria-live="polite">
        <h2 data-i18n="budget_result_title" class="font-display text-2xl font-bold mb-2">Hasil estimasi</h2>
        @if($estimate['hasPriceData'])
            <p class="text-gray-400 mb-5"><span data-i18n="budget_based_on_rdf">Berdasarkan price level RDF dari</span> {{ $estimate['pricedStores'] }} <span data-i18n="budget_stores_in_mall">toko di mall ini. Ini perkiraan, bukan harga resmi.</span></p>
        @else
            <p data-i18n="budget_no_price_data" class="text-amber-200 mb-5">Data price level belum tersedia untuk mall ini; hasil menggunakan level fallback yang dipilih.</p>
        @endif
        <p class="text-gray-300"><span data-i18n="budget_average_level">Rata-rata level:</span> {{ number_format($estimate['priceLevel'], 1) }} / 4</p>
        <p class="font-display text-3xl gradient-text mt-2">Rp {{ number_format($estimate['total'], 0, ',', '.') }}</p>
        <p class="text-sm text-gray-400 mt-1"><span data-i18n="budget_for">Untuk</span> {{ $estimate['people'] }} <span data-i18n="people">orang</span> (Rp {{ number_format($estimate['perPerson'], 0, ',', '.') }} <span data-i18n="per_person">per orang</span>)</p>
        <p data-i18n="budget_config_note" class="text-xs text-gray-500 mt-4">Angka level adalah estimasi yang dapat disesuaikan melalui BUDGET_PRICE_LEVEL_1..4 di .env.</p>
    </section>
    @endif
</div>
@endsection

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const select = document.getElementById('budget-mall');
        const cityButtons = document.querySelectorAll('[data-budget-city]');
        const normalize = value => value.trim().toLocaleLowerCase('id');

        cityButtons.forEach(button => button.addEventListener('click', () => {
            const city = button.dataset.budgetCity;
            Array.from(select.options).forEach(option => {
                option.hidden = Boolean(city) && option.value !== ''
                    && normalize(option.dataset.city || '') !== normalize(city);
            });
            if (select.selectedOptions[0]?.hidden) select.value = '';
            cityButtons.forEach(item => {
                const active = item === button;
                item.classList.toggle('bubble-filter--active', active);
                item.setAttribute('aria-pressed', String(active));
            });
        }));
    });
    </script>
    @endpush
