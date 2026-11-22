<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Store;
use App\Models\User;
use App\Services\ReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function __construct(
        private readonly ReservationService $reservationService
    ) {}

    public function index(Request $request): View
    {
        $query = Reservation::with(['store.mall', 'user', 'statusLogs'])
            ->latest();

        if ($request->filled('code')) {
            $query->where('reservation_code', 'ilike', '%' . trim($request->string('code')) . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->user()) {
            $query->where('user_id', $request->user()->id);
        }

        $reservations = $query->paginate(10)->withQueryString();
        $stores = Store::with('mall')->orderBy('name')->get();

        return view('reservations.index', compact('reservations', 'stores'));
    }

    public function create(Request $request): View
    {
        $stores = Store::with('mall')->orderBy('name')->get();
        $selectedStoreId = $request->integer('store_id') ?: null;

        return view('reservations.create', compact('stores', 'selectedStoreId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'reservation_time' => ['required', 'date_format:H:i'],
            'guest_count' => ['required', 'integer', 'min:1', 'max:50'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:30'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'special_requests' => ['nullable', 'string', 'max:1000'],
        ]);

        $userId = $request->user()?->id ?? $this->resolveGuestUserId(
            $validated['contact_name'],
            $validated['contact_email'] ?? null,
            $validated['contact_phone']
        );

        $reservation = $this->reservationService->createReservation($validated, $userId);

        return redirect()
            ->route('reservations.show', $reservation->reservation_code)
            ->with('success', "Reservasi berhasil dibuat dengan kode {$reservation->reservation_code}.");
    }

    public function show(string $code): View
    {
        $reservation = Reservation::with(['store.mall', 'user', 'reviewer', 'statusLogs.changer'])
            ->where('reservation_code', $code)
            ->firstOrFail();

        return view('reservations.show', compact('reservation'));
    }

    public function cancel(Request $request, string $code): RedirectResponse
    {
        $reservation = Reservation::where('reservation_code', $code)->firstOrFail();

        if (!$reservation->canBeCancelledByUser()) {
            return back()->with('error', 'Reservasi dengan status saat ini tidak dapat dibatalkan.');
        }

        $validated = $request->validate([
            'cancellation_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->reservationService->updateStatus(
            $reservation,
            Reservation::STATUS_CANCELLED,
            $request->user()?->id ?? $reservation->user_id,
            $validated['cancellation_reason'] ?? 'Dibatalkan oleh pengunjung.'
        );

        return back()->with('success', 'Reservasi berhasil dibatalkan.');
    }

    public function updateStatus(Request $request, string $code): RedirectResponse
    {
        $reservation = Reservation::where('reservation_code', $code)->firstOrFail();

        $validated = $request->validate([
            'status' => ['required', 'in:confirmed,rejected,cancelled'],
            'admin_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->reservationService->updateStatus(
            $reservation,
            $validated['status'],
            $request->user()?->id,
            $validated['admin_notes'] ?? null
        );

        return back()->with('success', 'Status reservasi berhasil diperbarui.');
    }

    private function resolveGuestUserId(string $name, ?string $email, string $phone): int
    {
        $resolvedEmail = $email ?: ('guest_' . preg_replace('/\D+/', '', $phone) . '@sumateramall.local');

        $user = User::firstOrCreate(
            ['email' => $resolvedEmail],
            [
                'name' => $name,
                'password' => bcrypt(str()->random(24)),
            ]
        );

        return $user->id;
    }
}