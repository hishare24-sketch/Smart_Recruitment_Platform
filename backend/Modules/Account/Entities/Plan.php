<?php

namespace Modules\Account\Entities;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = ['key', 'name', 'price', 'survey_limit', 'features', 'sort', 'active'];

    protected $casts = [
        'price' => 'float',
        'survey_limit' => 'integer',
        'features' => 'array',
        'sort' => 'integer',
        'active' => 'boolean',
    ];
}
