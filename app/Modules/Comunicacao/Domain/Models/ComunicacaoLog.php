<?php

namespace App\Modules\Comunicacao\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class ComunicacaoLog extends Model
{
    
    use RegistraAuditoria;
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