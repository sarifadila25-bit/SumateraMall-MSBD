<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ItineraryController extends Controller
{
    public function index(Request $request): View
    {
        $itineraries = $request->user()
            ->itineraries()
            ->latest()
            ->paginate(10);

        return view('itineraries.index', compact('itineraries'));
    }

    public function show(Request $request, int $id): View
    {
        $itinerary = $request->user()
            ->itineraries()
            ->findOrFail($id);

        return view('itineraries.show', compact('itinerary'));
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $itinerary = $request->user()
            ->itineraries()
            ->findOrFail($id);

        $itinerary->delete();

        return redirect()
            ->route('itineraries.index')
            ->with('success', 'Itinerary berhasil dihapus.');
    }
}
