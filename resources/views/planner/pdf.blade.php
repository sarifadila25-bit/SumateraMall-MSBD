<!doctype html>
@php($english = ($itinerary['locale'] ?? 'id') === 'en')
<html lang="{{ $english ? 'en' : 'id' }}">
<head>
    <meta charset="utf-8">
    <title>SumateraMall itinerary</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 12px; }
        h1 { color: #0f766e; }
        .summary { margin: 18px 0; padding: 12px; background: #ecfeff; }
        .stop { padding: 10px 0; border-bottom: 1px solid #d1d5db; }
        .muted { color: #64748b; }
    </style>
</head>
<body>
    <h1>{{ $english ? 'SumateraMall Hopping Itinerary' : 'Rencana Mall Hopping SumateraMall' }}</h1>
    <p class="muted">{{ $english ? 'Created' : 'Dibuat' }} {{ $itinerary['plannedAt'] }}. {{ $english ? 'Route source:' : 'Rute:' }} {{ $itinerary['source'] === 'google_maps' ? 'Google Maps Routes API' : ($itinerary['source'] === 'openstreetmap' ? 'OpenStreetMap / OSRM' : ($english ? 'straight-line estimate' : 'estimasi garis lurus')) }}.</p>
    <div class="summary">
        {{ $english ? 'Total distance:' : 'Total jarak:' }} {{ number_format($itinerary['totalDistance'] / 1000, 1) }} km<br>
        {{ $english ? 'Total time:' : 'Total waktu:' }} {{ floor($itinerary['totalDuration'] / 3600) }} {{ $english ? 'hours' : 'jam' }} {{ floor(($itinerary['totalDuration'] % 3600) / 60) }} {{ $english ? 'minutes' : 'menit' }}
    </div>
    @foreach($itinerary['malls'] as $index => $mall)
    <div class="stop">
        <strong>{{ $index + 1 }}. {{ $mall['name'] }}</strong><br>
        <span class="muted">{{ $mall['city'] }}</span>
    </div>
    @endforeach
    @if($itinerary['notice'])
    <p class="muted">{{ $itinerary['notice'] }}</p>
    @endif
</body>
</html>
