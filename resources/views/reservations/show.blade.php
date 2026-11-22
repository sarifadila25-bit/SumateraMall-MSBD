@extends('layouts.app')

@section('title', 'Detail Reservasi ' . $reservation->reservation_code)

@section('content')
<div class="max-w-4xl mx-auto px-6">
    <div class="mb-6">
        <a href="{{ route('reservations.index') }}" class="text-sm text-cyan-300 hover:underline">&larr; Kembali ke daftar reservasi</a>
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

    @php
        $badgeStyles = match($reservation->status) {
            'confirmed' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40',
            'rejected'  => 'bg-red-500/20 text-red-300 border-red-500/40',
            'cancelled' => 'bg-gray-500/20 text-gray-300 border-gray-500/40',
            default     => 'bg-amber-500/20 text-amber-200 border-amber-500/40',
        };
    @endphp

    <section class="glass rounded-3xl p-8 mb-8">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-white/10 pb-6 mb-6">
            <div>
                <p class="text-xs uppercase tracking-wider text-gray-400 mb-1">Kode Reservasi</p>
                <h1 class="font-mono text-2xl md:text-3xl font-bold text-cyan-300">{{ $reservation->reservation_code }}</h1>
            </div>
            <span class="px-4 py-1.5 rounded-full text-sm font-semibold border {{ $badgeStyles }}">
                {{ strtoupper($reservation->status) }}
            </span>
        </div>

        <div class="grid md:grid-cols-2 gap-6 text-sm">
            <div>
                <p class="text-gray-400">Tenant / Restoran</p>
                <p class="text-base font-semibold mt-0.5">{{ $reservation->store->name ?? '-' }}</p>
                @if($reservation->store?->mall)
                    <p class="text-gray-400 text-xs mt-0.5">{{ $reservation->store->mall->name }}</p>
                @endif
            </div>
            <div>
                <p class="text-gray-400">Waktu Kunjungan</p>
                <p class="text-base font-semibold mt-0.5">
                    {{ $reservation->reservation_date->format('d M Y') }}, pukul {{ \Illuminate\Support\Str::substr($reservation->reservation_time, 0, 5) }}
                </p>
                <p class="text-gray-400 text-xs mt-0.5">{{ $reservation->guest_count }} orang</p>
            </div>
            <div>
                <p class="text-gray-400">Nama Pemesan</p>
                <p class="text-base font-semibold mt-0.5">{{ $reservation->contact_name }}</p>
            </div>
            <div>
                <p class="text-gray-400">Kontak Telepon</p>
                <p class="text-base font-semibold mt-0.5">{{ $reservation->contact_phone }}</p>
            </div>
            @if($reservation->special_requests)
                <div class="md:col-span-2">
                    <p class="text-gray-400">Catatan Khusus</p>
                    <p class="mt-1 text-gray-200">{{ $reservation->special_requests }}</p>
                </div>
            @endif
            @if($reservation->admin_notes)
                <div class="md:col-span-2">
                    <p class="text-gray-400">Catatan Konfirmasi</p>
                    <p class="mt-1 text-cyan-200">{{ $reservation->admin_notes }}</p>
                </div>
            @endif
            @if($reservation->cancellation_reason)
                <div class="md:col-span-2">
                    <p class="text-gray-400">Alasan Pembatalan</p>
                    <p class="mt-1 text-red-300">{{ $reservation->cancellation_reason }}</p>
                </div>
            @endif
        </div>

        @if($reservation->canBeCancelledByUser())
            <form action="{{ route('reservations.cancel', $reservation->reservation_code) }}" method="POST"
                  class="mt-8 pt-6 border-t border-white/10 flex flex-col md:flex-row gap-3">
                @csrf
                @method('PATCH')
                <input type="text" name="cancellation_reason"
                       placeholder="Alasan pembatalan (opsional)"
                       class="flex-1 glass bg-slate-950/60 px-4 py-2.5 rounded-xl border border-white/10 text-sm">
                <button type="submit"
                        class="bg-red-500/20 hover:bg-red-500/30 text-red-200 border border-red-500/40 px-5 py-2.5 rounded-xl text-sm font-semibold transition">
                    Batalkan Reservasi
                </button>
            </form>
        @endif
    </section>

    <section class="glass rounded-3xl p-8">
        <h2 class="font-display text-2xl font-bold mb-5">Riwayat Perubahan Status</h2>
        <div class="space-y-4">
            @forelse($reservation->statusLogs as $log)
                <div class="border-l-2 border-cyan-400/60 pl-4 py-1">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm font-semibold">
                            @if($log->from_status)
                                <span class="text-gray-400">{{ strtoupper($log->from_status) }}</span> &rarr;
                            @endif
                            <span class="text-cyan-300">{{ strtoupper($log->to_status) }}</span>
                        </p>
                        <span class="text-xs text-gray-400">{{ $log->created_at?->format('d M Y H:i') }}</span>
                    </div>
                    @if($log->note)
                        <p class="text-sm text-gray-300 mt-1">{{ $log->note }}</p>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-400">Belum ada riwayat perubahan status.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection