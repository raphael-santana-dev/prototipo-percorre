<?php

namespace App\Modules\Comunicacao\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class ComunicacaoLog extends Model
{
    protected $table = 'comunicacao_logs';
    protected $guarded = [];
    protected $casts = [
        'anexos' => 'array',
        'data_agendamento' => 'datetime',
        'data_envio' => 'datetime',
    ];

    public function comunicado()
    {
        return $this->belongsTo(Comunicado::class);
    }
}