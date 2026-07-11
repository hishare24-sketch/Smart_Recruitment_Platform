<?php

namespace Modules\Broadcast\Entities;

use Illuminate\Database\Eloquent\Model;

class Broadcast extends Model
{
    protected $fillable = [
        'title', 'body', 'channel', 'audience', 'audience_value',
        'status', 'recipients_count', 'sent_by', 'sender_name', 'sent_at',
    ];

    protected $casts = [
        'recipients_count' => 'integer',
        'sent_at' => 'datetime',
    ];
}
