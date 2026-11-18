<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'resource_uri',
        'resource_type',
    ];
}
