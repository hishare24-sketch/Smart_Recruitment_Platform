<?php

namespace Modules\Profile\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Entities\User;

class Profile extends Model
{
    protected $fillable = [
        'user_id', 'headline', 'summary',
        'skills', 'experiences', 'certificates', 'prefs', 'proof_requests',
    ];

    protected $casts = [
        'skills' => 'array',
        'experiences' => 'array',
        'certificates' => 'array',
        'prefs' => 'array',
        'proof_requests' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
