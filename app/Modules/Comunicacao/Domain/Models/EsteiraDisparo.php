<?php

namespace App\Modules\Comunicacao\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class EsteiraDisparo extends Model
{
    protected $table = 'comunicacao_esteira_disparos';

    protected $fillable = [
        'esteira_id',
        'etapa_id',
        'destinatario_email',
        'destinatario_nome',
        'entidade_tipo',
        'entidade_id',
        'comunicado_id',
        'status',
        'motivo_status',
        'enviado_em',
    ];

    protected $casts = [
        'enviado_em' => 'datetime',
    ];

    public function esteira()
    {
        return $this->belongsTo(EsteiraComunicacao::class, 'esteira_id');
    }

    public function etapa()
    {
        return $this->belongsTo(EsteiraEtapa::class, 'etapa_id');
    }

    public function comunicado()
    {
        return $this->belongsTo(Comunicado::class, 'comunicado_id');
    }
}