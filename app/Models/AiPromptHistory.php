<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiPromptHistory extends Model
{
    protected $table = 'ai_prompt_history';

    protected $fillable = [
        'ticket_id',
        'prompt_type',
        'system_prompt',
        'user_prompt',
        'full_prompt',
        'model',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function usageLogs()
    {
        return $this->hasMany(AiUsageLog::class, 'prompt_history_id');
    }
}
