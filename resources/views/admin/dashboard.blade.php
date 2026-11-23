
@extends('layouts.app')

@section('title', 'Admin Dashboard - SumateraMall')

@section('content')
<section class="max-w-7xl mx-auto px-6 py-10">
    <div class="mb-8">
        <p class="text-blue-400 text-sm font-semibold uppercase tracking-wider">
            SumateraMall Administration
        </p>
        <h1 class="text-3xl font-bold mt-2">Admin Dashboard</h1>
        <p class="text-gray-400 mt-2">
            Selamat datang, {{ auth()->user()->name }}!
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="glass-strong rounded-2xl p-6 border border-white/10">
            <p class="text-gray-400 text-sm">Total Users</p>
            <h2 class="text-4xl font-bold mt-3">{{ number_format($stats['users']) }}</h2>
        </div>

        <div class="glass-strong rounded-2xl p-6 border border-white/10">
            <p class="text-gray-400 text-sm">Total Malls</p>
            <h2 class="text-4xl font-bold mt-3">{{ number_format($stats['malls']) }}</h2>
        </div>

        <div class="glass-strong rounded-2xl p-6 border border-white/10">
            <p class="text-gray-400 text-sm">Total Stores</p>
            <h2 class="text-4xl font-bold mt-3">{{ number_format($stats['stores']) }}</h2>
        </div>
    </div>

    <div class="glass-strong rounded-2xl p-6 border border-white/10 mt-8">
        <h2 class="text-xl font-bold mb-3">Admin Access</h2>
        <p class="text-gray-400">
            Kamu login sebagai administrator SumateraMall.
            Gunakan dashboard ini untuk memantau data sistem.
        </p>

        <a href="{{ route('home') }}"
            class="inline-block mt-5 rounded-xl bg-blue-600 hover:bg-blue-500 px-5 py-3 font-semibold transition">
            Kembali ke Homepage
        </a>
    </div>
</section>
@endsection
