<?php

namespace App\Modules\Comunicacao\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class EsteiraEtapa extends Model
{
    protected $table = 'comunicacao_esteira_etapas';

    protected $fillable = [
        'esteira_id',
        'ordem',
        'nome',
        'template_id',
        'canal_disparo',
        'tipo_acao',
        'tipo_agendamento',
        'data_disparo',
        'atraso_valor',
        'atraso_unidade',
        'filtro_engajamento_anterior',
        'condicoes_etapa',
    ];

    protected $casts = [
        'data_disparo'    => 'datetime',
        'atraso_valor'    => 'integer',
        'ordem'           => 'integer',
        'condicoes_etapa' => 'array',
    ];

    public function esteira()
    {
        return $this->belongsTo(EsteiraComunicacao::class, 'esteira_id');
    }

    public function template()
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }

    public function disparos()
    {
        return $this->hasMany(EsteiraDisparo::class, 'etapa_id');
    }
}