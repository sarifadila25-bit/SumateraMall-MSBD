@extends('layouts.app')

@section('title', 'Perbandingan Mall')

@section('content')
<div class="max-w-7xl mx-auto px-6">
    <div class="mb-8">
        <h1 data-i18n="page_compare_title" class="font-display text-5xl font-bold mb-3">Perbandingan Mall</h1>
        <p data-i18n="compare_description" class="text-gray-400">Bandingkan lokasi, cakupan toko, dan kategori dari data RDF.</p>
    </div>
    @if(count($malls) < 2)
    <div class="glass rounded-2xl p-8">
        <p data-i18n="no_comparison_data" class="text-gray-300">Data tidak cukup untuk membandingkan pilihan tersebut. Pilih mall lain yang memiliki data RDF.</p>
        <a data-i18n="back_to_malls" href="{{ route('malls.index') }}" class="inline-block mt-4 text-neon-cyan">Kembali ke daftar mall</a>
    </div>
    @else
    <div class="grid md:grid-cols-3 gap-6">
        @foreach($malls as $mall)
        <article class="glass rounded-2xl p-6">
            <h2 class="font-display text-2xl font-bold">{{ $mall['name'] }}</h2>
            <p class="text-gray-400 mt-1">{{ $mall['city'] }}, {{ $mall['province'] }}</p>
            <dl class="mt-6 space-y-4">
                <div class="flex justify-between gap-4 border-b border-white/10 pb-3">
                    <dt data-i18n="compare_store_count" class="text-gray-400">Toko dalam RDF</dt>
                    <dd class="font-semibold">{{ number_format((int) ($mall['storeCount'] ?? 0)) }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-white/10 pb-3">
                    <dt data-i18n="compare_category_count" class="text-gray-400">Kategori toko</dt>
                    <dd class="font-semibold">{{ number_format((int) ($mall['categoryCount'] ?? 0)) }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt data-i18n="compare_coordinates" class="text-gray-400">Koordinat</dt>
                    <dd class="text-right text-sm">@if(isset($mall['lat'], $mall['lng'])){{ $mall['lat'].', '.$mall['lng'] }}@else<span data-i18n="not_available">Belum tersedia</span>@endif</dd>
                </div>
            </dl>
           <a data-i18n="view_details" href="{{ route('malls.show', ['id' => basename($mall['mall'])]) }}" class="inline-block mt-6 text-neon-cyan">Lihat detail</a>
        </article>
        @endforeach
    </div>
    @endif
</div>
@endsection
