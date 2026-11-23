
@extends('layouts.app')

@section('title', 'My Vouchers - SumateraMall')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

    <div class="mb-8">
        <a href="{{ route('vouchers.index') }}" class="text-sm opacity-70 hover:opacity-100">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Voucher
        </a>
        <h1 class="text-3xl font-bold mt-4">My Vouchers</h1>
        <p class="opacity-70 mt-2">Kelola voucher yang sudah kamu klaim dan beli.</p>
    </div>

    @if (session('success'))
    <div class="rounded-xl p-4 mb-6 border border-green-500/30 bg-green-500/10 text-green-800 dark:text-green-300 font-semibold">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    </div>
@endif

    @if ($userVouchers->isEmpty())
        <div class="glass-strong rounded-2xl p-10 text-center">
            <i class="bi bi-ticket-perforated text-5xl opacity-40"></i>
            <h2 class="text-xl font-bold mt-4">Belum Ada Voucher</h2>
            <p class="opacity-60 mt-2">Yuk, cari voucher menarik untuk digunakan!</p>
            <a href="{{ route('vouchers.index') }}"
               class="inline-block mt-5 px-6 py-3 rounded-xl font-semibold text-black"
               style="background:linear-gradient(135deg,#e7c875,#b88a35)">
                Jelajahi Voucher
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @foreach ($userVouchers as $userVoucher)
                <div class="glass-strong rounded-2xl p-6">
                    <div class="flex justify-between items-start gap-3">
                        <div>
                            <span class="text-xs uppercase tracking-wider opacity-50">SumateraMall Voucher</span>
                            <h2 class="text-xl font-bold mt-2">
                                {{ $userVoucher->voucher->name ?? 'Voucher' }}
                            </h2>
                        </div>

                        @if ($userVoucher->payment_status === 'pending')
                            <span class="text-xs px-3 py-1 rounded-full bg-yellow-500/15 text-yellow-300">
                                Menunggu Pembayaran
                            </span>
                        @elseif ($userVoucher->status === 'used')
                            <span class="text-xs px-3 py-1 rounded-full bg-gray-500/20 opacity-80">
                                Sudah Digunakan
                            </span>
                        @else
                            <span class="text-xs px-3 py-1 rounded-full bg-green-500/15 text-green-300">
                                Aktif
                            </span>
                        @endif
                    </div>

                    <p class="text-sm opacity-60 mt-3">
                        {{ $userVoucher->voucher->description ?? 'Voucher SumateraMall' }}
                    </p>

                    <div class="border-t border-white/10 mt-5 pt-4 space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <span class="opacity-60">Harga</span>
                            <strong>Rp{{ number_format((float) $userVoucher->purchase_price, 0, ',', '.') }}</strong>
                        </div>

                        @if ($userVoucher->payment_method)
                            <div class="flex justify-between gap-3">
                                <span class="opacity-60">Metode pembayaran</span>
                                <strong>
                                    {{ [
                                        'bank_transfer' => 'Transfer Bank',
                                        'e_wallet' => 'E-Wallet',
                                        'qris' => 'QRIS',
                                    ][$userVoucher->payment_method] ?? $userVoucher->payment_method }}
                                </strong>
                            </div>
                        @endif

                        @if ($userVoucher->payment_status !== 'pending')
                            <div class="flex justify-between gap-3">
                                <span class="opacity-60">Kode voucher</span>
                                <strong class="font-mono" style="color:#d6ad55">
                                    {{ $userVoucher->claim_code }}
                                </strong>
                            </div>
                        @endif
                    </div>

                    @if ($userVoucher->payment_status === 'pending')
                        <form action="{{ route('vouchers.pay', $userVoucher) }}" method="POST" class="mt-5">
                            @csrf
                            <button type="submit"
                                class="w-full py-3 rounded-xl font-bold text-black hover:brightness-110"
                                style="background:linear-gradient(135deg,#e7c875,#b88a35)">
                                <i class="bi bi-credit-card me-2"></i>
                                Bayar Sekarang
                            </button>
                        </form>
                    @elseif ($userVoucher->status === 'active')
                        <div class="mt-5 text-xs opacity-50">
                            <i class="bi bi-info-circle me-1"></i>
                            Voucher siap digunakan sesuai syarat dan ketentuan.
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $userVouchers->links() }}
        </div>
    @endif
</div>
@endsection
