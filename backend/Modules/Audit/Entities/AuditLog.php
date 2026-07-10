<?php

namespace Modules\Audit\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Entities\User;

class AuditLog extends Model
{
    public $timestamps = false; // created_at فقط (يُضبط يدويًّا)

    protected $fillable = ['actor_id', 'actor_name', 'method', 'resource', 'action', 'path', 'target_id', 'status', 'meta', 'ip', 'created_at'];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
