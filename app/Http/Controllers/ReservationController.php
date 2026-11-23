<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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

        Reservation::create([
            ...$validated,
            'user_id' => Auth::id(),
            'store_id' => $store->id,
            'status' => 'pending',
        ]);

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
        // Pastikan reservasi milik user yang sedang login.
        abort_unless($reservation->user_id === Auth::id(), 403);

        // Hanya Pending dan Confirmed yang boleh dibatalkan.
        if (!in_array($reservation->status, ['pending', 'confirmed'])) {
            return back()->with(
                'error',
                'Reservasi ini tidak dapat dibatalkan.'
            );
        }

        $reservation->update([
            'status' => 'cancelled',
        ]);

        return redirect()
            ->route('reservations.mine')
            ->with('success', 'Reservasi berhasil dibatalkan.');
    }

}
