
@extends('layouts.app')

@section('title', 'Checkout Voucher - SumateraMall')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">

    <div class="mb-8">
        <a href="{{ route('vouchers.index') }}" class="text-sm opacity-70 hover:opacity-100">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Voucher
        </a>
        <h1 class="text-3xl font-bold mt-4">Checkout Voucher</h1>
        <p class="opacity-70 mt-2">Periksa detail voucher sebelum melanjutkan pembayaran.</p>
    </div>

    <div class="glass-strong rounded-2xl p-6 sm:p-8">
        <div class="flex flex-col sm:flex-row justify-between gap-4 mb-6">
            <div>
                <span class="text-xs uppercase tracking-wider opacity-60">Voucher pilihanmu</span>
                <h2 class="text-2xl font-bold mt-2">{{ $voucher->name }}</h2>
                <p class="opacity-70 mt-2">{{ $voucher->description }}</p>
            </div>

            <div class="sm:text-right">
                <span class="text-sm opacity-60">Harga voucher</span>
                <div class="text-2xl font-bold mt-1" style="color:#d6ad55">
                    Rp{{ number_format($voucher->price, 0, ',', '.') }}
                </div>
            </div>
        </div>

        <div class="border-t border-white/10 pt-5 space-y-3 text-sm">
            <div class="flex justify-between gap-4">
                <span class="opacity-60">Diskon</span>
                <strong>
                    @if ($voucher->discount_type === 'percentage')
                        {{ rtrim(rtrim(number_format($voucher->discount_value, 2), '0'), '.') }}%
                    @else
                        Rp{{ number_format($voucher->discount_value, 0, ',', '.') }}
                    @endif
                </strong>
            </div>

            <div class="flex justify-between gap-4">
                <span class="opacity-60">Minimum pembelian</span>
                <strong>Rp{{ number_format($voucher->minimum_purchase ?? 0, 0, ',', '.') }}</strong>
            </div>

            @if ($voucher->max_discount)
                <div class="flex justify-between gap-4">
                    <span class="opacity-60">Maksimal diskon</span>
                    <strong>Rp{{ number_format($voucher->max_discount, 0, ',', '.') }}</strong>
                </div>
            @endif
        </div>

        <form action="{{ route('vouchers.purchase', $voucher) }}" method="POST" class="mt-7">
            @csrf

            <label for="payment_method" class="block font-semibold mb-3">
                Metode Pembayaran
            </label>

            <select
                name="payment_method"
                id="payment_method"
                required
                class="w-full rounded-xl px-4 py-3 border border-white/20 bg-black/20 text-current focus:outline-none focus:ring-2 focus:ring-yellow-600"
            >
                <option value="">Pilih metode pembayaran</option>
                <option value="bank_transfer">Transfer Bank (Simulasi)</option>
                <option value="e_wallet">E-Wallet (Simulasi)</option>
                <option value="qris">QRIS (Simulasi)</option>
            </select>

            @error('payment_method')
                <p class="text-red-400 text-sm mt-2">{{ $message }}</p>
            @enderror

            @error('voucher')
                <p class="text-red-400 text-sm mt-2">{{ $message }}</p>
            @enderror

            <div class="flex justify-between items-center border-t border-white/10 mt-6 pt-5">
                <span class="opacity-70">Total pembayaran</span>
                <strong class="text-xl" style="color:#d6ad55">
                    Rp{{ number_format($voucher->price, 0, ',', '.') }}
                </strong>
            </div>

            <button
                type="submit"
                class="w-full mt-6 py-3 rounded-xl font-bold text-black transition hover:brightness-110"
                style="background:linear-gradient(135deg,#e7c875,#b88a35)"
            >
                <i class="bi bi-lock-fill me-2"></i>
                Lanjutkan Pembayaran
            </button>

            <p class="text-xs text-center opacity-50 mt-4">
                Ini merupakan simulasi pembayaran untuk keperluan demonstrasi.
                Tidak ada uang sungguhan yang ditransfer.
            </p>
        </form>
    </div>
</div>
@endsection
