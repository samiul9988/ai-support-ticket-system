<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketSentiment extends Model
{
    protected $table = 'ticket_sentiments';

    protected $fillable = [
        'ticket_id',
        'ticket_reply_id',
        'sentiment',
        'confidence',
        'analysis_text',
        'model',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'float',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function reply(): BelongsTo
    {
        return $this->belongsTo(TicketReply::class, 'ticket_reply_id');
    }

    public function scopeHappy($query) { return $query->where('sentiment', 'happy'); }
    public function scopeNeutral($query) { return $query->where('sentiment', 'neutral'); }
    public function scopeConfused($query) { return $query->where('sentiment', 'confused'); }
    public function scopeAngry($query) { return $query->where('sentiment', 'angry'); }
    public function scopeUrgent($query) { return $query->where('sentiment', 'urgent'); }
}
