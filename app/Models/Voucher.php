<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    protected $fillable = [
        'created_by',
        'mall_id',
        'store_id',
        'name',
        'description',
        'code',
        'discount_type',
        'discount_value',
        'max_discount',
        'minimum_purchase',
        'price',
        'quota',
        'claimed_count',
        'starts_at',
        'ends_at',
        'is_active',
        'terms',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'minimum_purchase' => 'decimal:2',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function mall(): BelongsTo
    {
        return $this->belongsTo(Mall::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function userVouchers(): HasMany
    {
        return $this->hasMany(UserVoucher::class);
    }
}
