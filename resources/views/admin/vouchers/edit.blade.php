
@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-10">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <p class="text-sm text-purple-400 font-semibold tracking-widest uppercase mb-2">
                Admin Dashboard / Voucher
            </p>
            <h1 class="text-3xl md:text-4xl font-bold text-white">
                Edit Voucher
            </h1>
            <p class="text-gray-400 mt-2">
                Perbarui informasi dan pengaturan voucher SumateraMall.
            </p>
        </div>

        <a href="{{ route('admin.vouchers.index') }}"
           class="inline-flex items-center justify-center px-5 py-3 rounded-xl border border-white/10 bg-white/5 text-gray-300 hover:bg-white/10 transition">
            ← Kembali
        </a>
    </div>

    <form action="{{ route('admin.vouchers.update', $voucher) }}"
          method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Informasi Voucher --}}
        <section class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur-xl p-6 md:p-8 shadow-xl">
            <div class="mb-6">
                <h2 class="text-xl font-bold text-white">01. Informasi Voucher</h2>
                <p class="text-sm text-gray-400 mt-1">Identitas dan deskripsi voucher.</p>
            </div>

            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Nama Voucher *</label>
                    <input type="text" name="name"
                           value="{{ old('name', $voucher->name) }}"
                           required
                           class="w-full rounded-xl bg-slate-800 border border-white/10 px-4 py-3 text-white focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 outline-none transition">
                    @error('name') <p class="text-red-400 text-sm mt-2">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Deskripsi</label>
                    <textarea name="description" rows="3"
                              class="w-full rounded-xl bg-slate-800 border border-white/10 px-4 py-3 text-white focus:border-purple-500 outline-none">{{ old('description', $voucher->description) }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Kode Voucher *</label>
                    <input type="text" name="code"
                           value="{{ old('code', $voucher->code) }}"
                           required
                           class="w-full rounded-xl bg-slate-800 border border-white/10 px-4 py-3 text-white uppercase focus:border-purple-500 outline-none">
                    @error('code') <p class="text-red-400 text-sm mt-2">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- Diskon dan Harga --}}
        <section class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur-xl p-6 md:p-8 shadow-xl">
            <div class="mb-6">
                <h2 class="text-xl font-bold text-white">02. Diskon & Harga</h2>
                <p class="text-sm text-gray-400 mt-1">Atur nilai diskon dan harga voucher.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Jenis Diskon *</label>
                    <select name="discount_type" required
                            class="w-full rounded-xl bg-slate-800 border border-white/10 px-4 py-3 text-white focus:border-purple-500 outline-none">
                        <option value="percentage" @selected(old('discount_type', $voucher->discount_type) === 'percentage')>Persentase (%)</option>
                        <option value="fixed" @selected(old('discount_type', $voucher->discount_type) === 'fixed')>Potongan Nominal (Rp)</option>
                    </select>
                    @error('discount_type') <p class="text-red-400 text-sm mt-2">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Nilai Diskon *</label>
                    <input type="number" name="discount_value" min="0.01" step="0.01"
                           value="{{ old('discount_value', $voucher->discount_value) }}"
                           required
                           class="w-full rounded-xl bg-slate-800 border border-white/10 px-4 py-3 text-white focus:border-purple-500 outline-none">
                    @error('discount_value') <p class="text-red-400 text-sm mt-2">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Maksimal Diskon (Rp)</label>
                    <input type="number" name="max_discount" min="0" step="0.01"
                           value="{{ old('max_discount', $voucher->max_discount) }}"
                           class="w-full rounded-xl bg-slate-800 border border-white/10 px-4 py-3 text-white focus:border-purple-500 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Minimum Pembelian (Rp)</label>
                    <input type="number" name="minimum_purchase" min="0" step="0.01"
                           value="{{ old('minimum_purchase', $voucher->minimum_purchase) }}"
                           class="w-full rounded-xl bg-slate-800 border border-white/10 px-4 py-3 text-white focus:border-purple-500 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Harga Voucher (Rp) *</label>
                    <input type="number" name="price" min="0" step="0.01"
                           value="{{ old('price', $voucher->price) }}"
                           required
                           class="w-full rounded-xl bg-slate-800 border border-white/10 px-4 py-3 text-white focus:border-purple-500 outline-none">
                    @error('price') <p class="text-red-400 text-sm mt-2">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Kuota Voucher</label>
                    <input type="number" name="quota" min="1"
                           value="{{ old('quota', $voucher->quota) }}"
                           placeholder="Unlimited jika kosong"
                           class="w-full rounded-xl bg-slate-800 border border-white/10 px-4 py-3 text-white focus:border-purple-500 outline-none">
                    <p class="text-xs text-gray-500 mt-2">Kosongkan jika kuota tidak terbatas.</p>
                </div>
            </div>
        </section>

        {{-- Lokasi dan Periode --}}
        <section class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur-xl p-6 md:p-8 shadow-xl">
            <div class="mb-6">
                <h2 class="text-xl font-bold text-white">03. Lokasi & Periode</h2>
                <p class="text-sm text-gray-400 mt-1">Atur lokasi dan masa berlaku voucher.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Mall</label>
                    <select name="mall_id"
                            class="w-full rounded-xl bg-slate-800 border border-white/10 px-4 py-3 text-white focus:border-purple-500 outline-none">
                        <option value="">Semua Mall</option>
                        @foreach ($malls as $mall)
                            <option value="{{ $mall->id }}"
                                @selected(old('mall_id', $voucher->mall_id) == $mall->id)>
                                {{ $mall->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Store</label>
                    <select name="store_id"
                            class="w-full rounded-xl bg-slate-800 border border-white/10 px-4 py-3 text-white focus:border-purple-500 outline-none">
                        <option value="">Semua Store</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}"
                                @selected(old('store_id', $voucher->store_id) == $store->id)>
                                {{ $store->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Mulai Berlaku</label>
                    <input type="datetime-local" name="starts_at"
                           value="{{ old('starts_at', $voucher->starts_at?->format('Y-m-d\TH:i')) }}"
                           class="w-full rounded-xl bg-slate-800 border border-white/10 px-4 py-3 text-white focus:border-purple-500 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Berakhir</label>
                    <input type="datetime-local" name="ends_at"
                           value="{{ old('ends_at', $voucher->ends_at?->format('Y-m-d\TH:i')) }}"
                           class="w-full rounded-xl bg-slate-800 border border-white/10 px-4 py-3 text-white focus:border-purple-500 outline-none">
                    @error('ends_at') <p class="text-red-400 text-sm mt-2">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- Syarat dan Status --}}
        <section class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur-xl p-6 md:p-8 shadow-xl">
            <div class="mb-6">
                <h2 class="text-xl font-bold text-white">04. Syarat & Status</h2>
                <p class="text-sm text-gray-400 mt-1">Atur ketentuan penggunaan voucher.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Syarat dan Ketentuan</label>
                <textarea name="terms" rows="4"
                          class="w-full rounded-xl bg-slate-800 border border-white/10 px-4 py-3 text-white focus:border-purple-500 outline-none">{{ old('terms', $voucher->terms) }}</textarea>
            </div>

            <label class="flex items-center gap-3 mt-6 p-4 rounded-xl bg-white/5 border border-white/10 cursor-pointer">
                <input type="checkbox" name="is_active" value="1"
                       class="w-5 h-5 accent-purple-500"
                       @checked(old('is_active', $voucher->is_active))>
                <span>
                    <span class="block text-sm font-semibold text-white">Aktifkan Voucher</span>
                    <span class="block text-xs text-gray-400 mt-1">
                        Voucher dapat ditampilkan kepada pengguna jika periode dan kuota memenuhi syarat.
                    </span>
                </span>
            </label>
        </section>

        {{-- Action --}}
        <div class="flex flex-col sm:flex-row justify-end gap-3 pb-8">
            <a href="{{ route('admin.vouchers.index') }}"
               class="px-6 py-3 rounded-xl border border-white/10 text-gray-300 hover:bg-white/10 text-center transition">
                Batal
            </a>

            <button type="submit"
                    class="px-8 py-3 rounded-xl bg-gradient-to-r from-purple-600 to-blue-600 text-white font-semibold shadow-lg shadow-purple-900/30 hover:from-purple-500 hover:to-blue-500 hover:-translate-y-0.5 transition">
                Simpan Perubahan →
            </button>
        </div>
    </form>
</div>
@endsection
