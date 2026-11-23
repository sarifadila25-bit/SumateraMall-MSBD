<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mall extends Model
{
    protected $fillable = [
        'city_id',
        'name',
        'address',
        'opening_hours',
        'floors',
        'category',
        'area',
        'website',
        'description',
        'source_namespace',
        'resource_uri',
        'latitude',
        'longitude',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    public function vouchers(): HasMany
    {
    return $this->hasMany(Voucher::class);
    }
}
