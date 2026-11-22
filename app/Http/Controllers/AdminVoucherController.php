<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminVoucherController extends Controller
{
    /** Semua voucher, termasuk yang nonaktif. */
    public function index(): JsonResponse
    {
        return response()->json(Voucher::query()->latest()->paginate(20));
    }

    public function show(Voucher $voucher): JsonResponse
    {
        return response()->json(['data' => $voucher]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:vouchers,code'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(['free', 'paid'])],
            'price' => ['sometimes', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'max_per_user' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $this->assertConsistent(
            $data['type'],
            $data['price'] ?? 0,
            $data['valid_from'] ?? null,
            $data['valid_until'] ?? null,
        );

        $voucher = Voucher::create($data);

        return response()->json(['data' => $voucher->fresh()], 201);
    }

    public function update(Request $request, Voucher $voucher): JsonResponse
    {
        $data = $request->validate([
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('vouchers', 'code')->ignore($voucher->id)],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', Rule::in(['free', 'paid'])],
            'price' => ['sometimes', 'integer', 'min:0'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'max_per_user' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $this->assertConsistent(
            $data['type'] ?? $voucher->type,
            array_key_exists('price', $data) ? $data['price'] : $voucher->price,
            array_key_exists('valid_from', $data) ? $data['valid_from'] : $voucher->valid_from,
            array_key_exists('valid_until', $data) ? $data['valid_until'] : $voucher->valid_until,
        );

        // Kunci baris supaya tidak bentrok dengan claim/beli yang sedang berjalan.
        $updated = DB::transaction(function () use ($voucher, $data) {
            $locked = Voucher::whereKey($voucher->id)->lockForUpdate()->firstOrFail();
            $locked->update($data);

            return $locked;
        });

        return response()->json(['data' => $updated->fresh()]);
    }

    public function destroy(Voucher $voucher): JsonResponse
    {
        if ($voucher->userVouchers()->exists() || $voucher->transactions()->exists()) {
            return response()->json([
                'message' => 'Voucher sudah punya riwayat dan tidak bisa dihapus. Nonaktifkan dengan is_active=0.',
            ], 409);
        }

        $voucher->delete();

        return response()->json(['message' => 'Voucher dihapus.']);
    }

    /** Aturan yang sama dengan CHECK constraint di database, supaya pesan error ramah. */
    private function assertConsistent(string $type, int $price, $from, $until): void
    {
        if ($type === 'free' && $price !== 0) {
            throw ValidationException::withMessages(['price' => 'Voucher gratis harus berharga 0.']);
        }
        if ($type === 'paid' && $price <= 0) {
            throw ValidationException::withMessages(['price' => 'Voucher berbayar harus berharga lebih dari 0.']);
        }
        if ($from && $until && Carbon::parse($until)->lte(Carbon::parse($from))) {
            throw ValidationException::withMessages(['valid_until' => 'valid_until harus setelah valid_from.']);
        }
    }
}