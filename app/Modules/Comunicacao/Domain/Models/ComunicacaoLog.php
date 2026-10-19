<?php

namespace App\Modules\Comunicacao\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;
use App\Models\Inscricao;

class ComunicacaoLog extends Model
{
    use RegistraAuditoria;

    protected $table = 'comunicacao_logs';
    protected $guarded = [];

    protected $casts = [
        'anexos' => 'array',
        'data_agendamento' => 'datetime',
        'data_envio' => 'datetime',
        'aberto_em' => 'datetime',
        'ultima_abertura_em' => 'datetime',
        'clicado_em' => 'datetime',
        'ultimo_clique_em' => 'datetime',
    ];

    public function comunicado()
    {
        return $this->belongsTo(Comunicado::class);
    }

    public function inscricao()
    {
        return $this->belongsTo(Inscricao::class, 'inscricao_id');
    }

    public function eventos()
    {
        return $this->hasMany(ComunicacaoLogEvento::class, 'comunicacao_log_id')->latest();
    }
}