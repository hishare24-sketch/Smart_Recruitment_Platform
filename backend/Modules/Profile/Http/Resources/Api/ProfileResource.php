<?php

namespace Modules\Profile\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'headline' => $this->headline ?? '',
            'summary' => $this->summary ?? '',
            'skills' => $this->skills ?? [],
            'experiences' => $this->experiences ?? [],
            'certificates' => $this->certificates ?? [],
            'prefs' => $this->prefs ?: (object) [],
            'proofRequests' => $this->proof_requests ?? [],
        ];
    }
}
