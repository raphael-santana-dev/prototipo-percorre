<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuriAtendimento extends Model
{
    protected $table = 'suri_atendimentos';

    protected $fillable = [
        'event_id',
        'suri_contato_id',
        'inscricao_id',
        'department_id',
        'attendant_id',
        'attendant_name',
        'attendant_email',
        'attendance_time_raw',
        'duracao_segundos',
        'user_tags',
        'messages',
        'finalizado_em',
    ];

    protected $casts = [
        'user_tags'     => 'array',
        'messages'      => 'array',
        'finalizado_em' => 'datetime',
    ];

    public function contato()
    {
        return $this->belongsTo(SuriContato::class, 'suri_contato_id');
    }
}