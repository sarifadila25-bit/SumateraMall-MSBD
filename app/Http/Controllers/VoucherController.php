<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\JsonResponse;

class VoucherController extends Controller
{
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
}