
@extends('layouts.app')

@section('title', 'Itinerary Detail - SumateraMall')

@section('content')
@php
    $payload = $itinerary->payload ?? [];
    $malls = $payload['malls'] ?? [];
@endphp

<section class="max-w-5xl mx-auto px-6 py-10">

    <a href="{{ route('itineraries.index') }}"
        class="text-blue-400 hover:text-blue-300 text-sm">
        &larr; Kembali ke My Itineraries
    </a>

    <div class="mt-6 mb-8">
        <p class="text-blue-400 text-sm font-semibold uppercase tracking-wider">
            Saved Journey
        </p>

        <h1 class="text-3xl font-bold mt-2">
            {{ $itinerary->title }}
        </h1>

        <p class="text-gray-400 mt-2">
            Dibuat pada:
            {{ $itinerary->created_at?->format('d M Y, H:i') ?? '-' }}
        </p>
    </div>

    <div class="glass-strong rounded-2xl border border-white/10 p-6">
        <h2 class="text-xl font-bold mb-5">Daftar Mall</h2>

        @forelse ($malls as $index => $mall)
            <div class="flex gap-4 py-4 border-b border-white/10 last:border-0">
                <div class="flex-shrink-0 w-9 h-9 rounded-full bg-blue-600 flex items-center justify-center font-bold">
                    {{ $index + 1 }}
                </div>

                <div>
                    <h3 class="font-semibold">
                        {{ $mall['name'] ?? 'Mall tanpa nama' }}
                    </h3>

                    <p class="text-gray-400 text-sm mt-1">
                        {{ $mall['city'] ?? '-' }}
                    </p>

                    @if (!empty($mall['address']))
                        <p class="text-gray-500 text-sm mt-1">
                            {{ $mall['address'] }}
                        </p>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-gray-400">
                Detail mall tidak tersedia pada itinerary ini.
            </p>
        @endforelse
    </div>

    <div class="mt-6">
        <a href="{{ route('planner.index') }}"
            class="inline-block rounded-xl bg-blue-600 hover:bg-blue-500 px-5 py-3 font-semibold">
            Buat Rute Baru
        </a>
    </div>

</section>
@endsection
