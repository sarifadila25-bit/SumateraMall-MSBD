
@extends('layouts.app')

@section('title', 'Register - SumateraMall')

@section('content')
<section class="auth-page max-w-md mx-auto px-6 py-10">
    <div class="glass-strong rounded-3xl p-8 shadow-2xl border border-[#d6bd82] bg-[#fffdf6]">

        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-[#49351c]">
                Create Account
            </h1>
            <p class="text-[#796a4d] mt-2">
                Gabung dan jelajahi SumateraMall!
            </p>
            <div class="w-16 h-1 rounded-full bg-[#d6ad4f] mx-auto mt-5"></div>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-5 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('register.store') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label for="name" class="block mb-2 text-sm font-semibold text-[#49351c]">
                    Nama lengkap
                </label>
                <input id="name" type="text" name="name"
                    value="{{ old('name') }}" required
                    autocomplete="name"
                    placeholder="Masukkan nama lengkap"
                    class="w-full rounded-xl bg-white border border-[#e5d7b6] px-4 py-3 text-[#49351c] placeholder-[#b0a184] focus:outline-none focus:border-[#c69a3b] focus:ring-2 focus:ring-[#c69a3b]/20 transition">
            </div>

            <div>
                <label for="email" class="block mb-2 text-sm font-semibold text-[#49351c]">
                    Email
                </label>
                <input id="email" type="email" name="email"
                    value="{{ old('email') }}" required
                    autocomplete="email"
                    placeholder="Masukkan email"
                    class="w-full rounded-xl bg-white border border-[#e5d7b6] px-4 py-3 text-[#49351c] placeholder-[#b0a184] focus:outline-none focus:border-[#c69a3b] focus:ring-2 focus:ring-[#c69a3b]/20 transition">
            </div>

            <div>
                <label for="password" class="block mb-2 text-sm font-semibold text-[#49351c]">
                    Password
                </label>
                <input id="password" type="password" name="password"
                    required minlength="8" autocomplete="new-password"
                    placeholder="Minimal 8 karakter"
                    class="w-full rounded-xl bg-white border border-[#e5d7b6] px-4 py-3 text-[#49351c] placeholder-[#b0a184] focus:outline-none focus:border-[#c69a3b] focus:ring-2 focus:ring-[#c69a3b]/20 transition">
                <p class="text-xs text-[#796a4d] mt-2">
                    Minimal 8 karakter.
                </p>
            </div>

            <div>
                <label for="password_confirmation" class="block mb-2 text-sm font-semibold text-[#49351c]">
                    Konfirmasi password
                </label>
                <input id="password_confirmation" type="password"
                    name="password_confirmation" required minlength="8"
                    autocomplete="new-password"
                    placeholder="Masukkan ulang password"
                    class="w-full rounded-xl bg-white border border-[#e5d7b6] px-4 py-3 text-[#49351c] placeholder-[#b0a184] focus:outline-none focus:border-[#c69a3b] focus:ring-2 focus:ring-[#c69a3b]/20 transition">
            </div>

            <button type="submit"
                class="w-full rounded-xl py-3 font-semibold text-white bg-gradient-to-r from-[#d6ad4f] to-[#a77722] hover:brightness-95 shadow-md transition">
                Create Account
            </button>
        </form>

        <p class="text-center text-sm text-[#796a4d] mt-7">
            Sudah punya akun?
            <a href="{{ route('login') }}"
                class="font-semibold text-[#a77722] hover:text-[#775114] hover:underline">
                Login di sini
            </a>
        </p>

    </div>
</section>
@endsection
