<?php

namespace Modules\Support\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    protected $fillable = [
        'user_id', 'user_name', 'subject', 'category', 'priority',
        'status', 'assigned_to', 'assignee_name', 'last_reply_at',
    ];

    protected $casts = [
        'last_reply_at' => 'datetime',
    ];

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class)->orderBy('id');
    }
}
