<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuriMensagem extends Model
{
    protected $table = 'suri_mensagens';

    protected $fillable = [
        'suri_contato_id',
        'inscricao_id',
        'external_id',
        'direcao',
        'message_type',
        'texto',
        'is_echo',
        'is_deleted',
        'status',
        'status_weight',
        'sent_timestamp_ms',
        'attendant_id',
        'attendant_name',
        'channel_id',
        'error_code',
        'error_reason',
        'attachments',
        'extra_payload',
        'delivered_at',
        'read_at',
        'failed_at',
    ];

    protected $casts = [
        'is_echo'       => 'boolean',
        'is_deleted'    => 'boolean',
        'attachments'   => 'array',
        'extra_payload' => 'array',
        'delivered_at'  => 'datetime',
        'read_at'       => 'datetime',
        'failed_at'     => 'datetime',
    ];

    public function contato()
    {
        return $this->belongsTo(SuriContato::class, 'suri_contato_id');
    }
}