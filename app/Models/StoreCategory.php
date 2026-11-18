<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class StoreCategory extends Pivot
{
    protected $table = 'store_categories';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'store_id',
        'category_id',
    ];
}
