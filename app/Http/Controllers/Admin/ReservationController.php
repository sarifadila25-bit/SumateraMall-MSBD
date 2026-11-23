<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReservationController extends Controller
{
    public function index()
    {
        $reservations = Reservation::with([
            'user:id,name,email',
            'store:id,name,mall_id',
            'store.mall:id,name',
        ])
            ->latest()
            ->paginate(15);

        return view('admin.reservations.index', compact('reservations'));
    }

    public function update(Request $request, Reservation $reservation)
{
    $allowedTransitions = match ($reservation->status) {
        'pending' => ['confirmed', 'rejected'],
        'confirmed' => ['completed'],
        default => [],
    };

    if (empty($allowedTransitions)) {
        return back()->with(
            'error',
            'Status reservasi ini sudah final dan tidak dapat diubah lagi.'
        );
    }

    $validated = $request->validate([
        'status' => [
            'required',
            Rule::in($allowedTransitions),
        ],
        'admin_notes' => ['nullable', 'string', 'max:1000'],
    ]);

    $reservation->update($validated);

    return redirect()
        ->route('admin.reservations.index')
        ->with('success', 'Status reservasi berhasil diperbarui.');
}
}
