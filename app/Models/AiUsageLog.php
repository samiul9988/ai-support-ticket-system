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
        'http_status',
        'request_endpoint',
        'operation',
        'request_payload_size',
        'response_body',
        'retry_count',
        'error_type',
        'log_level',
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
            'request_payload_size' => 'integer',
            'retry_count' => 'integer',
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

    public function scopeByOperation($query, string $operation)
    {
        return $query->where('operation', $operation);
    }

    public function scopeErrorsOnly($query)
    {
        return $query->where('log_level', 'error');
    }

    public function scopeRetried($query)
    {
        return $query->where('retry_count', '>', 0);
    }
}
