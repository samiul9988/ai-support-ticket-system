<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketClassification extends Model
{
    protected $table = 'ticket_classifications';

    protected $fillable = [
        'ticket_id',
        'category',
        'confidence',
        'reasoning',
        'model',
        'is_auto_applied',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'float',
            'is_auto_applied' => 'boolean',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function scopeBilling($query) { return $query->where('category', 'billing'); }
    public function scopePayment($query) { return $query->where('category', 'payment'); }
    public function scopeRefund($query) { return $query->where('category', 'refund'); }
    public function scopeShipping($query) { return $query->where('category', 'shipping'); }
    public function scopeTechnical($query) { return $query->where('category', 'technical'); }
    public function scopeAccount($query) { return $query->where('category', 'account'); }
    public function scopeOrder($query) { return $query->where('category', 'order'); }
}
