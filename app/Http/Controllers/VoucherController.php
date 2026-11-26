<?php

namespace App\Http\Controllers;

use App\Models\UserVoucher;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VoucherController extends Controller
{
    public function index()
    {
        $vouchers = Voucher::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->where(function ($query) {
                $query->whereNull('quota')
                    ->orWhereColumn('claimed_count', '<', 'quota');
            })
            ->with(['mall', 'store'])
            ->latest()
            ->paginate(12);

        $claimedVoucherIds = auth()->check()
    ? auth()->user()->userVouchers()
        ->whereIn('status', ['active', 'used'])
        ->pluck('voucher_id')
        ->toArray()
    : [];

        return view('vouchers.index', compact('vouchers', 'claimedVoucherIds'));
    }

    public function checkout(Voucher $voucher)
    {
        if (
            ! $voucher->is_active ||
            $voucher->price <= 0 ||
            ($voucher->starts_at && $voucher->starts_at->isFuture()) ||
            ($voucher->ends_at && $voucher->ends_at->isPast()) ||
            ($voucher->quota !== null && $voucher->claimed_count >= $voucher->quota)
        ) {
            return redirect()
                ->route('vouchers.index')
                ->withErrors(['voucher' => 'Voucher tidak tersedia untuk dibeli saat ini.']);
        }

        $alreadyOwned = auth()->user()
            ->userVouchers()
            ->where('voucher_id', $voucher->id)
            ->whereIn('status', ['active', 'used'])
            ->exists();

        if ($alreadyOwned) {
            return redirect()
                ->route('vouchers.index')
                ->withErrors(['voucher' => 'Kamu sudah memiliki voucher ini.']);
        }

        return view('vouchers.checkout', compact('voucher'));
    }

    public function purchase(Request $request, Voucher $voucher)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:bank_transfer,e_wallet,qris',
        ]);

        try {
            $userVoucher = DB::transaction(function () use ($request, $voucher, $validated) {
                $voucher = Voucher::whereKey($voucher->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    ! $voucher->is_active ||
                    $voucher->price <= 0 ||
                    ($voucher->starts_at && $voucher->starts_at->isFuture()) ||
                    ($voucher->ends_at && $voucher->ends_at->isPast()) ||
                    ($voucher->quota !== null && $voucher->claimed_count >= $voucher->quota)
                ) {
                    throw ValidationException::withMessages([
                        'voucher' => 'Voucher tidak tersedia untuk dibeli saat ini.',
                    ]);
                }

                $existing = $request->user()
                    ->userVouchers()
                    ->where('voucher_id', $voucher->id)
                    ->whereIn('status', ['active', 'used'])
                    ->exists();

                if ($existing) {
                    throw ValidationException::withMessages([
                        'voucher' => 'Kamu sudah memiliki voucher ini.',
                    ]);
                }

                $pending = $request->user()
                    ->userVouchers()
                    ->where('voucher_id', $voucher->id)
                    ->where('payment_status', 'pending')
                    ->first();

                if ($pending) {
                    throw ValidationException::withMessages([
                        'voucher' => 'Kamu masih memiliki pembayaran yang belum diselesaikan.',
                    ]);
                }

                return $request->user()->userVouchers()->create([
                    'voucher_id' => $voucher->id,
                    'claim_code' => 'SM-'.Str::upper(Str::random(10)),
                    'status' => 'pending_payment',
                    'purchase_price' => $voucher->price,
                    'payment_status' => 'pending',
                    'payment_method' => $validated['payment_method'],
                ]);
            });
        } catch (ValidationException $e) {
            throw $e;
        }

        return redirect()
            ->route('vouchers.mine')
            ->with('success', 'Pesanan berhasil dibuat. Silakan selesaikan pembayaran simulasi.');
    }

    public function myVouchers()
    {
        $userVouchers = auth()->user()
            ->userVouchers()
            ->with('voucher.mall', 'voucher.store')
            ->latest()
            ->paginate(10);

        return view('vouchers.mine', compact('userVouchers'));
    }

    public function pay(Request $request, UserVoucher $userVoucher)
    {
        try {
            DB::transaction(function () use ($request, $userVoucher) {
                $userVoucher = $request->user()
                    ->userVouchers()
                    ->whereKey($userVoucher->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($userVoucher->payment_status === 'paid') {
                    throw ValidationException::withMessages([
                        'voucher' => 'Pembayaran voucher ini sudah berhasil.',
                    ]);
                }

                if ($userVoucher->payment_status !== 'pending') {
                    throw ValidationException::withMessages([
                        'voucher' => 'Transaksi ini tidak menunggu pembayaran.',
                    ]);
                }

                $voucher = Voucher::whereKey($userVoucher->voucher_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    ! $voucher->is_active ||
                    ($voucher->starts_at && $voucher->starts_at->isFuture()) ||
                    ($voucher->ends_at && $voucher->ends_at->isPast())
                ) {
                    throw ValidationException::withMessages([
                        'voucher' => 'Voucher sudah tidak tersedia.',
                    ]);
                }

                if (
                    $voucher->quota !== null &&
                    $voucher->claimed_count >= $voucher->quota
                ) {
                    throw ValidationException::withMessages([
                        'voucher' => 'Kuota voucher sudah habis.',
                    ]);
                }

                $userVoucher->update([
                    'payment_status' => 'paid',
                    'status' => 'active',
                    'paid_at' => now(),
                    'claimed_at' => now(),
                ]);

                $voucher->increment('claimed_count');
            });
        } catch (ValidationException $e) {
            throw $e;
        }

        return redirect()
            ->route('vouchers.mine')
            ->with('success', 'Pembayaran simulasi berhasil! Voucher kamu sekarang aktif.');
    }

    public function claim(Request $request, Voucher $voucher)
    {
        try {
            DB::transaction(function () use ($request, $voucher) {
                $voucher = Voucher::whereKey($voucher->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($voucher->price > 0) {
                    throw ValidationException::withMessages([
                        'voucher' => 'Voucher ini berbayar. Silakan lakukan pembelian.',
                    ]);
                }

                if (! $voucher->is_active ||
                    ($voucher->starts_at && $voucher->starts_at->isFuture()) ||
                    ($voucher->ends_at && $voucher->ends_at->isPast())) {
                    throw ValidationException::withMessages([
                        'voucher' => 'Voucher tidak tersedia saat ini.',
                    ]);
                }

                if ($voucher->quota !== null &&
                    $voucher->claimed_count >= $voucher->quota) {
                    throw ValidationException::withMessages([
                        'voucher' => 'Kuota voucher sudah habis.',
                    ]);
                }

                $alreadyClaimed = $request->user()
                    ->userVouchers()
                    ->where('voucher_id', $voucher->id)
                    ->exists();

                if ($alreadyClaimed) {
                    throw ValidationException::withMessages([
                        'voucher' => 'Kamu sudah memiliki voucher ini.',
                    ]);
                }

                $request->user()->userVouchers()->create([
                    'voucher_id' => $voucher->id,
                    'claim_code' => 'SM-'.Str::upper(Str::random(10)),
                    'status' => 'active',
                    'purchase_price' => 0,
                    'payment_status' => 'not_required',
                    'claimed_at' => now(),
                ]);

                $voucher->increment('claimed_count');
            });
        } catch (ValidationException $e) {
            throw $e;
        }

        return redirect()
            ->route('vouchers.index')
            ->with('success', 'Voucher berhasil diklaim! Cek My Vouchers kamu.');
    }
}
