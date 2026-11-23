
@extends('layouts.app')

@section('title', 'Login - SumateraMall')

@section('content')
<section class="auth-page max-w-md mx-auto px-6 py-10">
    <div class="glass-strong rounded-3xl p-8 shadow-2xl border border-[#d6bd82] bg-[#fffdf6]">

        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-[#49351c]">
                Welcome Back!
            </h1>
            <p class="text-[#796a4d] mt-2">
                Login ke akun SumateraMall kamu
            </p>
            <div class="w-16 h-1 rounded-full bg-[#d6ad4f] mx-auto mt-5"></div>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-5 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login.store') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="block mb-2 text-sm font-semibold text-[#49351c]">
                    Email
                </label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    placeholder="Masukkan email kamu"
                    required
                    autocomplete="email"
                    class="w-full rounded-xl bg-white border border-[#e5d7b6] px-4 py-3 text-[#49351c] placeholder-[#b0a184] focus:outline-none focus:border-[#c69a3b] focus:ring-2 focus:ring-[#c69a3b]/20 transition"
                >
            </div>

            <div>
                <label for="password" class="block mb-2 text-sm font-semibold text-[#49351c]">
                    Password
                </label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    placeholder="Masukkan password kamu"
                    required
                    autocomplete="current-password"
                    class="w-full rounded-xl bg-white border border-[#e5d7b6] px-4 py-3 text-[#49351c] placeholder-[#b0a184] focus:outline-none focus:border-[#c69a3b] focus:ring-2 focus:ring-[#c69a3b]/20 transition"
                >
            </div>

            <label class="flex items-center gap-2 text-sm text-[#796a4d] cursor-pointer">
                <input type="checkbox" name="remember" value="1" class="accent-[#b88b32]">
                Ingat saya
            </label>

            <button type="submit"
                class="w-full rounded-xl py-3 font-semibold text-white bg-gradient-to-r from-[#d6ad4f] to-[#a77722] hover:brightness-95 shadow-md transition">
                Login
            </button>
        </form>

        <p class="text-center text-sm text-[#796a4d] mt-7">
            Belum punya akun?
            <a href="{{ route('register') }}" class="font-semibold text-[#a77722] hover:text-[#775114] hover:underline">
                Register sekarang
            </a>
        </p>
    </div>
</section>
@endsection
