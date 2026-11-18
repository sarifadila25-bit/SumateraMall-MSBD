@extends('layouts.app')

@section('title', 'Team — SumateraMall')

@section('content')
<div class="max-w-6xl mx-auto px-6">
    <header class="mb-12">
        <span class="bubble-filter bubble-filter--active" data-i18n="team_eyebrow">ABOUT THE PROJECT</span>
        <h1 class="font-display text-5xl font-bold mt-5 mb-3 gradient-text" data-i18n="team_title">The team behind SumateraMall</h1>
        <p class="max-w-2xl text-gray-400" data-i18n="team_description">
            A multidisciplinary project bringing mall discovery, semantic data, and practical trip planning together.
        </p>
    </header>

    <div class="grid md:grid-cols-3 gap-6">
        <article class="glass team-card rounded-3xl p-7">
            <span class="team-card__icon" aria-hidden="true">01</span>
            <h2 class="font-display text-xl font-bold mt-6 mb-3" data-i18n="team_semantic_title">Semantic data</h2>
            <p class="text-sm text-gray-400" data-i18n="team_semantic_description">
                Connecting mall, store, category, and location information in a browsable knowledge graph.
            </p>
        </article>
        <article class="glass team-card rounded-3xl p-7">
            <span class="team-card__icon" aria-hidden="true">02</span>
            <h2 class="font-display text-xl font-bold mt-6 mb-3" data-i18n="team_discovery_title">Mall discovery</h2>
            <p class="text-sm text-gray-400" data-i18n="team_discovery_description">
                Building clear search, useful filters, and accessible ways to explore shopping destinations.
            </p>
        </article>
        <article class="glass team-card rounded-3xl p-7">
            <span class="team-card__icon" aria-hidden="true">03</span>
            <h2 class="font-display text-xl font-bold mt-6 mb-3" data-i18n="team_integrations_title">Connected experiences</h2>
            <p class="text-sm text-gray-400" data-i18n="team_integrations_description">
                Bringing together maps, open data, and trip-planning tools while making data limits clear.
            </p>
        </article>
    </div>
</div>
@endsection
