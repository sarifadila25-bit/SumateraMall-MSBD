<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherTransaction extends Model
{
    protected $fillable = [
        'user_id', 'voucher_id', 'user_voucher_id',
        'type', 'amount', 'payment_status', 'payment_ref',
    ];

    protected function casts(): array
    {
        return ['amount' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function userVoucher(): BelongsTo
    {
        return $this->belongsTo(UserVoucher::class);
    }
}