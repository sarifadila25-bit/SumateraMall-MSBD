<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserVoucher extends Model
{
    protected $fillable = [
        'user_id',
        'voucher_id',
        'claim_code',
        'status',
        'purchase_price',
        'payment_status',
        'payment_method',
        'paid_at',
        'claimed_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'paid_at' => 'datetime',
            'claimed_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }
}
