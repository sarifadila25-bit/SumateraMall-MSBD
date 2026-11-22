<?php

namespace App\Services;

use App\Models\UserVoucher;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VoucherService
{
    /** Claim voucher gratis. */
    public function claimFree(int $userId, int $voucherId): UserVoucher
    {
        return DB::transaction(function () use ($userId, $voucherId) {
            $voucher = $this->lockAndValidate($voucherId, $userId);

            if ($voucher->type !== 'free') {
                throw new DomainException('Voucher ini berbayar, gunakan pembelian.');
            }

            $userVoucher = $this->grant($voucher, $userId);

            VoucherTransaction::create([
                'user_id' => $userId,
                'voucher_id' => $voucher->id,
                'user_voucher_id' => $userVoucher->id,
                'type' => 'claim',
                'amount' => 0,
                'payment_status' => 'paid',
            ]);

            return $userVoucher;
        });
    }

    /** Beli voucher berbayar. Pembayaran hanya simulasi. */
    public function purchase(int $userId, int $voucherId, bool $paymentSucceeds = true): VoucherTransaction
    {
        return DB::transaction(function () use ($userId, $voucherId, $paymentSucceeds) {
            $voucher = $this->lockAndValidate($voucherId, $userId);

            if ($voucher->type !== 'paid') {
                throw new DomainException('Voucher ini gratis, gunakan claim.');
            }

            $status = $paymentSucceeds ? 'paid' : 'failed';
            $userVoucher = $status === 'paid' ? $this->grant($voucher, $userId) : null;

            return VoucherTransaction::create([
                'user_id' => $userId,
                'voucher_id' => $voucher->id,
                'user_voucher_id' => $userVoucher?->id,
                'type' => 'purchase',
                'amount' => $voucher->price,
                'payment_status' => $status,
                'payment_ref' => 'PAY-'.strtoupper(Str::random(12)),
            ]);
        });
    }

    /** Pakai voucher milik user. */
    public function redeem(int $userId, int $userVoucherId): UserVoucher
    {
        return DB::transaction(function () use ($userId, $userVoucherId) {
            $uv = UserVoucher::whereKey($userVoucherId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (! $uv) {
                throw new DomainException('Voucher milik pengguna tidak ditemukan.');
            }
            if ($uv->status !== 'active') {
                throw new DomainException('Voucher sudah dipakai atau kedaluwarsa.');
            }
            if ($uv->expires_at && now()->gt($uv->expires_at)) {
                throw new DomainException('Voucher sudah kedaluwarsa.');
            }

            $uv->update(['status' => 'used', 'used_at' => now()]);

            return $uv;
        });
    }

    /** Riwayat voucher dan transaksi milik user. */
    public function history(int $userId): array
    {
        return [
            'vouchers' => UserVoucher::with('voucher')
                ->where('user_id', $userId)->latest('claimed_at')->get(),
            'transactions' => VoucherTransaction::with('voucher')
                ->where('user_id', $userId)->latest()->get(),
        ];
    }

    /** Validasi voucher (aktif, masa berlaku, stok). */
    public function assertAvailable(Voucher $voucher): void
    {
        $now = now();

        if (! $voucher->is_active) {
            throw new DomainException('Voucher tidak aktif.');
        }
        if ($voucher->valid_from && $now->lt($voucher->valid_from)) {
            throw new DomainException('Voucher belum berlaku.');
        }
        if ($voucher->valid_until && $now->gt($voucher->valid_until)) {
            throw new DomainException('Voucher sudah kedaluwarsa.');
        }
        if ($voucher->stock <= 0) {
            throw new DomainException('Stok voucher habis.');
        }
    }

    /**
     * Kunci baris voucher (SELECT ... FOR UPDATE) lalu validasi.
     * Semua permintaan untuk voucher yang sama antre di sini,
     * sehingga stok dan batas per user tidak bisa "tembus" saat bersamaan.
     */
    private function lockAndValidate(int $voucherId, int $userId): Voucher
    {
        $voucher = Voucher::whereKey($voucherId)->lockForUpdate()->first();

        if (! $voucher) {
            throw new DomainException('Voucher tidak ditemukan.');
        }

        $this->assertAvailable($voucher);

        $owned = UserVoucher::where('user_id', $userId)
            ->where('voucher_id', $voucherId)->count();

        if ($owned >= $voucher->max_per_user) {
            throw new DomainException('Batas klaim per pengguna tercapai.');
        }

        return $voucher;
    }

    private function grant(Voucher $voucher, int $userId): UserVoucher
    {
        $voucher->decrement('stock');

        return UserVoucher::create([
            'user_id' => $userId,
            'voucher_id' => $voucher->id,
            'status' => 'active',
            'claimed_at' => now(),
            'expires_at' => $voucher->valid_until,
        ]);
    }
}