@extends('layouts.app')

@section('title', 'Daftar & Lacak Reservasi')

@section('content')
<div class="max-w-5xl mx-auto px-6">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
        <div>
            <h1 class="font-display text-4xl md:text-5xl font-bold mb-2">Reservasi Tenant & Restoran</h1>
            <p class="text-gray-400">Kelola pengajuan reservasi tempat di mall-mall Sumatera dan pantau status konfirmasinya.</p>
        </div>
        <a href="{{ route('reservations.create') }}"
           class="inline-flex items-center justify-center bg-gradient-to-r from-neon-cyan to-neon-purple text-black px-6 py-3 rounded-full font-semibold shrink-0">
            + Buat Reservasi Baru
        </a>
    </div>

    @if(session('success'))
        <div class="glass rounded-2xl p-4 mb-6 border border-emerald-500/40 text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="glass rounded-2xl p-4 mb-6 border border-red-500/40 text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('reservations.index') }}" method="GET" class="glass rounded-3xl p-6 mb-8">
        <div class="grid md:grid-cols-3 gap-4 items-end">
            <div>
                <label for="filter-code" class="block text-sm text-gray-300 mb-2">Kode Reservasi</label>
                <input id="filter-code" type="text" name="code" value="{{ request('code') }}"
                       placeholder="Contoh: RSV-20261123-XXXXX"
                       class="w-full glass bg-slate-950/60 p-3 rounded-xl border border-white/10">
            </div>
            <div>
                <label for="filter-status" class="block text-sm text-gray-300 mb-2">Status</label>
                <select id="filter-status" name="status"
                        class="w-full glass bg-slate-950/60 p-3 rounded-xl border border-white/10">
                    <option value="">Semua Status</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending (Menunggu)</option>
                    <option value="confirmed" @selected(request('status') === 'confirmed')>Confirmed (Dikonfirmasi)</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>Rejected (Ditolak)</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled (Dibatalkan)</option>
                </select>
            </div>
            <div class="flex gap-3">
                <button type="submit"
                        class="flex-1 bg-white/15 hover:bg-white/25 text-white px-5 py-3 rounded-xl font-semibold transition">
                    Filter
                </button>
                @if(request()->hasAny(['code', 'status']))
                    <a href="{{ route('reservations.index') }}"
                       class="glass px-4 py-3 rounded-xl text-sm text-gray-300 flex items-center">
                        Reset
                    </a>
                @endif
            </div>
        </div>
    </form>

    @if($reservations->isEmpty())
        <div class="glass rounded-3xl p-12 text-center">
            <p class="font-display text-2xl font-bold mb-2">Belum ada data reservasi</p>
            <p class="text-gray-400 mb-6">Ajukan reservasi meja atau kunjungan toko di mall pilihanmu sekarang.</p>
            <a href="{{ route('reservations.create') }}"
               class="inline-block bg-gradient-to-r from-neon-cyan to-neon-purple text-black px-7 py-3 rounded-full font-semibold">
                Buat Reservasi Pertama
            </a>
        </div>
    @else
        <div class="space-y-4">
            @foreach($reservations as $item)
                @php
                    $badgeStyles = match($item->status) {
                        'confirmed' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40',
                        'rejected'  => 'bg-red-500/20 text-red-300 border-red-500/40',
                        'cancelled' => 'bg-gray-500/20 text-gray-300 border-gray-500/40',
                        default     => 'bg-amber-500/20 text-amber-200 border-amber-500/40',
                    };
                @endphp
                <article class="glass rounded-3xl p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-3 mb-2">
                            <span class="font-mono text-sm font-semibold text-cyan-300">{{ $item->reservation_code }}</span>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold border {{ $badgeStyles }}">
                                {{ strtoupper($item->status) }}
                            </span>
                        </div>
                        <h2 class="font-display text-xl font-bold">
                            {{ $item->store->name ?? 'Tenant' }}
                            @if($item->store?->mall)
                                <span class="text-sm font-normal text-gray-400">— {{ $item->store->mall->name }}</span>
                            @endif
                        </h2>
                        <p class="text-sm text-gray-300 mt-1">
                            Tanggal: <strong>{{ $item->reservation_date->format('d M Y') }}</strong>
                            &bull; Pukul <strong>{{ \Illuminate\Support\Str::substr($item->reservation_time, 0, 5) }}</strong>
                            &bull; {{ $item->guest_count }} orang ({{ $item->contact_name }})
                        </p>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <a href="{{ route('reservations.show', $item->reservation_code) }}"
                           class="glass hover:bg-white/15 px-5 py-2.5 rounded-full text-sm font-semibold transition">
                            Detail & Riwayat
                        </a>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $reservations->links() }}
        </div>
    @endif
</div>
@endsection