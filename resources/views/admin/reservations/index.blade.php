
@extends('layouts.app')

@section('title', 'Reservation Management')

@section('content')
<style>
    .admin-reservations {
        max-width: 1250px;
        margin: 0 auto;
        padding: 42px 24px 70px;
        color: #f5eee5;
    }

    .admin-reservations .eyebrow {
        color: #c99b59;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 2px;
        text-transform: uppercase;
    }

    .admin-reservations h1 {
        font-size: clamp(27px, 4vw, 36px);
        font-weight: 800;
        margin-top: 8px;
    }

    .admin-reservations .subtitle {
        color: #aaa099;
        margin-top: 7px;
        font-size: 14px;
    }

    .reservation-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin: 30px 0;
    }

    .reservation-stat {
        background: #211b16;
        border: 1px solid rgba(201,155,89,.2);
        border-radius: 16px;
        padding: 21px;
    }

    .reservation-stat span {
        color: #aaa099;
        font-size: 13px;
    }

    .reservation-stat strong {
        display: block;
        font-size: 29px;
        margin-top: 9px;
    }

    .reservation-table-wrap {
        background: #211b16;
        border: 1px solid rgba(201,155,89,.2);
        border-radius: 17px;
        overflow: hidden;
    }

    .reservation-table-heading {
        padding: 22px 24px;
        border-bottom: 1px solid rgba(255,255,255,.08);
    }

    .reservation-table-heading h2 {
        font-size: 18px;
        font-weight: 750;
    }

    .reservation-table-heading p {
        color: #aaa099;
        font-size: 13px;
        margin-top: 5px;
    }

    .reservation-table {
        width: 100%;
        min-width: 1050px;
        text-align: left;
        border-collapse: collapse;
    }

    .reservation-table th {
        padding: 15px 18px;
        color: #b9aa98;
        background: rgba(255,255,255,.035);
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .7px;
    }

    .reservation-table td {
        padding: 18px;
        border-top: 1px solid rgba(255,255,255,.055);
        font-size: 13px;
        vertical-align: top;
    }

    .reservation-table tbody tr:hover {
        background: rgba(255,255,255,.025);
    }

    .reservation-muted {
        color: #aaa099;
        font-size: 12px;
        margin-top: 4px;
    }

    .reservation-status {
        display: inline-block;
        padding: 6px 10px;
        border-radius: 30px;
        font-size: 11px;
        font-weight: 700;
        text-transform: capitalize;
        white-space: nowrap;
    }

    .status-pending {
        color: #e7b65e;
        background: rgba(231,182,94,.12);
    }

    .status-confirmed {
        color: #70d5a0;
        background: rgba(112,213,160,.12);
    }

    .status-rejected {
        color: #f08a82;
        background: rgba(240,138,130,.12);
    }

    .status-completed {
        color: #a9b8d5;
        background: rgba(169,184,213,.12);
    }

    .reservation-action-form {
        display: grid;
        gap: 8px;
        min-width: 180px;
    }

    .reservation-action-form select,
    .reservation-action-form textarea {
        width: 100%;
        border: 1px solid rgba(201,155,89,.25);
        border-radius: 8px;
        padding: 9px 10px;
        background: #17120e;
        color: #f5eee5;
        font-size: 12px;
    }

    .reservation-action-form textarea {
        min-height: 58px;
        resize: vertical;
    }

    .reservation-save {
        border: 0;
        border-radius: 8px;
        background: #c99b59;
        color: #241a12;
        font-size: 12px;
        font-weight: 800;
        padding: 10px;
        cursor: pointer;
    }

    .reservation-save:hover {
        background: #ddb477;
    }

    .reservation-empty {
        padding: 55px 20px;
        text-align: center;
        color: #aaa099;
    }

    .reservation-alert {
        padding: 14px 18px;
        border-radius: 11px;
        margin-bottom: 20px;
        font-size: 14px;
    }

    @media (max-width: 850px) {
        .reservation-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 480px) {
        .reservation-stats {
            gap: 10px;
        }

        .reservation-stat {
            padding: 15px;
        }
    }

    html[data-theme="light"] .admin-reservations {
        color: #35291f;
    }

    html[data-theme="light"] .reservation-stat,
    html[data-theme="light"] .reservation-table-wrap {
        background: #fff;
        border-color: #eadcc8;
    }

    html[data-theme="light"] .reservation-table th {
        color: #806b53;
        background: #f8f3ec;
    }

    html[data-theme="light"] .reservation-table td {
        border-color: #eee5da;
    }

    html[data-theme="light"] .reservation-action-form select,
    html[data-theme="light"] .reservation-action-form textarea {
        background: #fffaf4;
        color: #35291f;
        border-color: #e3d3bd;
    }
</style>

@php
    $currentPageReservations = $reservations->getCollection();
@endphp

<div class="admin-reservations">
    <div class="mb-8">
        <p class="eyebrow">Admin Dashboard / Management</p>
        <h1>Reservation Management</h1>
        <p class="subtitle">Kelola dan konfirmasi reservasi restoran pengguna SumateraMall.</p>
    </div>

    @if (session('success'))
        <div class="reservation-alert" style="background:rgba(50,160,100,.12);border:1px solid rgba(50,160,100,.25);color:#70d5a0;">
            ✓ {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="reservation-alert" style="background:rgba(220,80,80,.12);border:1px solid rgba(220,80,80,.25);color:#f08a82;">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="reservation-stats">
        <div class="reservation-stat">
            <span>Total Reservasi</span>
            <strong>{{ $reservations->total() }}</strong>
        </div>

        <div class="reservation-stat">
            <span>Menunggu Konfirmasi</span>
            <strong>{{ $currentPageReservations->where('status', 'pending')->count() }}</strong>
        </div>

        <div class="reservation-stat">
            <span>Dikonfirmasi</span>
            <strong>{{ $currentPageReservations->where('status', 'confirmed')->count() }}</strong>
        </div>

        <div class="reservation-stat">
            <span>Ditolak</span>
            <strong>{{ $currentPageReservations->where('status', 'rejected')->count() }}</strong>
        </div>
    </div>

    <div class="reservation-table-wrap">
        <div class="reservation-table-heading">
            <h2>Daftar Reservasi</h2>
            <p>Reservasi dari seluruh pengguna SumateraMall.</p>
        </div>

        @if ($reservations->isEmpty())
            <div class="reservation-empty">
                <div style="font-size:38px;margin-bottom:12px;">♧</div>
                <h3 style="font-size:18px;font-weight:700;color:inherit;">Belum Ada Reservasi</h3>
                <p style="font-size:13px;margin-top:7px;">Reservasi pengguna akan muncul di sini.</p>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="reservation-table">
                    <thead>
                        <tr>
                            <th>Pengguna</th>
                            <th>Restoran</th>
                            <th>Jadwal</th>
                            <th>Tamu</th>
                            <th>Status</th>
                            <th>Kelola</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($reservations as $reservation)
                            <tr>
                                <td>
                                    <strong>{{ $reservation->user?->name ?? 'Pengguna tidak ditemukan' }}</strong>
                                    <p class="reservation-muted">
                                    {{ $reservation->user?->email ?? '-' }}
                                    </p>
                                </td>

                                <td>
                                    <strong>{{ $reservation->store?->name ?? 'Restoran tidak ditemukan' }}</strong>
                                    <p class="reservation-muted">
                                        {{ $reservation->store?->mall?->name ?? '-' }}
                                    </p>
                                </td>

                                <td>
                                    {{ $reservation->reservation_at->format('d M Y') }}
                                    <p class="reservation-muted">
                                        {{ $reservation->reservation_at->format('H:i') }} WIB
                                    </p>
                                </td>

                                <td>{{ $reservation->party_size }} orang</td>

                                <td>
                                    <span class="reservation-status status-{{ $reservation->status }}">
                                        {{ str_replace('_', ' ', $reservation->status) }}
                                    </span>
                                </td>

                                <td>
    @if (in_array($reservation->status, ['pending', 'confirmed']))
        <form
            action="{{ route('admin.reservations.update', $reservation) }}"
            method="POST"
            class="reservation-action-form"
        >
            @csrf
            @method('PATCH')

            <select name="status" required>
                <option value="" disabled selected>Pilih status baru</option>

                @if ($reservation->status === 'pending')
                    <option value="confirmed">Confirmed</option>
                    <option value="rejected">Rejected</option>
                @elseif ($reservation->status === 'confirmed')
                    <option value="completed">Completed</option>
                @endif
            </select>

            <textarea name="admin_notes" placeholder="Catatan untuk pengguna...">{{ $reservation->admin_notes }}</textarea>

            <button type="submit" class="reservation-save">
                Simpan Perubahan
            </button>
        </form>
    @else
        <span style="font-size:13px;opacity:.65;">
            Status sudah final. Tidak dapat diubah.
        </span>
    @endif
</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="padding:20px 24px;border-top:1px solid rgba(255,255,255,.08);">
                {{ $reservations->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
