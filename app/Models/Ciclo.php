<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ciclo extends Model
{
    protected $fillable = [
        'nome', 'ano', 'semestre', 'data_inicio', 'data_fim', 'status', 'regras_pontuacao'
    ];

    protected $casts = [
        'data_inicio' => 'datetime',
        'data_fim' => 'datetime',
        'status' => 'boolean',
        'regras_pontuacao' => 'array',
    ];

    public function inscricoes()
    {
        return $this->hasMany(Inscricao::class);
    }

    public function campos()
    {
        return $this->hasMany(CampoFormulario::class)->orderBy('ordem');
    }

    public function cursos()
    {
        return $this->belongsToMany(Curso::class, 'ciclo_curso');
    }

    public function statusPipeline()
    {
        return $this->belongsToMany(StatusInscricao::class, 'ciclo_status_inscricao')
            ->withPivot('ordem')
            ->orderBy('pivot_ordem', 'asc');
    }
}
