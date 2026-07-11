<?php

namespace Modules\Broadcast\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminBroadcastResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'channel' => $this->channel,
            'audience' => $this->audience,
            'audience_value' => $this->audience_value,
            'status' => $this->status,
            'recipients' => (int) $this->recipients_count,
            'sender' => $this->sender_name,
            'sentAt' => optional($this->sent_at)->toISOString(),
            'createdAt' => optional($this->created_at)->toISOString(),
        ];
    }
}
