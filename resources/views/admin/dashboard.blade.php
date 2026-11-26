
@extends('layouts.app')

@section('title', 'Admin Dashboard - SumateraMall')

@push('styles')
<style>
    /* ADMIN DASHBOARD - LIGHT MODE */
    html[data-theme="light"] .admin-eyebrow {
        color: #a87924;
    }

    html[data-theme="light"] .admin-heading {
        color: #392b18;
    }

    html[data-theme="light"] .admin-description,
    html[data-theme="light"] .admin-stat-label,
    html[data-theme="light"] .admin-access-description {
        color: #806f55;
    }

    html[data-theme="light"] .admin-primary-button {
        background: #d6a33d;
        color: #392b18;
    }

    html[data-theme="light"] .admin-primary-button:hover {
        background: #bd892b;
        color: #ffffff;
    }

    /* ADMIN DASHBOARD - DARK MODE */
    html[data-theme="dark"] .admin-eyebrow {
        color: #e2b85f;
    }

    html[data-theme="dark"] .admin-heading {
        color: #fff7e5;
    }

    html[data-theme="dark"] .admin-description,
    html[data-theme="dark"] .admin-stat-label,
    html[data-theme="dark"] .admin-access-description {
        color: #b8aa91;
    }

    html[data-theme="dark"] .admin-primary-button {
        background: #d6a33d;
        color: #392b18;
    }

    html[data-theme="dark"] .admin-primary-button:hover {
        background: #edbd57;
        color: #392b18;
    }

    .admin-primary-button {
        transition: all 0.2s ease;
    }
</style>
@endpush

@section('content')
<section class="max-w-7xl mx-auto px-6 py-10">

    {{-- DASHBOARD HEADER --}}
    <div class="mb-8">

        <p class="admin-eyebrow text-sm font-semibold uppercase tracking-wider">
            SumateraMall Administration
        </p>

        <h1 class="admin-heading text-3xl font-bold mt-2">
            Admin Dashboard
        </h1>

        <p class="admin-description mt-2">
            Selamat datang, {{ auth()->user()->name }}!
        </p>

    </div>

    {{-- STATISTICS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- TOTAL USERS --}}
        <div class="glass-strong rounded-2xl p-6 border border-white/10">

            <p class="admin-stat-label text-sm">
                Total Users
            </p>

            <h2 class="admin-heading text-4xl font-bold mt-3">
                {{ number_format($stats['users']) }}
            </h2>

        </div>

        {{-- TOTAL MALLS --}}
        <div class="glass-strong rounded-2xl p-6 border border-white/10">

            <p class="admin-stat-label text-sm">
                Total Malls
            </p>

            <h2 class="admin-heading text-4xl font-bold mt-3">
                {{ number_format($stats['malls']) }}
            </h2>

        </div>

        {{-- TOTAL STORES --}}
        <div class="glass-strong rounded-2xl p-6 border border-white/10">

            <p class="admin-stat-label text-sm">
                Total Stores
            </p>

            <h2 class="admin-heading text-4xl font-bold mt-3">
                {{ number_format($stats['stores']) }}
            </h2>

        </div>

    </div>

    {{-- ADMIN ACCESS --}}
    <div class="glass-strong rounded-2xl p-6 border border-white/10 mt-8">

        <h2 class="admin-heading text-xl font-bold mb-3">
            Admin Access
        </h2>

        <p class="admin-access-description">
            Kamu login sebagai administrator SumateraMall.
            Gunakan dashboard ini untuk memantau data sistem.
        </p>

        <a href="{{ route('home') }}"
           class="admin-primary-button inline-block mt-5 rounded-xl px-5 py-3 font-semibold transition">
            Kembali ke Homepage
        </a>

    </div>

</section>
@endsection
