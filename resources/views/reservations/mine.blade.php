
@extends('layouts.app')

@section('title', 'Reservasi Saya')

@section('content')
<style>
    .my-reservations {
        max-width: 1050px;
        margin: 0 auto;
        padding: 42px 24px 70px;
        color: var(--text-primary, #f5eee5);
    }

    .my-reservations h1 {
        font-size: 30px;
        font-weight: 800;
        margin-bottom: 8px;
    }

    .reservation-subtitle {
        opacity: .7;
        margin-bottom: 30px;
    }

    .my-reservation-card {
        border: 1px solid rgba(205, 160, 90, .25);
        background: rgba(255,255,255,.035);
        border-radius: 16px;
        padding: 22px;
        margin-bottom: 16px;
    }

    .my-reservation-card h3 {
        font-size: 18px;
        font-weight: 750;
        margin-bottom: 5px;
    }

    .my-reservation-mall {
        opacity: .65;
        font-size: 13px;
        margin-bottom: 16px;
    }

    .reservation-info {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        font-size: 14px;
    }

    .reservation-info span {
        opacity: .65;
        display: block;
        font-size: 12px;
        margin-bottom: 3px;
    }

    .reservation-status {
        display: inline-block;
        margin-top: 18px;
        padding: 6px 12px;
        border-radius: 30px;
        background: rgba(205,160,90,.14);
        color: #d9ad69;
        font-size: 12px;
        font-weight: 700;
        text-transform: capitalize;
    }

    .reservation-cancel {
    display: inline-block;
    margin-top: 16px;
    padding: 9px 15px;
    border: 1px solid rgba(190, 75, 65, .4);
    border-radius: 9px;
    background: rgba(190, 75, 65, .12);
    color: #e58b80;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    }

    .reservation-cancel:hover {
    background: rgba(190, 75, 65, .22);
    }

    .reservation-empty {
        text-align: center;
        padding: 55px 20px;
        border: 1px dashed rgba(205,160,90,.3);
        border-radius: 16px;
    }

    .reservation-empty p {
        opacity: .7;
        margin: 8px 0 20px;
    }

    .reservation-back {
        display: inline-block;
        padding: 11px 18px;
        border-radius: 10px;
        background: #c99b59;
        color: #241a12;
        font-weight: 700;
        text-decoration: none;
    }

    @media (max-width: 600px) {
        .reservation-info {
            grid-template-columns: 1fr;
        }
    }

    html[data-theme="light"] .my-reservations {
        color: #35291f;
    }

    html[data-theme="light"] .my-reservation-card {
        background: #fff;
        border-color: #eadcc8;
    }

    html[data-theme="light"] .reservation-empty {
        background: #fff;
    }
</style>

<div class="my-reservations">
    <h1>Reservasi Saya</h1>
    <p class="reservation-subtitle">
        Pantau status reservasi restoran yang sudah kamu kirim.
    </p>

    @if (session('success'))
        <div style="padding:14px 18px;margin-bottom:20px;border-radius:12px;background:rgba(50,160,100,.12);border:1px solid rgba(50,160,100,.3);">
            {{ session('success') }}
        </div>
    @endif

    @forelse ($reservations as $reservation)
        <div class="my-reservation-card">
            <h3>{{ $reservation->store->name }}</h3>

            <p class="my-reservation-mall">
                {{ $reservation->store->mall->name ?? 'Mall tidak diketahui' }}
            </p>

            <div class="reservation-info">
                <div>
                    <span>Nama pemesan</span>
                    {{ $reservation->customer_name }}
                </div>

                <div>
                    <span>Nomor telepon</span>
                    {{ $reservation->customer_phone }}
                </div>

                <div>
                    <span>Tanggal dan waktu</span>
                    {{ $reservation->reservation_at->format('d M Y, H:i') }}
                </div>

                <div>
                    <span>Jumlah orang</span>
                    {{ $reservation->party_size }} orang
                </div>
            </div>

            @if ($reservation->notes)
                <p style="margin-top:15px;font-size:13px;">
                    <strong>Catatan:</strong> {{ $reservation->notes }}
                </p>
            @endif

            <div class="reservation-status">
                Status: {{ str_replace('_', ' ', $reservation->status) }}
            </div>

            @if ($reservation->admin_notes)
                <p style="margin-top:12px;font-size:13px;">
                    <strong>Catatan admin:</strong> {{ $reservation->admin_notes }}
                </p>
            @endif

            @if (in_array($reservation->status, ['pending', 'confirmed']))
    <form action="{{ route('reservations.cancel', $reservation) }}"
          method="POST"
          onsubmit="return confirm('Yakin ingin membatalkan reservasi ini?')">
        @csrf
        @method('PATCH')

        <button type="submit" class="reservation-cancel">
            Batalkan Reservasi
        </button>
    </form>
@endif

        </div>
    @empty
        <div class="reservation-empty">
            <h3 style="font-size:18px;font-weight:750;">Belum ada reservasi</h3>
            <p>Reservasi restoran yang kamu kirim akan muncul di halaman ini.</p>
            <a href="{{ route('reservations.index') }}" class="reservation-back">
                Cari Restoran
            </a>
        </div>
    @endforelse

    @if ($reservations->hasPages())
        <div style="margin-top:22px;">
            {{ $reservations->links() }}
        </div>
    @endif
</div>
@endsection
