<?php

namespace App\Modules\Comunicacao\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Ciclo;
use App\Models\Formulario;

class EsteiraComunicacao extends Model
{
    protected $table = 'comunicacao_esteiras';

    protected $fillable = [
        'nome',
        'descricao',
        'status',
        'publico_alvo',
        'ciclo_id',
        'formulario_id',
        'condicoes',
        'condicao_saida',
        'revalidar_condicoes_no_envio',
        'apenas_horario_comercial',
        'criado_por',
    ];

    protected $casts = [
        'status'                       => 'boolean',
        'condicoes'                    => 'array',
        'condicao_saida'               => 'array',
        'revalidar_condicoes_no_envio' => 'boolean',
        'apenas_horario_comercial'     => 'boolean',
    ];

    public function etapas()
    {
        return $this->hasMany(EsteiraEtapa::class, 'esteira_id')->orderBy('ordem', 'asc');
    }

    public function disparos()
    {
        return $this->hasMany(EsteiraDisparo::class, 'esteira_id');
    }

    public function ciclo()
    {
        return $this->belongsTo(Ciclo::class, 'ciclo_id');
    }

    public function formulario()
    {
        return $this->belongsTo(Formulario::class, 'formulario_id');
    }
}