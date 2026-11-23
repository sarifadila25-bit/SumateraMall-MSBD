
@extends('layouts.app')

@section('title', 'My Itineraries - SumateraMall')

@section('content')
<section class="max-w-7xl mx-auto px-6 py-10">

    <div class="mb-8">
        <p class="text-blue-400 text-sm font-semibold uppercase tracking-wider">
            Personal Planner
        </p>
        <h1 class="text-3xl font-bold mt-2">My Itineraries</h1>
        <p class="text-gray-400 mt-2">
            Kelola semua rencana perjalanan mall yang sudah kamu simpan.
        </p>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl bg-green-500/10 border border-green-500/30 p-4 text-green-400">
            {{ session('success') }}
        </div>
    @endif

    @if ($itineraries->isEmpty())
        <div class="glass-strong rounded-2xl border border-white/10 p-10 text-center">
            <h2 class="text-xl font-bold">Belum ada itinerary</h2>
            <p class="text-gray-400 mt-2">
                Buat rencana perjalanan mall dan simpan setelah login.
            </p>
            <a href="{{ route('planner.index') }}"
                class="inline-block mt-6 rounded-xl bg-blue-600 hover:bg-blue-500 px-5 py-3 font-semibold">
                Buat Itinerary
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @foreach ($itineraries as $itinerary)
                <div class="glass-strong rounded-2xl border border-white/10 p-6">
                    <h2 class="text-lg font-bold">
                        {{ $itinerary->title }}
                    </h2>

                    <p class="text-gray-400 text-sm mt-2">
                        Dibuat:
                        {{ $itinerary->created_at?->format('d M Y, H:i') ?? '-' }}
                    </p>

                    <div class="flex flex-wrap gap-3 mt-6">
                        <a href="{{ route('itineraries.show', $itinerary->id) }}"
                            class="rounded-xl bg-blue-600 hover:bg-blue-500 px-4 py-2 text-sm font-semibold">
                            Lihat Detail
                        </a>

                        <form method="POST"
                            action="{{ route('itineraries.destroy', $itinerary->id) }}"
                            onsubmit="return confirm('Yakin ingin menghapus itinerary ini?')">
                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                class="rounded-xl border border-red-500/40 text-red-400 hover:bg-red-500/10 px-4 py-2 text-sm font-semibold">
                                Hapus
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $itineraries->links() }}
        </div>
    @endif

</section>
@endsection
