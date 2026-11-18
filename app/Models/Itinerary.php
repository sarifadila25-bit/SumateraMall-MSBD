<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Itinerary extends Model
{
    protected $fillable = [
        'session_id',
        'title',
        'payload',
        'starts_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'starts_at' => 'datetime',
        ];
    }
}
