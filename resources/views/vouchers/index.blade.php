
@extends('layouts.app')

@section('title', 'Voucher & Promo - SumateraMall')

@section('content')



<style>
    .voucher-page {
        width: min(100% - 32px, 1152px);
        margin: 0 auto;
        color: #fff7e5;
    }

    .voucher-heading {
        text-align: center;
        margin: 0 auto 34px;
    }

    .voucher-heading h1 {
        font-size: clamp(26px, 4vw, 34px);
        font-weight: 800;
        color: #f2d27e;
        margin-bottom: 8px;
    }

    .voucher-heading p {
        color: #bdb29a;
        font-size: 14px;
    }

    .voucher-card {
        height: 100%;
        overflow: hidden;
        border: 1px solid rgba(226, 190, 94, .18);
        border-radius: 18px;
        background: rgba(255, 255, 255, .045);
        backdrop-filter: blur(14px);
        transition: transform .25s ease, border-color .25s ease, box-shadow .25s ease;
    }

    .voucher-card:hover {
        transform: translateY(-4px);
        border-color: rgba(226, 190, 94, .5);
        box-shadow: 0 12px 35px rgba(0, 0, 0, .2);
    }

    .voucher-card-header {
        padding: 20px;
        border-bottom: 1px dashed rgba(226, 190, 94, .3);
        background: rgba(226, 190, 94, .055);
    }

    .voucher-label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .7px;
        color: #f2d27e;
        border: 1px solid rgba(226, 190, 94, .3);
        background: rgba(226, 190, 94, .09);
        padding: 6px 11px;
        border-radius: 30px;
        margin-bottom: 13px;
    }

    .voucher-name {
        color: #fff7e5;
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .voucher-discount {
        color: #f2d27e;
        font-size: 27px;
        font-weight: 800;
        margin: 0;
    }

    .voucher-card-body {
        padding: 20px;
    }

    .voucher-description {
        color: #bdb29a;
        font-size: 13px;
        line-height: 1.6;
        min-height: 40px;
    }

    .voucher-code {
        text-align: center;
        border: 1px dashed rgba(226, 190, 94, .38);
        background: rgba(226, 190, 94, .06);
        color: #f2d27e;
        border-radius: 10px;
        padding: 11px;
        font-weight: 700;
        letter-spacing: 1.5px;
        margin: 16px 0;
    }

    .voucher-price-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 14px;
        margin-bottom: 14px;
        border-bottom: 1px solid rgba(255,255,255,.09);
    }

    .voucher-price {
        color: #f2d27e;
        font-weight: 800;
        font-size: 18px;
    }

    .voucher-info {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        color: #c5baa2;
        font-size: 12px;
        margin-bottom: 11px;
    }

    .voucher-info i {
        color: #d6ad4f;
        width: 14px;
    }

    .voucher-action {
        margin-top: 20px;
    }

    .voucher-action .btn {
        width: 100%;
        padding: 11px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
    }

    .btn-voucher {
        background: linear-gradient(135deg, #f2d27e, #bd8f2f);
        border: none;
        color: #241a09;
    }

    .btn-voucher:hover {
        background: linear-gradient(135deg, #ffe49a, #d6ad4f);
        color: #241a09;
    }

    .btn-claimed {
        background: rgba(255,255,255,.08);
        color: #c5baa2;
        border: 1px solid rgba(255,255,255,.12);
    }

    .voucher-empty {
        text-align: center;
        padding: 55px 20px;
        border: 1px solid rgba(226,190,94,.18);
        border-radius: 18px;
        background: rgba(255,255,255,.04);
    }

    .voucher-empty i {
        color: #d6ad4f;
        font-size: 42px;
    }

    .voucher-empty h5 {
        color: #f2d27e;
        margin-top: 15px;
        font-weight: 700;
    }

    .voucher-alert {
        max-width: 760px;
        margin: 0 auto 22px;
        border-radius: 12px;
    }

    .voucher-count {
        color: #d6ad4f;
        font-size: 12px;
        border: 1px solid rgba(226,190,94,.25);
        border-radius: 30px;
        padding: 7px 13px;
        white-space: nowrap;
    }

    .voucher-pagination {
        display: flex;
        justify-content: center;
        margin-top: 30px;
    }

    
    @media (max-width: 576px) {
        .voucher-page {
            width: min(100% - 24px, 1152px);
        }

        .voucher-heading {
            margin-bottom: 25px;
        }
    }

    /* LIGHT MODE */
    html[data-theme="light"] .voucher-page {
        color: #382b17;
    }

    html[data-theme="light"] .voucher-heading h1 {
        color: #8b641c;
    }

    html[data-theme="light"] .voucher-heading p {
        color: #6b5b3e;
    }

    html[data-theme="light"] .voucher-card {
        background: #fffdf7;
        border: 1px solid #e4d5b5;
        box-shadow: 0 8px 24px rgba(91, 67, 25, .08);
    }

    html[data-theme="light"] .voucher-card-header {
        background: #f8f0dc;
        border-color: #e4d5b5;
    }

    html[data-theme="light"] .voucher-name,
    html[data-theme="light"] .voucher-card-body,
    html[data-theme="light"] .voucher-card-body h5 {
        color: #382b17;
    }

    html[data-theme="light"] .voucher-description,
    html[data-theme="light"] .voucher-info,
    html[data-theme="light"] .voucher-card-body p[style],
    html[data-theme="light"] .voucher-card-body span[style] {
        color: #6b5b3e !important;
    }

    html[data-theme="light"] .voucher-discount,
    html[data-theme="light"] .voucher-price,
    html[data-theme="light"] .voucher-label,
    html[data-theme="light"] .voucher-count,
    html[data-theme="light"] .voucher-info i {
        color: #8b641c;
    }

    html[data-theme="light"] .voucher-label,
    html[data-theme="light"] .voucher-code,
    html[data-theme="light"] .voucher-count {
        background: #fbf4e4;
        border-color: #dfc58d;
    }

    html[data-theme="light"] .voucher-code {
        color: #805b17;
    }

    html[data-theme="light"] .voucher-price-row {
        border-color: #e8dfcc;
    }

    html[data-theme="light"] .voucher-empty {
        background: #fffdf7;
        border-color: #e4d5b5;
    }

    html[data-theme="light"] .voucher-empty h5 {
        color: #8b641c;
    }

    html[data-theme="light"] .voucher-empty p {
        color: #6b5b3e !important;
    }

    html[data-theme="light"] .btn-claimed {
        background: #eee8d9;
        color: #655638;
        border-color: #d8c9a5;
    }

    html[data-theme="light"] .btn-voucher {
        color: #241a09;
    }
</style>


<div class="voucher-page">

    <div class="voucher-heading">
        <h1><i class="bi bi-ticket-perforated me-2"></i>Voucher & Promo</h1>
        <p>Temukan promo menarik dan nikmati belanja lebih hemat di SumateraMall.</p>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show voucher-alert" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show voucher-alert" role="alert">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-1" style="color:#fff7e5;">Promo Pilihan</h5>
            <p class="mb-0 small" style="color:#a99d83;">Jangan sampai kehabisan promo favoritmu.</p>
        </div>
        <span class="voucher-count">{{ $vouchers->total() }} Voucher</span>
    </div>

    @if ($vouchers->isEmpty())
        <div class="voucher-empty">
            <i class="bi bi-ticket-perforated"></i>
            <h5>Belum Ada Voucher</h5>
            <p class="mb-0" style="color:#bdb29a;">Voucher promo akan muncul di sini jika tersedia.</p>
        </div>
    @else
        <div class="row g-4">
            @foreach ($vouchers as $voucher)
                <div class="col-md-6 col-lg-4">
                    <div class="voucher-card">

                        <div class="voucher-card-header">
                            <span class="voucher-label">
                                @if ($voucher->price == 0)
                                    <i class="bi bi-gift"></i> GRATIS
                                @else
                                    <i class="bi bi-star"></i> PREMIUM
                                @endif
                            </span>

                            <h5 class="voucher-name">{{ $voucher->name }}</h5>

                            <p class="voucher-discount">
                                @if ($voucher->discount_type === 'percentage')
                                    {{ rtrim(rtrim(number_format($voucher->discount_value, 2, '.', ''), '0'), '.') }}% OFF
                                @else
                                    Rp{{ number_format($voucher->discount_value, 0, ',', '.') }} OFF
                                @endif
                            </p>
                        </div>

                        <div class="voucher-card-body">

                            <p class="voucher-description">
                                {{ $voucher->description ?? 'Nikmati promo menarik dari SumateraMall.' }}
                            </p>

                            <div class="voucher-code">
                                <i class="bi bi-ticket-perforated me-1"></i>
                                {{ $voucher->code }}
                            </div>

                            <div class="voucher-price-row">
                                <span class="small" style="color:#bdb29a;">Harga voucher</span>
                                <span class="voucher-price">
                                    @if ($voucher->price == 0)
                                        Gratis
                                    @else
                                        Rp{{ number_format($voucher->price, 0, ',', '.') }}
                                    @endif
                                </span>
                            </div>

                            <div class="voucher-info">
                                <i class="bi bi-geo-alt-fill"></i>
                                <span>
                                    @if ($voucher->mall)
                                        {{ $voucher->mall->name }}
                                    @elseif ($voucher->store)
                                        {{ $voucher->store->name }}
                                    @else
                                        Berlaku umum
                                    @endif
                                </span>
                            </div>

                            @if ($voucher->minimum_purchase > 0)
                                <div class="voucher-info">
                                    <i class="bi bi-cart-check-fill"></i>
                                    <span>Min. belanja Rp{{ number_format($voucher->minimum_purchase, 0, ',', '.') }}</span>
                                </div>
                            @endif

                            <div class="voucher-info">
                                <i class="bi bi-calendar-event"></i>
                                <span>
                                    @if ($voucher->ends_at)
                                        Berlaku sampai {{ $voucher->ends_at->format('d M Y') }}
                                    @else
                                        Tidak ada batas akhir
                                    @endif
                                </span>
                            </div>

                            @if ($voucher->quota !== null)
                                <div class="voucher-info">
                                    <i class="bi bi-people-fill"></i>
                                    <span>Sisa kuota: {{ max(0, $voucher->quota - $voucher->claimed_count) }}</span>
                                </div>
                            @endif

                            
<div class="voucher-action">
    @if (in_array($voucher->id, $claimedVoucherIds))
        <button type="button" class="btn btn-claimed" disabled>
            <i class="bi bi-check-circle-fill me-1"></i>
            Sudah Diklaim
        </button>

    @elseif ($voucher->price == 0)
        @auth
            <form action="{{ route('vouchers.claim', $voucher) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-voucher">
                    <i class="bi bi-ticket-perforated me-1"></i>
                    Klaim Gratis
                </button>
            </form>
        @else
            <a href="{{ route('login') }}" class="btn btn-voucher">
                Login untuk Klaim
            </a>
        @endauth

    @else
        @auth
            <a href="{{ route('vouchers.checkout', $voucher) }}" class="btn btn-voucher">
                <i class="bi bi-cart-check me-1"></i>
                Beli Voucher
            </a>
        @else
            <a href="{{ route('login') }}" class="btn btn-voucher">
                Login untuk Membeli
            </a>
                            @endauth
                        @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="voucher-pagination">
            {{ $vouchers->links() }}
        </div>
    @endif

</div>
@endsection
