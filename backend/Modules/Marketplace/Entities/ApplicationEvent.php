<?php

namespace Modules\Marketplace\Entities;

use Illuminate\Database\Eloquent\Model;

class ApplicationEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['application_id', 'from_stage', 'to_stage', 'note', 'actor_id', 'actor_name', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
