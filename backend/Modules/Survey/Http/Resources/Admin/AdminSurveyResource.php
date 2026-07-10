<?php

namespace Modules\Survey\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminSurveyResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'state' => $this->state,
            'points_pool' => $this->points_pool,
            'responses' => is_array($this->responses) ? count($this->responses) : 0,
            'owner' => optional($this->user)->name,
            'createdAt' => optional($this->created_at)->toISOString(),
        ];
    }
}
