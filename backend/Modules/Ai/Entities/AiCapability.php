<?php

namespace Modules\Ai\Entities;

use Illuminate\Database\Eloquent\Model;

class AiCapability extends Model
{
    protected $fillable = ['key', 'label', 'icon', 'hint', 'enabled', 'sort'];

    protected $casts = [
        'enabled' => 'boolean',
        'sort' => 'integer',
    ];
}
