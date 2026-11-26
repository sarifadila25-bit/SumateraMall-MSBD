
<!DOCTYPE html>
<html lang="id" prefix="schema: http://schema.org/ mm: http://sumateramall.org/ontology#">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script>
        try {
            document.documentElement.dataset.theme =
                localStorage.getItem('sumateramall-theme') || 'dark';
        } catch (error) {
            document.documentElement.dataset.theme = 'dark';
        }
    </script>

    <title>@yield('title', 'SumateraMall Semantic Explorer')</title>

    <meta property="og:title" content="@yield('og_title', 'SumateraMall Semantic Explorer')" />
    <meta property="og:description" content="@yield('og_description', 'Sistem pencarian mall di Pulau Sumatera berbasis Web Semantik')" />
    <meta property="og:image" content="@yield('og_image', asset('images/og-default.jpg'))" />
    <meta property="og:type" content="@yield('og_type', 'website')" />
    <meta property="og:url" content="{{ url()->current() }}" />

    <script>
        window.sumateraGoogleMapsKey =
            {{ Illuminate\Support\Js::from(config('services.google_maps.browser_key')) }};
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body class="animated-bg min-h-screen text-white font-body">

    {{-- NAVBAR --}}
    <nav class="site-nav fixed top-4 left-1/2 -translate-x-1/2 z-50 w-[95%] max-w-7xl"
         aria-label="Main navigation">

        <div class="site-nav__inner glass-strong rounded-2xl px-5 py-3 shadow-2xl flex items-center gap-3">

            {{-- MENU KIRI --}}
            <div class="flex items-center gap-3 shrink-0">

                @auth
                    @if (auth()->user()->role === 'admin')

                        {{-- MENU ADMIN --}}
                        <a href="{{ route('admin.dashboard') }}"
                           class="site-nav__link">
                            Dashboard
                        </a>

                        <a href="{{ route('team') }}"
                           data-i18n="nav_team"
                           class="site-nav__link">
                            Team
                        </a>

                        <a href="{{ route('admin.reservations.index') }}"
                           class="site-nav__link">
                            Kelola Reservasi
                        </a>

                        <a href="{{ route('admin.vouchers.index') }}"
                           class="site-nav__link">
                            Kelola Voucher
                        </a>

                    @else

                        {{-- LOGO + HOME --}}
                        <a href="{{ route('home') }}"
                           class="flex items-center gap-2 shrink-0"
                           aria-label="SumateraMall Home">

                            <img src="{{ asset('images/sumateramall-logo.png') }}"
                                 alt="SumateraMall Logo"
                                 class="w-9 h-9 object-contain rounded-lg">

                            <span data-i18n="nav_home" class="site-nav__link">
                                Home
                            </span>
                        </a>

                        {{-- TEAM --}}
                        <a href="{{ route('team') }}"
                           data-i18n="nav_team"
                           class="site-nav__link">
                            Team
                        </a>

                        {{-- VOUCHER --}}
                        <a href="{{ route('vouchers.index') }}"
                           class="site-nav__link">
                            Voucher
                        </a>

                        {{-- RESERVASI --}}
                        <a href="{{ route('reservations.index') }}"
                           class="site-nav__link">
                            Reservasi
                        </a>

                    @endif

                @else

                    {{-- LOGO + HOME --}}
                    <a href="{{ route('home') }}"
                       class="flex items-center gap-2 shrink-0"
                       aria-label="SumateraMall Home">

                        <img src="{{ asset('images/sumateramall-logo.png') }}"
                             alt="SumateraMall Logo"
                             class="w-9 h-9 object-contain rounded-lg">

                        <span data-i18n="nav_home" class="site-nav__link">
                            Home
                        </span>
                    </a>

                    {{-- TEAM --}}
                    <a href="{{ route('team') }}"
                       data-i18n="nav_team"
                       class="site-nav__link">
                        Team
                    </a>

                @endauth

            </div>

            {{-- SEARCH: USER DAN GUEST SAJA --}}
            @if (!auth()->check() || auth()->user()->role !== 'admin')

                <form action="{{ route('search.filter') }}"
                      method="GET"
                      class="site-nav__search flex-1 min-w-0"
                      role="search">

                    <svg aria-hidden="true"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor">

                        <circle cx="11" cy="11" r="7" stroke-width="2"></circle>

                        <path d="m16 16 4 4"
                              stroke-width="2"
                              stroke-linecap="round">
                        </path>

                    </svg>

                    <label class="sr-only" for="site-search">
                        Search stores and malls
                    </label>

                    <input
                        id="site-search"
                        type="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search stores, categories, or malls…"
                        autocomplete="off"
                        maxlength="100"
                        required
                    >

                    <button type="submit">
                        Search
                    </button>

                </form>

            @endif

            {{-- THEME DAN LANGUAGE SWITCH --}}
            <div class="flex items-center gap-2 shrink-0 ml-auto">

                {{-- DARK / LIGHT MODE --}}
                <button
                    type="button"
                    data-theme-toggle
                    aria-label="Toggle color theme"
                    title="Toggle theme"
                    class="glass w-10 h-10 flex items-center justify-center rounded-full hover:bg-white/20 transition"
                >
                </button>

                {{-- LANGUAGE SWITCH --}}
                <button
                    type="button"
                    data-language-toggle
                    aria-label="Switch language"
                    class="glass px-3 py-2 rounded-full text-xs font-semibold whitespace-nowrap"
                >
                    ID / EN
                </button>

            </div>

            {{-- PROFILE KANAN --}}
            <details class="site-nav__profile relative shrink-0">

                <summary class="list-none cursor-pointer flex items-center gap-2 rounded-xl px-3 py-2
                                bg-white/10 hover:bg-white/20 transition text-white">

                    <svg class="w-5 h-5"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2">

                        <circle cx="12" cy="8" r="4"></circle>

                        <path d="M5 21v-2a7 7 0 0 1 14 0v2"></path>

                    </svg>

                    <span>Profile</span>
                    <span class="text-xs">▼</span>

                </summary>

                <div class="site-nav__profile-menu absolute right-0 top-full mt-3 w-52 rounded-2xl
                            border border-white/10 shadow-2xl p-3 z-[60]">

                    {{-- BELUM LOGIN --}}
                    @guest

                        <a href="{{ route('login') }}"
                           class="site-nav__profile-link block rounded-xl px-4 py-3">
                            Login
                        </a>

                        <a href="{{ route('register') }}"
                           class="site-nav__profile-register block rounded-xl px-4 py-3 mt-1 text-center">
                            Create Account
                        </a>

                    @endguest

                    {{-- SUDAH LOGIN --}}
                    @auth

                        <div class="px-4 py-2 border-b border-white/10 mb-2">

                            <p class="text-white font-semibold">
                                {{ auth()->user()->name }}
                            </p>

                            <p class="text-gray-400 text-xs">
                                {{ auth()->user()->email }}
                            </p>

                            @if (auth()->user()->role === 'admin')

                                <span class="inline-block mt-2 text-xs text-purple-300">
                                    Admin
                                </span>

                            @else

                                <span class="inline-block mt-2 text-xs text-blue-300">
                                    User
                                </span>

                            @endif

                        </div>

                        {{-- MENU PROFILE ADMIN --}}
                        @if (auth()->user()->role === 'admin')

                            <a href="{{ route('admin.dashboard') }}"
                               class="site-nav__profile-link block rounded-xl px-4 py-3 mb-1">
                                Admin Dashboard
                            </a>

                        @else

                            {{-- MENU PROFILE USER --}}
                            <a href="{{ route('itineraries.index') }}"
                               class="site-nav__profile-link block rounded-xl px-4 py-3 mb-1">
                                My Itineraries
                            </a>

                            <a href="{{ route('vouchers.mine') }}"
                               class="site-nav__profile-link block rounded-xl px-4 py-3 mb-1">
                                Voucher Saya
                            </a>

                            <a href="{{ route('reservations.mine') }}"
                               class="site-nav__profile-link block rounded-xl px-4 py-3 mb-1">
                                Reservasi Saya
                            </a>

                        @endif

                        {{-- LOGOUT --}}
                        <form action="{{ route('logout') }}" method="POST">

                            @csrf

                            <button type="submit"
                                    class="w-full text-left rounded-xl px-4 py-3 text-red-300 hover:bg-white/10">
                                Logout
                            </button>

                        </form>

                    @endauth

                </div>

            </details>

        </div>
    </nav>

    {{-- MAIN CONTENT --}}
    <main class="pt-32 pb-20">
        @yield('content')
    </main>

    {{-- FOOTER --}}
    <footer class="glass border-t border-white/10 py-8 mt-20">

        <div class="max-w-7xl mx-auto px-6 text-center">

            <p data-i18n="footer_copyright"
               class="text-sm text-gray-400">
                &copy; 2026 SumateraMall Semantic Explorer
            </p>

            <p data-i18n="footer_coverage"
               class="text-xs text-gray-500 mt-2">
                Data mall dari 10 provinsi di Pulau Sumatera
            </p>

        </div>

    </footer>

    {{-- CHATBOX --}}
    @include('components.chatbox')

    @stack('scripts')

</body>
</html>
