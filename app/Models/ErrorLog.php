<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErrorLog extends Model
{
    protected $table = 'error_logs';

    protected $fillable = [
        'error_code',
        'exception_class',
        'http_status',
        'user_message',
        'technical_message',
        'trace',
        'context',
        'user_id',
        'url',
        'method',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'http_status' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
