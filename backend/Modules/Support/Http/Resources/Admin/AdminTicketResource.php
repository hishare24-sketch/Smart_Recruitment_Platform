<?php

namespace Modules\Support\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminTicketResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'user' => $this->user_name ?? '—',
            'category' => $this->category,
            'priority' => $this->priority,
            'status' => $this->status,
            'assignee' => $this->assignee_name,
            'repliesCount' => $this->replies_count ?? $this->replies()->count(),
            'lastReplyAt' => optional($this->last_reply_at)->toISOString(),
            'createdAt' => optional($this->created_at)->toISOString(),
            'replies' => $this->whenLoaded('replies', fn () => $this->replies->map(fn ($r) => [
                'id' => $r->id,
                'author' => $r->author_name,
                'isStaff' => (bool) $r->is_staff,
                'body' => $r->body,
                'at' => optional($r->created_at)->toISOString(),
            ])->values()),
        ];
    }
}
