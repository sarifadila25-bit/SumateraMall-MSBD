@extends('layouts.app')

@section('title', 'Daftar Mall Sumatera')

@section('content')

<form action="{{ route('malls.compare') }}" method="GET" class="glass rounded-2xl p-6 mb-10">
    @if($errors->any())
        <p role="alert" class="mb-4 text-red-300">
            {{ $errors->first() }}
        </p>
    @endif

    <h2 class="font-display text-xl font-bold mb-2">
        Bandingkan Mall
    </h2>

    <p class="text-sm text-gray-400 mb-5">
        Pilih dua pusat perbelanjaan untuk melihat perbedaannya.
    </p>

    <div class="grid md:grid-cols-2 gap-4">
        <div>
            <label for="mall-first" class="block text-sm font-medium mb-2">
                Mall pertama
            </label>
            <select id="mall-first" name="mall_ids[]" required
                    class="w-full glass bg-slate-950/60 text-white p-3 rounded-xl border border-white/10">
                <option value="">Pilih mall pertama</option>
                @foreach($malls as $mall)
                    <option value="{{ $mall['id'] }}">
                {{ $mall['name'] }} - {{ $mall['city'] }}
                </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="mall-second" class="block text-sm font-medium mb-2">
                Mall kedua
            </label>
            <select id="mall-second" name="mall_ids[]" required
                    class="w-full glass bg-slate-950/60 text-white p-3 rounded-xl border border-white/10">
                <option value="">Pilih mall kedua</option>
                @foreach($malls as $mall)
                    <option value="{{ $mall['id'] }}">
                        {{ $mall['name'] }} - {{ $mall['city'] }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <button type="submit"
            class="mt-5 bg-gradient-to-r from-neon-cyan to-neon-purple text-black px-6 py-3 rounded-xl font-semibold">
        Bandingkan Mall
    </button>
</form>

@endsection