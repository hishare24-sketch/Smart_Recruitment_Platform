<?php

namespace Modules\Marketplace\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\User\Entities\User;

class Application extends Model
{
    protected $fillable = ['user_id', 'opportunity_id', 'stage', 'note', 'stage_changed_at'];

    protected $casts = [
        'stage_changed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ApplicationEvent::class)->orderBy('id');
    }
}
