@extends('layouts.app')

@section('title', 'Buat Reservasi Baru')

@section('content')
<div class="max-w-3xl mx-auto px-6">
    <div class="mb-8">
        <a href="{{ route('reservations.index') }}" class="text-sm text-cyan-300 hover:underline">&larr; Kembali ke daftar reservasi</a>
        <h1 class="font-display text-4xl md:text-5xl font-bold mt-2 mb-2">Form Reservasi</h1>
        <p class="text-gray-400">Isi detail kunjunganmu ke tenant atau restoran pilihan di SumateraMall.</p>
    </div>

    @if($errors->any())
        <div class="glass rounded-2xl p-4 mb-6 border border-red-500/50 text-red-300">
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('reservations.store') }}" method="POST" class="glass rounded-3xl p-8 space-y-5">
        @csrf

        <div>
            <label for="store_id" class="block text-sm mb-2">Pilih Tenant / Restoran <span class="text-red-400">*</span></label>
            <select id="store_id" name="store_id" required
                    class="w-full glass bg-slate-950/60 p-3 rounded-xl border border-white/10">
                <option value="">-- Pilih toko atau restoran --</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" @selected(old('store_id', $selectedStoreId) == $store->id)>
                        {{ $store->name }}{{ $store->mall ? ' — ' . $store->mall->name : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="grid md:grid-cols-3 gap-4">
            <div>
                <label for="reservation_date" class="block text-sm mb-2">Tanggal Kunjungan <span class="text-red-400">*</span></label>
                <input id="reservation_date" type="date" name="reservation_date"
                       min="{{ now()->toDateString() }}"
                       value="{{ old('reservation_date', now()->toDateString()) }}" required
                       class="w-full glass bg-slate-950/60 p-3 rounded-xl border border-white/10">
            </div>
            <div>
                <label for="reservation_time" class="block text-sm mb-2">Jam <span class="text-red-400">*</span></label>
                <input id="reservation_time" type="time" name="reservation_time"
                       value="{{ old('reservation_time', '12:00') }}" required
                       class="w-full glass bg-slate-950/60 p-3 rounded-xl border border-white/10">
            </div>
            <div>
                <label for="guest_count" class="block text-sm mb-2">Jumlah Tamu <span class="text-red-400">*</span></label>
                <input id="guest_count" type="number" name="guest_count" min="1" max="50"
                       value="{{ old('guest_count', 2) }}" required
                       class="w-full glass bg-slate-950/60 p-3 rounded-xl border border-white/10">
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label for="contact_name" class="block text-sm mb-2">Nama Pemesan <span class="text-red-400">*</span></label>
                <input id="contact_name" type="text" name="contact_name"
                       value="{{ old('contact_name', auth()->user()?->name) }}"
                       placeholder="Nama lengkap" required
                       class="w-full glass bg-slate-950/60 p-3 rounded-xl border border-white/10">
            </div>
            <div>
                <label for="contact_phone" class="block text-sm mb-2">No. WhatsApp / Telepon <span class="text-red-400">*</span></label>
                <input id="contact_phone" type="text" name="contact_phone"
                       value="{{ old('contact_phone') }}"
                       placeholder="08xxxxxxxxxx" required
                       class="w-full glass bg-slate-950/60 p-3 rounded-xl border border-white/10">
            </div>
        </div>

        <div>
            <label for="contact_email" class="block text-sm mb-2">Email (Opsional)</label>
            <input id="contact_email" type="email" name="contact_email"
                   value="{{ old('contact_email', auth()->user()?->email) }}"
                   placeholder="nama@email.com"
                   class="w-full glass bg-slate-950/60 p-3 rounded-xl border border-white/10">
        </div>

        <div>
            <label for="special_requests" class="block text-sm mb-2">Catatan Khusus (Opsional)</label>
            <textarea id="special_requests" name="special_requests" rows="3"
                      placeholder="Contoh: Meja dekat jendela, kursi bayi, area non-smoking..."
                      class="w-full glass bg-slate-950/60 p-3 rounded-xl border border-white/10">{{ old('special_requests') }}</textarea>
        </div>

        <div class="pt-2">
            <button type="submit"
                    class="bg-gradient-to-r from-neon-cyan to-neon-purple text-black px-8 py-3 rounded-full font-semibold">
                Kirim Pengajuan Reservasi
            </button>
        </div>
    </form>
</div>
@endsection