<?php

namespace Modules\Marketplace\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opportunity extends Model
{
    protected $fillable = ['user_id', 'title', 'company', 'location', 'salary', 'category', 'skills'];

    protected $casts = ['skills' => 'array'];

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
