<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ReservationStatusLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ReservationService
{
    public function createReservation(array $data, ?int $userId = null): Reservation
    {
        return DB::transaction(function () use ($data, $userId): Reservation {
            $reservation = Reservation::create([
                'reservation_code' => $this->generateUniqueCode(),
                'user_id' => $userId ?? $data['user_id'],
                'store_id' => $data['store_id'],
                'reservation_date' => $data['reservation_date'],
                'reservation_time' => $data['reservation_time'],
                'guest_count' => $data['guest_count'],
                'contact_name' => $data['contact_name'],
                'contact_phone' => $data['contact_phone'],
                'special_requests' => $data['special_requests'] ?? null,
                'status' => Reservation::STATUS_PENDING,
            ]);

            ReservationStatusLog::create([
                'reservation_id' => $reservation->id,
                'changed_by' => $userId ?? $data['user_id'],
                'from_status' => null,
                'to_status' => Reservation::STATUS_PENDING,
                'note' => 'Reservasi berhasil dibuat dan menunggu konfirmasi.',
                'created_at' => now(),
            ]);

            return $reservation->load(['store.mall', 'user', 'statusLogs']);
        });
    }

    public function updateStatus(
        Reservation $reservation,
        string $newStatus,
        ?int $actorId = null,
        ?string $note = null
    ): Reservation {
        $allowedStatuses = [
            Reservation::STATUS_CONFIRMED,
            Reservation::STATUS_REJECTED,
            Reservation::STATUS_CANCELLED,
        ];

        if (!in_array($newStatus, $allowedStatuses, true)) {
            throw new InvalidArgumentException("Status reservasi tidak valid: {$newStatus}");
        }

        if ($reservation->status === $newStatus) {
            return $reservation;
        }

        return DB::transaction(function () use ($reservation, $newStatus, $actorId, $note): Reservation {
            $oldStatus = $reservation->status;

            $updates = ['status' => $newStatus];

            if ($newStatus === Reservation::STATUS_CANCELLED) {
                $updates['cancelled_at'] = now();
                $updates['cancellation_reason'] = $note;
            } else {
                $updates['reviewed_by'] = $actorId;
                $updates['reviewed_at'] = now();
                $updates['admin_notes'] = $note;
            }

            $reservation->update($updates);

            ReservationStatusLog::create([
                'reservation_id' => $reservation->id,
                'changed_by' => $actorId,
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'note' => $note,
                'created_at' => now(),
            ]);

            return $reservation->fresh(['store.mall', 'user', 'reviewer', 'statusLogs']);
        });
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = 'RSV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
        } while (Reservation::where('reservation_code', $code)->exists());

        return $code;
    }
}