<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampoFormulario extends Model
{
    protected $fillable = [
        'ciclo_id', 'etapa', 'ordem', 'label', 'name', 'tipo', 'largura', 
        'subtipo', 'tamanho_min', 'tamanho_max', 'regex_mascara', 'opcoes', 
        'obrigatorio', 'regras_validacao', 'depende_de', 'depende_operador', 'depende_valor'
    ];

    protected $casts = [
        'opcoes' => 'array', // Converte o JSON do banco para Array no PHP
        'obrigatorio' => 'boolean',
    ];

    public function ciclo()
    {
        return $this->belongsTo(Ciclo::class);
    }
}