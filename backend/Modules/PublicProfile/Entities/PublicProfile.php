<?php

namespace Modules\PublicProfile\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Entities\User;

class PublicProfile extends Model
{
    protected $fillable = [
        'user_id', 'slug', 'doc', 'stats', 'testimonials', 'comments', 'inbox',
    ];

    protected $casts = [
        'doc' => 'array',
        'stats' => 'array',
        'testimonials' => 'array',
        'comments' => 'array',
        'inbox' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
