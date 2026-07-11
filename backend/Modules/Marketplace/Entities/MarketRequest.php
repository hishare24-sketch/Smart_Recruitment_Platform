<?php

namespace Modules\Marketplace\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketRequest extends Model
{
    use SoftDeletes;

    protected $table = 'market_requests';

    protected $fillable = ['user_id', 'type', 'title', 'org', 'state', 'compensation', 'remote'];

    protected $casts = ['remote' => 'boolean'];
}
