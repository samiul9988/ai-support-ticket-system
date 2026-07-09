<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsageLog extends Model
{
    protected $table = 'ai_usage_logs';

    protected $fillable = [
        'ticket_id',
        'prompt_history_id',
        'model',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'cost',
        'duration_ms',
        'success',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'total_tokens' => 'integer',
            'cost' => 'float',
            'duration_ms' => 'integer',
            'success' => 'boolean',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function promptHistory(): BelongsTo
    {
        return $this->belongsTo(AiPromptHistory::class, 'prompt_history_id');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }
}
