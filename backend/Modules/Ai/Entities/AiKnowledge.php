<?php

namespace Modules\Ai\Entities;

use Illuminate\Database\Eloquent\Model;

class AiKnowledge extends Model
{
    protected $table = 'ai_knowledge';

    protected $fillable = ['title', 'content', 'tags', 'enabled'];

    protected $casts = [
        'tags' => 'array',
        'enabled' => 'boolean',
    ];
}
