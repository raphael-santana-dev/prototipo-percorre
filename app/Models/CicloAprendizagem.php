<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CicloAprendizagem extends Model
{
    use SoftDeletes;
    
    protected $table = 'ciclos_aprendizagem';
    protected $guarded = ['id'];
    protected $casts = ['data_inicio' => 'date', 'data_fim' => 'date', 'status' => 'boolean'];

    public function fases()
    {
        return $this->hasMany(CicloAprendizagemFase::class, 'ciclo_aprendizagem_id')->orderBy('ordem', 'asc');
    }

    public function acompanhamentosAlunos()
    {
        return $this->hasMany(AlunoCicloAprendizagem::class, 'ciclo_aprendizagem_id');
    }
}