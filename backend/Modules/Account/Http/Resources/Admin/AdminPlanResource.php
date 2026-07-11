<?php

namespace Modules\Account\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\User\Entities\User;

class AdminPlanResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->name,
            'price' => (float) $this->price,
            'survey_limit' => $this->survey_limit,
            'features' => is_array($this->features) ? $this->features : [],
            'active' => (bool) $this->active,
            'sort' => (int) $this->sort,
            // عدد المشتركين الحاليّين في هذه الباقة (users.tier)
            'subscribers' => User::where('tier', $this->key)->count(),
        ];
    }
}
