
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-10">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-5 mb-8">
        <div>
            <p class="text-sm text-purple-400 font-semibold uppercase tracking-widest mb-2">
                Admin Dashboard / Management
            </p>
            <h1 class="text-3xl md:text-4xl font-bold text-white">
                Voucher Management
            </h1>
            <p class="text-gray-400 mt-2">
                Kelola semua voucher dan promo SumateraMall.
            </p>
        </div>

        <a href="{{ route('admin.vouchers.create') }}"
           class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-purple-600 to-blue-600 text-white font-semibold shadow-lg shadow-purple-900/30 hover:from-purple-500 hover:to-blue-500 hover:-translate-y-0.5 transition">
            <span class="text-xl">+</span>
            Tambah Voucher
        </a>
    </div>

    {{-- Notification --}}
    @if (session('success'))
        <div class="mb-6 rounded-xl border border-green-500/20 bg-green-500/10 px-5 py-4 text-green-300">
            ✓ {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 rounded-xl border border-red-500/20 bg-red-500/10 px-5 py-4 text-red-300">
            {{ session('error') }}
        </div>
    @endif

    {{-- Statistics --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">

        <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-6 shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400">Total Voucher</p>
                    <h2 class="text-3xl font-bold text-white mt-2">
                        {{ $vouchers->total() }}
                    </h2>
                </div>
                <div class="w-12 h-12 rounded-xl bg-purple-500/15 flex items-center justify-center text-2xl">
                    🎟️
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-6 shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400">Voucher Aktif</p>
                    <h2 class="text-3xl font-bold text-green-400 mt-2">
                        {{ $vouchers->getCollection()->where('is_active', true)->count() }}
                    </h2>
                </div>
                <div class="w-12 h-12 rounded-xl bg-green-500/15 flex items-center justify-center text-2xl">
                    ✓
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-6 shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400">Voucher Nonaktif</p>
                    <h2 class="text-3xl font-bold text-gray-300 mt-2">
                        {{ $vouchers->getCollection()->where('is_active', false)->count() }}
                    </h2>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gray-500/15 flex items-center justify-center text-2xl">
                    ◌
                </div>
            </div>
        </div>

    </div>

    {{-- Voucher Table --}}
    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur-xl shadow-xl overflow-hidden">

        <div class="px-6 py-5 border-b border-white/10">
            <h2 class="text-lg font-bold text-white">Daftar Voucher</h2>
            <p class="text-sm text-gray-400 mt-1">
                Semua voucher yang terdaftar di SumateraMall.
            </p>
        </div>

        @if ($vouchers->isEmpty())

            <div class="px-6 py-16 text-center">
                <div class="w-20 h-20 mx-auto rounded-2xl bg-purple-500/10 flex items-center justify-center text-4xl mb-5">
                    🎟️
                </div>

                <h3 class="text-xl font-bold text-white">
                    Belum Ada Voucher
                </h3>

                <p class="text-gray-400 mt-2 mb-6">
                    Mulai buat voucher pertama untuk pengguna SumateraMall.
                </p>

                <a href="{{ route('admin.vouchers.create') }}"
                   class="inline-flex px-6 py-3 rounded-xl bg-purple-600 text-white font-semibold hover:bg-purple-500 transition">
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

                    <tbody class="divide-y divide-white/5">
                        @foreach ($vouchers as $voucher)
                            <tr class="hover:bg-white/[0.03] transition">

                                <td class="px-6 py-5">
                                    <p class="font-semibold text-white">
                                        {{ $voucher->name }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ $voucher->mall?->name ?? 'Semua Mall' }}
                                    </p>
                                </td>

                                <td class="px-6 py-5">
                                    <span class="inline-flex px-3 py-1.5 rounded-lg bg-purple-500/10 border border-purple-500/20 text-purple-300 text-xs font-mono font-semibold">
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
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-gray-500/10 text-gray-400 text-xs font-semibold border border-gray-500/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-5">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('admin.vouchers.edit', $voucher) }}"
                                           class="px-3 py-2 rounded-lg bg-blue-500/10 border border-blue-500/20 text-blue-300 text-xs font-semibold hover:bg-blue-500/20 transition">
                                            Edit
                                        </a>

                                        <form action="{{ route('admin.vouchers.destroy', $voucher) }}"
                                              method="POST"
                                              onsubmit="return confirm('Yakin ingin menghapus voucher ini?')">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    class="px-3 py-2 rounded-lg bg-red-500/10 border border-red-500/20 text-red-300 text-xs font-semibold hover:bg-red-500/20 transition">
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
