<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuriWebhookEvent extends Model
{
    protected $table = 'suri_webhook_events';

    protected $fillable = [
        'event_id',
        'chatbot_id',
        'event_type',
        'event_timestamp',
        'payload',
        'status',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'payload'         => 'array',
        'event_timestamp' => 'datetime',
        'processed_at'    => 'datetime',
    ];
}