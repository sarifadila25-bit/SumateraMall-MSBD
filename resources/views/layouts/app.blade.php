<!DOCTYPE html>
<html lang="id" prefix="schema: <http://schema.org/> mm: <http://sumateramall.org/ontology#>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>try { document.documentElement.dataset.theme = localStorage.getItem('sumateramall-theme') || 'dark'; } catch (error) { document.documentElement.dataset.theme = 'dark'; }</script>
    <title>@yield('title', 'SumateraMall Semantic Explorer')</title>

    <meta property="og:title" content="@yield('og_title', 'SumateraMall Semantic Explorer')" />
    <meta property="og:description" content="@yield('og_description', 'Sistem pencarian mall di Pulau Sumatera berbasis Web Semantik')" />
    <meta property="og:image" content="@yield('og_image', asset('images/og-default.jpg'))" />
    <meta property="og:type" content="@yield('og_type', 'website')" />
    <meta property="og:url" content="{{ url()->current() }}" />

    <script>window.sumateraGoogleMapsKey = {{ Illuminate\Support\Js::from(config('services.google_maps.browser_key')) }};</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="animated-bg min-h-screen text-white font-body">

    <nav class="site-nav fixed top-4 left-1/2 -translate-x-1/2 z-50 w-[95%] max-w-7xl" aria-label="Main navigation">
        <div class="site-nav__inner glass-strong rounded-2xl px-5 py-3 shadow-2xl">
            <a href="{{ route('home') }}" data-i18n="nav_home" class="site-nav__link">Home</a>
            <a href="{{ route('team') }}" data-i18n="nav_team" class="site-nav__link">Team</a>
            <form action="{{ route('search.filter') }}" method="GET" class="site-nav__search" role="search">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <circle cx="11" cy="11" r="7" stroke-width="2"></circle>
                    <path d="m16 16 4 4" stroke-width="2" stroke-linecap="round"></path>
                </svg>
                <label class="sr-only" for="site-search" data-i18n="nav_search_label">Search stores and malls</label>
                <input id="site-search" type="search" name="search" value="{{ request('search') }}"
                       data-i18n-placeholder="nav_search_placeholder"
                       placeholder="Search stores, categories, or malls…"
                       autocomplete="off" maxlength="100" required>
                <button type="submit" data-i18n="nav_search_button">Search</button>
            </form>
        </div>
    </nav>

    <main class="pt-32 pb-20">
        @yield('content')
    </main>

    <footer class="glass border-t border-white/10 py-8 mt-20">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <div class="site-preferences mb-5 flex justify-center gap-3">
                <button type="button" data-theme-toggle aria-label="Toggle color theme" class="glass px-3 py-2 rounded-full text-xs font-semibold">Light mode</button>
                <button type="button" data-language-toggle aria-label="Switch language" class="glass px-3 py-2 rounded-full text-xs font-semibold">ID / EN</button>
            </div>
            <p data-i18n="footer_copyright" class="text-sm text-gray-400">&copy; 2026 SumateraMall Semantic Explorer</p>
            <p data-i18n="footer_coverage" class="text-xs text-gray-500 mt-2">Data mall dari 10 provinsi di Pulau Sumatera</p>
        </div>
    </footer>

    @include('components.chatbox')
    @stack('scripts')
</body>
</html>