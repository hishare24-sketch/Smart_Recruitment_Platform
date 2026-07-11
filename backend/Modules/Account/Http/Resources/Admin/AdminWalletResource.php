<?php

namespace Modules\Account\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminWalletResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'userName' => optional($this->user)->name,
            'userEmail' => optional($this->user)->email,
            'balance' => (float) $this->balance,
            'transactions' => is_array($this->transactions) ? count($this->transactions) : 0,
            'updatedAt' => optional($this->updated_at)->toISOString(),
        ];
    }
}
