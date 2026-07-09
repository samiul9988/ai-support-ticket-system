<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'priority' => $this->priority->value,
            'source' => $this->source,
            'ip_address' => $this->when($request->user()?->isAdmin(), $this->ip_address),
            'user_agent' => $this->when($request->user()?->isAdmin(), $this->user_agent),
            'ai_context' => $this->when($this->ai_context !== null, $this->ai_context),
            'user' => new UserResource($this->whenLoaded('user')),
            'assigned_agent' => new UserResource($this->whenLoaded('assignedAgent')),
            'category' => new TicketCategoryResource($this->whenLoaded('category')),
            'replies' => TicketReplyResource::collection($this->whenLoaded('replies')),
            'replies_count' => $this->whenCounted('replies'),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'status_history' => TicketStatusHistoryResource::collection($this->whenLoaded('statusHistory')),
            'resolved_at' => $this->resolved_at,
            'closed_at' => $this->closed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
