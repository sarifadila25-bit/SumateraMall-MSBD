
@extends('layouts.app')

@section('title', 'Our Team - SumateraMall')

@section('content')
<section class="team-page">
    <div class="team-container">

        {{-- Team Header --}}
        <header class="team-header">
            <span class="team-eyebrow">MEET OUR TEAM</span>

            <h1>
                The People Behind
                <span>SumateraMall</span>
            </h1>

            <p>
                Get to know the minds shaping SumateraMall. Together, we’re building a smarter, more seamless way to explore your favorite malls.
            </p>
        </header>

        {{-- Team Members --}}
        <div class="team-grid">

            {{-- 01. Salwa --}}
            <article class="team-member">
                <div class="team-photo">
                    <img
                        src="{{ asset('images/team/salwa.jpeg') }}"
                        alt="Salwa Salsabila Rizki"
                        loading="lazy"
                        decoding="async"
                    >
                </div>

                <div class="team-info">
                    <span class="team-number">01</span>
                    <h2>Salwa Salsabila Rizki</h2>
                    <p class="team-nim">251402123</p>
                    <span class="team-role">Backend Developer</span>
                </div>
            </article>

            {{-- 02. Syifa --}}
            <article class="team-member">
                <div class="team-photo">
                    <img
                        src="{{ asset('images/team/syifa.jpeg') }}"
                        alt="Syifa Nazira"
                        loading="lazy"
                        decoding="async"
                    >
                </div>

                <div class="team-info">
                    <span class="team-number">02</span>
                    <h2>Syifa Nazira</h2>
                    <p class="team-nim">251402126</p>
                    <span class="team-role">Backend Developer</span>
                </div>
            </article>

            {{-- 03. Jelita --}}
            <article class="team-member">
                <div class="team-photo">
                    <img
                        src="{{ asset('images/team/jelita.jpeg') }}"
                        alt="Jelita Hati Sinurat"
                        loading="lazy"
                        decoding="async"
                    >
                </div>

                <div class="team-info">
                    <span class="team-number">03</span>
                    <h2>Jelita Hati Sinurat</h2>
                    <p class="team-nim">251402141</p>
                    <span class="team-role">Dokumentasi & Laporan</span>
                </div>
            </article>

            {{-- 04. Jesqueen --}}
            <article class="team-member">
                <div class="team-photo">
                    <img
                        src="{{ asset('images/team/jesqueen.jpeg') }}"
                        alt="Jesqueen Maria Purba"
                        loading="lazy"
                        decoding="async"
                    >
                </div>

                <div class="team-info">
                    <span class="team-number">04</span>
                    <h2>Jesqueen Maria Purba</h2>
                    <p class="team-nim">251402099</p>
                    <span class="team-role">Frontend Developer</span>
                </div>
            </article>

            {{-- 05. Fadila --}}
            <article class="team-member">
                <div class="team-photo">
                    <img
                        src="{{ asset('images/team/fadila.jpeg') }}"
                        alt="Fadila Lisma Sari"
                        loading="lazy"
                        decoding="async"
                    >
                </div>

                <div class="team-info">
                    <span class="team-number">05</span>
                    <h2>Fadila Lisma Sari</h2>
                    <p class="team-nim">251402117</p>
                    <span class="team-role">Project Manager</span>
                </div>
            </article>

        </div>

        {{-- Footer --}}
        <footer class="team-footer">
            <span>SUMATERAMALL</span>
            <p>Built together, made for everyone.</p>
        </footer>

    </div>
</section>
@endsection
