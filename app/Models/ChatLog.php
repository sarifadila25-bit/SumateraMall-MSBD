<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatLog extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'user_message',
        'bot_reply',
        'intent_detected',
        'sparql_query_used',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
