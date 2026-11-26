@extends('layouts.app')

@section('title', 'Perbandingan Mall')

@section('content')
<section class="mall-compare" aria-labelledby="compare-title">
    <header class="mall-compare__hero">
        <div class="mall-compare__hero-copy">
            <p class="mall-compare__eyebrow">
                <span class="mall-compare__eyebrow-dot"></span>
                Eksplorasi pusat perbelanjaan Sumatera
            </p>
            <h1 id="compare-title" data-i18n="page_compare_title">Perbandingan Mall</h1>
            <p data-i18n="compare_description" class="mall-compare__intro">
                Bandingkan lokasi, cakupan toko, dan kategori dari data RDF.
            </p>
            <div class="mall-compare__summary">
                <span class="mall-compare__summary-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M7 7h13m0 0-3-3m3 3-3 3M17 17H4m0 0 3 3m-3-3 3-3" />
                    </svg>
                </span>
                <span>{{ count($malls) }} mall ditemukan dari data RDF</span>
            </div>
        </div>

        <div class="mall-compare__art" aria-hidden="true">
            <span class="mall-compare__orbit mall-compare__orbit--outer"></span>
            <span class="mall-compare__orbit mall-compare__orbit--inner"></span>
            <span class="mall-compare__art-card mall-compare__art-card--left">
                <span></span><span></span><span></span>
            </span>
            <span class="mall-compare__art-card mall-compare__art-card--right">
                <span></span><span></span><span></span>
            </span>
            <span class="mall-compare__art-mark">VS</span>
        </div>
    </header>

    @if(count($malls) < 2)
        <section class="mall-compare__empty" aria-live="polite">
            <div class="mall-compare__empty-icon" aria-hidden="true">
                <svg viewBox="0 0 48 48" fill="none">
                    <path d="M8 39h32M12 39V20l12-9 12 9v19M19 39V27h10v12M18 20h.01M30 20h.01" />
                    <path d="M36 8v7m-3.5-3.5h7" />
                </svg>
            </div>
            <div class="mall-compare__empty-copy">
                <span class="mall-compare__section-label">DATA PERBANDINGAN</span>
                <h2>Belum ada data yang bisa dibandingkan</h2>
                <p data-i18n="no_comparison_data">
                    Data tidak cukup untuk membandingkan pilihan tersebut. Pilih mall lain yang memiliki data RDF.
                </p>
                <a data-i18n="back_to_malls" href="{{ route('malls.index') }}" class="mall-compare__button">
                    <span>Kembali ke daftar mall</span>
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M5 12h14m-6-6 6 6-6 6" />
                    </svg>
                </a>
            </div>
        </section>
    @else
        <div class="mall-compare__section-heading">
            <div>
                <span class="mall-compare__section-label">RINGKASAN RDF</span>
                <h2>Kenali perbedaannya</h2>
            </div>
            <span class="mall-compare__count">{{ count($malls) }} mall dibandingkan</span>
        </div>

        <section class="mall-compare__cards" aria-label="Hasil perbandingan mall">
            @foreach($malls as $mall)
                @if(!$loop->first)
                    <div class="mall-compare__versus" aria-label="versus">VS</div>
                @endif
                <article class="mall-compare__card">
                    <div class="mall-compare__card-top">
                        <span class="mall-compare__number">0{{ $loop->iteration }}</span>
                        <span class="mall-compare__data-badge">
                            <span></span>
                            Data RDF
                        </span>
                    </div>

                    <div class="mall-compare__mall-icon" aria-hidden="true">
                        <svg viewBox="0 0 32 32" fill="none">
                            <path d="M5 27V12l11-7 11 7v15M3 27h26M12 27v-8h8v8M11 13h.01M21 13h.01" />
                        </svg>
                    </div>

                    <h3 class="mall-compare__mall-name">{{ $mall['name'] }}</h3>
                    <p class="mall-compare__location">
                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M16 8.3c0 4.2-6 9.2-6 9.2S4 12.5 4 8.3a6 6 0 1 1 12 0Z" />
                            <circle cx="10" cy="8" r="2" />
                        </svg>
                        {{ $mall['city'] }}, {{ $mall['province'] }}
                    </p>

                    <div class="mall-compare__stats">
                        <div class="mall-compare__stat">
                            <span class="mall-compare__stat-label">
                                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                    <path d="M3 17V7l7-4 7 4v10M1.5 17h17M7 17v-5h6v5M7 8h.01M13 8h.01" />
                                </svg>
                                <span data-i18n="compare_store_count">Toko dalam RDF</span>
                            </span>
                            <strong>{{ number_format((int) ($mall['storeCount'] ?? 0)) }}</strong>
                        </div>
                        <div class="mall-compare__stat">
                            <span class="mall-compare__stat-label">
                                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                    <rect x="3" y="3" width="5" height="5" rx="1" />
                                    <rect x="12" y="3" width="5" height="5" rx="1" />
                                    <rect x="3" y="12" width="5" height="5" rx="1" />
                                    <rect x="12" y="12" width="5" height="5" rx="1" />
                                </svg>
                                <span data-i18n="compare_category_count">Kategori toko</span>
                            </span>
                            <strong>{{ number_format((int) ($mall['categoryCount'] ?? 0)) }}</strong>
                        </div>
                    </div>

                    <div class="mall-compare__coordinates">
                        <span data-i18n="compare_coordinates">Koordinat</span>
                        <strong>
                            @if(isset($mall['lat'], $mall['lng']))
                                {{ $mall['lat'].', '.$mall['lng'] }}
                            @else
                                <span data-i18n="not_available">Belum tersedia</span>
                            @endif
                        </strong>
                    </div>

                    <a data-i18n="view_details"
                       href="{{ route('malls.show', ['id' => basename($mall['mall'])]) }}"
                       class="mall-compare__details">
                        Lihat detail
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 12h14m-6-6 6 6-6 6" />
                        </svg>
                    </a>
                </article>
            @endforeach
        </section>
    @endif
</section>
@endsection
