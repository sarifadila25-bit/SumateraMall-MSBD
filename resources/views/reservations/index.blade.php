
@extends('layouts.app')

@section('title', 'Reservasi Restoran')

@section('content')

<style>
    .reservation-page {
        width: min(100% - 32px, 1120px);
        margin: 0 auto;
        padding: 38px 0 65px;
        color: #fff7e5;
    }

    .reservation-heading {
        text-align: center;
        margin-bottom: 32px;
    }

    .reservation-heading .eyebrow {
        color: #d6ad4f;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 2px;
        margin-bottom: 9px;
    }

    .reservation-heading h1 {
        color: #f2d27e;
        font-size: clamp(27px, 4vw, 36px);
        font-weight: 800;
        margin-bottom: 8px;
    }

    .reservation-heading p {
        color: #bdb29a;
        font-size: 14px;
    }

    .reservation-search {
        display: flex;
        gap: 10px;
        max-width: 650px;
        margin: 0 auto 35px;
    }

    .reservation-search input {
        flex: 1;
        min-width: 0;
        padding: 13px 16px;
        border-radius: 12px;
        border: 1px solid rgba(226,190,94,.3);
        background: rgba(255,255,255,.06);
        color: inherit;
        outline: none;
    }

    .reservation-search input:focus {
        border-color: #d6ad4f;
    }

    .reservation-search button,
    .reservation-submit {
        border: 0;
        border-radius: 12px;
        padding: 12px 21px;
        background: linear-gradient(135deg, #f2d27e, #bd8f2f);
        color: #241a09;
        font-weight: 800;
        cursor: pointer;
        transition: .2s;
    }

    .reservation-search button:hover,
    .reservation-submit:hover {
        filter: brightness(1.08);
        transform: translateY(-1px);
    }

    .reservation-section-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px;
    }

    .reservation-section-title h2 {
        color: #f2d27e;
        font-size: 19px;
        font-weight: 800;
    }

    .reservation-count {
        color: #d6ad4f;
        font-size: 12px;
        border: 1px solid rgba(226,190,94,.3);
        background: rgba(226,190,94,.08);
        padding: 6px 12px;
        border-radius: 30px;
    }

    .restaurant-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
        align-items: start;
    }

    .restaurant-card {
        overflow: hidden;
        border: 1px solid rgba(226,190,94,.25);
        border-radius: 17px;
        background: rgba(255,255,255,.045);
        box-shadow: 0 8px 24px rgba(0,0,0,.12);
        transition: .2s;
        min-width: 0;
    }

    .restaurant-card:hover {
        border-color: rgba(226,190,94,.6);
        transform: translateY(-3px);
    }

    .restaurant-card-content {
        padding: 21px;
    }

    .restaurant-top {
        display: flex;
        gap: 14px;
        align-items: center;
        margin-bottom: 19px;
    }

    .restaurant-icon {
        width: 55px;
        height: 55px;
        flex-shrink: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        border-radius: 15px;
        background: rgba(226,190,94,.12);
        color: #f2d27e;
        font-size: 25px;
    }

    .restaurant-name {
        color: #fff7e5;
        font-size: 18px;
        font-weight: 800;
        margin-bottom: 5px;
    }

    .restaurant-mall {
        color: #bdb29a;
        font-size: 13px;
    }

    .restaurant-category {
        color: #c5baa2;
        font-size: 12px;
        padding: 9px 11px;
        background: rgba(255,255,255,.045);
        border-radius: 9px;
        margin-bottom: 17px;
    }

    .restaurant-hours {
        color: #c5baa2;
        font-size: 12px;
        margin-bottom: 15px;
    }

    .restaurant-action {
        display: block;
        width: 100%;
        padding: 11px;
        text-align: center;
        border-radius: 11px;
        background: linear-gradient(135deg, #f2d27e, #bd8f2f);
        color: #241a09;
        font-size: 13px;
        font-weight: 800;
        text-decoration: none;
        cursor: pointer;
    }

    .restaurant-action:hover {
        color: #241a09;
        filter: brightness(1.08);
    }

    .reservation-form {
        border-top: 1px solid rgba(226,190,94,.25);
        margin-top: 18px;
        padding-top: 18px;
    }

    .reservation-form h4 {
        color: #f2d27e;
        font-size: 16px;
        font-weight: 800;
        margin-bottom: 16px;
    }

    .reservation-field {
        margin-bottom: 13px;
    }

    .reservation-field label {
        display: block;
        color: #e8ddc7;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 6px;
    }

    .reservation-field input,
    .reservation-field textarea {
        width: 100%;
        border: 1px solid rgba(226,190,94,.25);
        border-radius: 9px;
        background: rgba(255,255,255,.07);
        color: #fff7e5;
        padding: 10px 12px;
        outline: none;
        font-size: 13px;
    }

    .reservation-field input:focus,
    .reservation-field textarea:focus {
        border-color: #d6ad4f;
    }

    .reservation-field input::placeholder,
    .reservation-field textarea::placeholder {
        color: #a99d83;
    }

    .reservation-field input[type="datetime-local"] {
        color-scheme: dark;
    }

    .reservation-submit {
        width: 100%;
        margin-top: 5px;
        padding: 13px;
        font-size: 14px;
    }

    .reservation-note {
        color: #bdb29a;
        font-size: 11px;
        text-align: center;
        margin-top: 10px;
    }

    .reservation-error {
        color: #ff9292;
        font-size: 11px;
        margin-top: 5px;
    }

    .reservation-empty {
        padding: 45px 20px;
        text-align: center;
        border: 1px solid rgba(226,190,94,.25);
        border-radius: 16px;
        color: #bdb29a;
    }

    .restaurant-card details summary {
        list-style: none;
    }

    .restaurant-card details summary::-webkit-details-marker {
        display: none;
    }

    @media(max-width: 700px) {
        .restaurant-grid {
            grid-template-columns: 1fr;
        }

        .reservation-search {
            flex-direction: column;
        }

        .reservation-page {
            width: min(100% - 24px, 1120px);
        }
    }

    /* LIGHT MODE */
    html[data-theme="light"] .reservation-page {
        color: #382b17;
    }

    html[data-theme="light"] .reservation-heading h1,
    html[data-theme="light"] .reservation-section-title h2 {
        color: #8b641c;
    }

    html[data-theme="light"] .reservation-heading p {
        color: #6b5b3e;
    }

    html[data-theme="light"] .reservation-search input {
        background: #fffdf7;
        color: #382b17;
        border-color: #e4d5b5;
    }

    html[data-theme="light"] .restaurant-card {
        background: #fffdf7;
        border-color: #e4d5b5;
        box-shadow: 0 8px 24px rgba(91,67,25,.08);
    }

    html[data-theme="light"] .restaurant-icon {
        background: #f8f0dc;
        color: #8b641c;
    }

    html[data-theme="light"] .restaurant-name {
        color: #382b17;
    }

    html[data-theme="light"] .restaurant-mall,
    html[data-theme="light"] .restaurant-hours {
        color: #6b5b3e;
    }

    html[data-theme="light"] .restaurant-category {
        background: #f8f0dc;
        color: #6b5b3e;
    }

    html[data-theme="light"] .reservation-form {
        border-color: #e4d5b5;
    }

    html[data-theme="light"] .reservation-form h4 {
        color: #8b641c;
    }

    html[data-theme="light"] .reservation-field label {
        color: #49391f;
    }

    html[data-theme="light"] .reservation-field input,
    html[data-theme="light"] .reservation-field textarea {
        background: #fff;
        color: #382b17;
        border-color: #dfd1b6;
    }

    html[data-theme="light"] .reservation-field input::placeholder,
    html[data-theme="light"] .reservation-field textarea::placeholder {
        color: #96866b;
    }

    html[data-theme="light"] .reservation-field input[type="datetime-local"] {
        color-scheme: light;
    }

    html[data-theme="light"] .reservation-note {
        color: #79684b;
    }

    html[data-theme="light"] .reservation-error {
        color: #bd3030;
    }

    html[data-theme="light"] .reservation-empty {
        background: #fffdf7;
        border-color: #e4d5b5;
        color: #6b5b3e;
    }
</style>

<div class="reservation-page">

@if ($errors->any())
    <div style="max-width:700px;margin:0 auto 24px;padding:16px 20px;
                border:1px solid #dc7777;border-radius:12px;
                background:rgba(220,80,80,.10);color:inherit;">
        <strong>Reservasi belum berhasil dikirim.</strong>
        <p style="margin-top:6px;font-size:13px;">
            Ada data yang perlu diperbaiki:
        </p>
        <ul style="padding-left:20px;margin-top:8px;font-size:13px;list-style:disc;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

    <header class="reservation-heading">
        <div class="eyebrow">SUMATERAMALL EXPERIENCE</div>
        <h1>Reservasi Restoran</h1>
        <p>Pilih restoran favoritmu dan pesan tempat sebelum berkunjung.</p>
    </header>

    <form action="{{ route('reservations.index') }}" method="GET"
          class="reservation-search">
        <input type="text" name="search"
               value="{{ request('search') }}"
               placeholder="Cari nama restoran...">
        <button type="submit">
            <i class="bi bi-search me-1"></i> Cari
        </button>
    </form>

    <div class="reservation-section-title">
        <h2><i class="bi bi-shop me-2"></i>Pilihan Restoran</h2>
        <span class="reservation-count">{{ $restaurants->total() }} Restoran</span>
    </div>

    @if ($restaurants->isEmpty())

        <div class="reservation-empty">
            <i class="bi bi-search" style="font-size:35px;"></i>
            <h3 class="mt-3 font-bold">Restoran tidak ditemukan</h3>
            <p class="mt-2">Coba gunakan kata kunci pencarian lain.</p>
        </div>

    @else

        <div class="restaurant-grid">

            @foreach ($restaurants as $restaurant)

                <article class="restaurant-card">
                    <div class="restaurant-card-content">

                        <div class="restaurant-top">
                            <div class="restaurant-icon">
                                <i class="bi bi-cup-hot-fill"></i>
                            </div>

                            <div>
                                <h3 class="restaurant-name">
                                    {{ $restaurant->name }}
                                </h3>
                                <p class="restaurant-mall">
                                    <i class="bi bi-geo-alt me-1"></i>
                                    {{ $restaurant->mall?->name ?? 'Mall tidak diketahui' }}
                                </p>
                            </div>
                        </div>

                        <div class="restaurant-category">
                            <i class="bi bi-tags me-2"></i>
                            {{ $restaurant->category_text }}
                        </div>

                        @if ($restaurant->opening_hours)
                            <p class="restaurant-hours">
                                <i class="bi bi-clock me-2"></i>
                                {{ $restaurant->opening_hours }}
                            </p>
                        @endif

                        @auth
                            <details id="reservation-{{ $restaurant->id }}">
                                <summary class="restaurant-action">
                                    <i class="bi bi-calendar-check me-1"></i>
                                    Reservasi Sekarang
                                </summary>

                                <div class="reservation-form">
                                    <h4>Detail Reservasi</h4>

                                    <form action="{{ route('reservations.store', $restaurant->id) }}"
                                          method="POST">
                                        @csrf

                                        <div class="reservation-field">
                                            <label>Nama Pemesan</label>
                                            <input type="text" name="customer_name"
                                                   value="{{ old('customer_name', auth()->user()->name) }}"
                                                   maxlength="255" required>
                                            @error('customer_name')
                                                <p class="reservation-error">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="reservation-field">
                                            <label>Nomor HP</label>
                                            <input type="tel" name="customer_phone"
                                                   value="{{ old('customer_phone') }}"
                                                   placeholder="081234567890"
                                                   maxlength="30" required>
                                            @error('customer_phone')
                                                <p class="reservation-error">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="reservation-field">
                                            <label>Tanggal dan Jam Reservasi</label>
                                            <input type="datetime-local" name="reservation_at"
                                                   value="{{ old('reservation_at') }}"
                                                   min="{{ now()->format('Y-m-d\TH:i') }}"
                                                   required>
                                            @error('reservation_at')
                                                <p class="reservation-error">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="reservation-field">
                                            <label>Jumlah Orang</label>
                                            <input type="number" name="party_size"
                                                   value="{{ old('party_size', 2) }}"
                                                   min="1" max="50" required>
                                            @error('party_size')
                                                <p class="reservation-error">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="reservation-field">
                                            <label>Catatan (Opsional)</label>
                                            <textarea name="notes" rows="3"
                                                      maxlength="1000"
                                                      placeholder="Contoh: ingin meja dekat jendela...">{{ old('notes') }}</textarea>
                                            @error('notes')
                                                <p class="reservation-error">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <button type="submit" class="reservation-submit">
                                            <i class="bi bi-check-circle me-2"></i>
                                            Kirim Reservasi
                                        </button>

                                        <p class="reservation-note">
                                            Reservasi menunggu konfirmasi dari admin.
                                        </p>
                                    </form>
                                </div>
                            </details>
                        @else
                            <a href="{{ route('login') }}" class="restaurant-action">
                                Login untuk Reservasi
                            </a>
                        @endauth

                    </div>
                </article>

            @endforeach

        </div>

        <div class="mt-8">
            {{ $restaurants->links() }}
        </div>

    @endif

</div>
@endsection
