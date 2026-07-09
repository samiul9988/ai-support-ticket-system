<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TicketReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'content',
        'is_ai_generated',
        'reply_type',
    ];

    protected function casts(): array
    {
        return [
            'is_ai_generated' => 'boolean',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault([
            'name' => 'AI Assistant',
            'email' => 'ai@system.local',
        ]);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function isAiGenerated(): bool
    {
        return $this->is_ai_generated;
    }

    public function isHumanReply(): bool
    {
        return ! $this->is_ai_generated && $this->user_id !== null;
    }

    public function isSystemReply(): bool
    {
        return $this->reply_type === 'system';
    }
}
