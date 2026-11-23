
@extends('layouts.app')

@section('title', 'Kelola Voucher - SumateraMall')

@section('content')
<div class="max-w-7xl mx-auto px-4 pt-28 pb-12">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-5 mb-9">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] gradient-text mb-3">
                SumateraMall / Admin
            </p>
            <h1 class="text-3xl md:text-4xl font-bold text-white"
                style="font-family: 'Space Grotesk', sans-serif;">
                Kelola Voucher
            </h1>
            <p class="text-sm text-gray-400 mt-2">
                Atur voucher, promo, harga, dan kuota untuk pengguna SumateraMall.
            </p>
        </div>

        <a href="{{ route('admin.vouchers.create') }}"
           class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-bold text-sm text-[#211b10] shadow-lg neon-glow transition hover:-translate-y-1"
           style="background: linear-gradient(135deg, #f8e7a1, #bd8f2f);">
            <span class="text-xl">+</span>
            Tambah Voucher
        </a>
    </div>

    {{-- Notification --}}
    @if (session('success'))
        <div class="glass rounded-xl px-5 py-4 mb-5 text-green-400 border border-green-500/20">
            ✓ {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="glass rounded-xl px-5 py-4 mb-5 text-red-400 border border-red-500/20">
            {{ session('error') }}
        </div>
    @endif

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="glass rounded-xl px-5 py-4 mb-5 text-red-400 border border-red-500/20">
            <p class="font-semibold mb-2">Ada kesalahan:</p>
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Statistics --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">

        <div class="glass-strong rounded-2xl p-6 transition hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400">Total Voucher</p>
                    <h2 class="text-3xl font-bold text-white mt-2">
                        {{ $vouchers->total() }}
                    </h2>
                </div>
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl"
                     style="background: rgba(189,143,47,.16); color: #f8e7a1;">
                    🎟
                </div>
            </div>
        </div>

        <div class="glass-strong rounded-2xl p-6 transition hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400">Voucher Aktif</p>
                    <h2 class="text-3xl font-bold text-green-400 mt-2">
                        {{ $vouchers->getCollection()->where('is_active', true)->count() }}
                    </h2>
                </div>
                <div class="w-12 h-12 rounded-xl bg-green-500/10 flex items-center justify-center text-2xl text-green-400">
                    ✓
                </div>
            </div>
        </div>

        <div class="glass-strong rounded-2xl p-6 transition hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400">Voucher Nonaktif</p>
                    <h2 class="text-3xl font-bold text-gray-300 mt-2">
                        {{ $vouchers->getCollection()->where('is_active', false)->count() }}
                    </h2>
                </div>
                <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-2xl text-gray-400">
                    ○
                </div>
            </div>
        </div>

    </div>

    {{-- Voucher Table --}}
    <div class="glass-strong rounded-2xl overflow-hidden shadow-2xl">

        <div class="px-6 py-5 border-b border-white/10">
            <h2 class="text-lg font-bold text-white"
                style="font-family: 'Space Grotesk', sans-serif;">
                Daftar Voucher
            </h2>
            <p class="text-xs text-gray-400 mt-1">
                Kelola seluruh voucher yang tersedia di SumateraMall.
            </p>
        </div>

        @if ($vouchers->isEmpty())

            <div class="px-6 py-16 text-center">
                <div class="w-20 h-20 mx-auto rounded-2xl glass flex items-center justify-center text-4xl mb-5">
                    🎟
                </div>

                <h3 class="text-xl font-bold text-white">
                    Belum Ada Voucher
                </h3>

                <p class="text-sm text-gray-400 mt-2 mb-6">
                    Mulai buat voucher pertama untuk pengguna SumateraMall.
                </p>

                <a href="{{ route('admin.vouchers.create') }}"
                   class="inline-flex px-6 py-3 rounded-xl font-bold text-sm text-[#211b10] transition hover:-translate-y-1"
                   style="background: linear-gradient(135deg, #f8e7a1, #bd8f2f);">
                    + Buat Voucher Pertama
                </a>
            </div>

        @else

            <div class="overflow-x-auto">
                <table class="w-full text-left min-w-[850px]">
                    <thead class="bg-white/5 text-xs uppercase tracking-wider text-gray-400">
                        <tr>
                            <th class="px-6 py-4 font-semibold">Voucher</th>
                            <th class="px-6 py-4 font-semibold">Kode</th>
                            <th class="px-6 py-4 font-semibold">Diskon</th>
                            <th class="px-6 py-4 font-semibold">Harga</th>
                            <th class="px-6 py-4 font-semibold">Kuota</th>
                            <th class="px-6 py-4 font-semibold">Status</th>
                            <th class="px-6 py-4 font-semibold text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-white/10">
                        @foreach ($vouchers as $voucher)
                            <tr class="transition hover:bg-white/5">

                                <td class="px-6 py-5">
                                    <p class="font-semibold text-white">
                                        {{ $voucher->name }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ $voucher->mall?->name ?? 'Semua Mall' }}
                                    </p>
                                </td>

                                <td class="px-6 py-5">
                                    <span class="inline-flex px-3 py-1.5 rounded-lg text-xs font-mono font-semibold"
                                          style="background: rgba(189,143,47,.12); color: #e8ca79; border: 1px solid rgba(189,143,47,.25);">
                                        {{ $voucher->code }}
                                    </span>
                                </td>

                                <td class="px-6 py-5 text-sm text-gray-300">
                                    @if ($voucher->discount_type === 'percentage')
                                        {{ $voucher->discount_value }}%
                                    @else
                                        Rp{{ number_format($voucher->discount_value, 0, ',', '.') }}
                                    @endif
                                </td>

                                <td class="px-6 py-5 text-sm font-semibold text-white">
                                    @if ($voucher->price == 0)
                                        <span class="text-green-400">Gratis</span>
                                    @else
                                        Rp{{ number_format($voucher->price, 0, ',', '.') }}
                                    @endif
                                </td>

                                <td class="px-6 py-5 text-sm text-gray-300">
                                    @if ($voucher->quota === null)
                                        Unlimited
                                    @else
                                        {{ $voucher->claimed_count }} / {{ $voucher->quota }}
                                    @endif
                                </td>

                                <td class="px-6 py-5">
                                    @if ($voucher->is_active)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-green-500/10 text-green-400 text-xs font-semibold border border-green-500/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/5 text-gray-400 text-xs font-semibold border border-white/10">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-5">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('admin.vouchers.edit', $voucher) }}"
                                           class="px-3 py-2 rounded-lg text-xs font-semibold transition hover:bg-white/10"
                                           style="color: #e8ca79; border: 1px solid rgba(189,143,47,.35);">
                                            Edit
                                        </a>

                                        <form action="{{ route('admin.vouchers.destroy', $voucher) }}"
                                              method="POST"
                                              onsubmit="return confirm('Yakin ingin menghapus voucher ini?')">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    class="px-3 py-2 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-semibold hover:bg-red-500/20 transition">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-5 border-t border-white/10">
                {{ $vouchers->links() }}
            </div>

        @endif
    </div>

</div>
@endsection
