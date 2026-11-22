<?php

namespace App\Http\Controllers;

use App\Models\UserVoucher;
use App\Models\Voucher;
use App\Services\VoucherService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function __construct(private readonly VoucherService $vouchers) {}

    /** Daftar voucher aktif yang belum kedaluwarsa. */
    public function index(): JsonResponse
    {
        $vouchers = Voucher::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>', now()))
            ->latest()
            ->paginate(10);

        return response()->json($vouchers);
    }

    /** Detail satu voucher. */
    public function show(Voucher $voucher): JsonResponse
    {
        abort_unless($voucher->is_active, 404);

        return response()->json(['data' => $voucher]);
    }

    /** Claim voucher gratis. */
    public function claim(Request $request, Voucher $voucher): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        try {
            $userVoucher = $this->vouchers->claimFree($data['user_id'], $voucher->id);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $userVoucher->load('voucher')], 201);
    }

    /** Beli voucher berbayar (pembayaran disimulasikan). */
    public function purchase(Request $request, Voucher $voucher): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'simulate_payment' => ['sometimes', 'in:success,fail'],
        ]);

        $succeeds = ($data['simulate_payment'] ?? 'success') === 'success';

        try {
            $transaction = $this->vouchers->purchase($data['user_id'], $voucher->id, $succeeds);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(
            ['data' => $transaction->load('voucher')],
            $transaction->payment_status === 'paid' ? 201 : 402,
        );
    }

    /** Pakai voucher milik user. */
    public function redeem(Request $request, UserVoucher $userVoucher): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        try {
            $result = $this->vouchers->redeem($data['user_id'], $userVoucher->id);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $result->load('voucher')]);
    }

    /** Riwayat voucher dan transaksi milik user. */
    public function history(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        return response()->json(['data' => $this->vouchers->history($data['user_id'])]);
    }
}