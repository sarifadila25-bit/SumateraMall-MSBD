<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function index()
    {
        $restaurants = Store::with('mall:id,name')
            ->where(function ($query) {
                $query->where('category_text', 'ILIKE', '%restaurant%')
                    ->orWhere('category_text', 'ILIKE', '%restoran%');
            })
            ->select('id', 'name', 'category_text', 'mall_id', 'opening_hours', 'phone')
            ->orderBy('name')
            ->paginate(12);

        return view('reservations.index', compact('restaurants'));
    }

    public function store(Request $request, Store $store)
{
    abort_unless(
        str_contains(strtolower($store->category_text ?? ''), 'restaurant')
        || str_contains(strtolower($store->category_text ?? ''), 'restoran'),
        404
    );

    $validated = $request->validate([
        'customer_name' => ['required', 'string', 'max:255'],
        'customer_phone' => ['required', 'string', 'max:30'],
        'reservation_at' => ['required', 'date', 'after:now'],
        'party_size' => ['required', 'integer', 'min:1', 'max:50'],
        'notes' => ['nullable', 'string', 'max:1000'],
    ]);

    DB::transaction(function () use ($validated, $store) {
        $reservation = Reservation::create([
            ...$validated,
            'user_id' => Auth::id(),
            'store_id' => $store->id,
            'status' => 'pending',
        ]);

        $reservation->statusLogs()->create([
            'changed_by' => Auth::id(),
            'from_status' => null,
            'to_status' => 'pending',
            'note' => 'Reservasi berhasil dibuat dan menunggu konfirmasi.',
        ]);
    });

    return redirect()
        ->route('reservations.mine')
        ->with('success', 'Reservasi berhasil dikirim! Silakan tunggu konfirmasi dari admin.');
}

    public function mine()
    {
        $reservations = Reservation::with('store.mall')
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        return view('reservations.mine', compact('reservations'));
    }


    public function cancel(Reservation $reservation)
    {
    abort_unless($reservation->user_id === Auth::id(), 403);

    if (!in_array($reservation->status, ['pending', 'confirmed'])) {
        return back()->with(
            'error',
            'Reservasi ini tidak dapat dibatalkan.'
        );
    }

    DB::transaction(function () use ($reservation) {
        $oldStatus = $reservation->status;

        $reservation->update([
            'status' => 'cancelled',
        ]);

        $reservation->statusLogs()->create([
            'changed_by' => Auth::id(),
            'from_status' => $oldStatus,
            'to_status' => 'cancelled',
            'note' => 'Reservasi dibatalkan oleh pengguna.',
        ]);
    });

    return redirect()
        ->route('reservations.mine')
        ->with('success', 'Reservasi berhasil dibatalkan.');
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

    DB::transaction(function () use ($request, $reservation, $validated) {
        $oldStatus = $reservation->status;

        $reservation->update($validated);

        $reservation->statusLogs()->create([
            'changed_by' => $request->user()->id,
            'from_status' => $oldStatus,
            'to_status' => $validated['status'],
            'note' => $validated['admin_notes'] ?? null,
        ]);
    });

    return redirect()
        ->route('admin.reservations.index')
        ->with('success', 'Status reservasi berhasil diperbarui.');
}

}
