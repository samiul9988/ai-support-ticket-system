<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'assigned_to',
        'category_id',
        'title',
        'description',
        'status',
        'priority',
        'source',
        'ip_address',
        'user_agent',
        'ai_context',
        'resolved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'ai_context' => 'array',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class)->latest();
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(TicketStatusHistory::class)->oldest();
    }

    public function scopeOpen($query)
    {
        return $query->where('status', TicketStatus::OPEN);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', TicketStatus::IN_PROGRESS);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', TicketStatus::RESOLVED);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', TicketStatus::CLOSED);
    }

    public function scopeByPriority($query, TicketPriority $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeAssignedTo($query, int $agentId)
    {
        return $query->where('assigned_to', $agentId);
    }

    public function scopeByCustomer($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function isOpen(): bool
    {
        return $this->status === TicketStatus::OPEN;
    }

    public function isResolved(): bool
    {
        return $this->status === TicketStatus::RESOLVED;
    }

    public function isClosed(): bool
    {
        return $this->status === TicketStatus::CLOSED;
    }
}
