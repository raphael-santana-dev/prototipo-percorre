<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\RegistraAuditoria;

class Formulario extends Model
{
    use HasFactory;
    use SoftDeletes;
    use RegistraAuditoria;
    

    protected $fillable = [
        'titulo', 'slug', 'descricao', 'status', 'tipo',
        'data_inicio', 'data_fim', 'acesso_livre', 
        'apenas_estudantes', 'roles_permitidas', 'users_permitidos',
        'unidades_permitidas', 'cursos_permitidos', 'turnos_permitidas', 'exigir_email',
        'ciclo_id', 
        'unidade_id', 
        'curso_id', 
        'ciclo_aprendizagem_fase_id'
    ];

    protected $casts = [
        'status' => 'boolean',
        'acesso_livre' => 'boolean',
        'apenas_estudantes' => 'boolean',
        'exigir_email' => 'boolean',
        'roles_permitidas' => 'array',
        'users_permitidos' => 'array',
        'unidades_permitidas' => 'array',
        'cursos_permitidos' => 'array',
        'turnos_permitidas' => 'array',
        'data_inicio' => 'datetime',
        'data_fim' => 'datetime',
    ];

    public function faseAprendizagem()
    {
        return $this->belongsTo(CicloAprendizagemFase::class, 'ciclo_aprendizagem_fase_id');
    }

    public function cicloSeletivo()
    {
        return $this->belongsTo(Ciclo::class, 'ciclo_id');
    }

    public function unidade()
    {
        return $this->belongsTo(\App\Modules\Unidade\Domain\Models\Unidade::class, 'unidade_id');
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'curso_id');
    }

    public function campos()
    {
        return $this->hasMany(CampoFormulario::class, 'formulario_id');
    }
    
    public function respostas()
    {
        return $this->hasMany(RespostaFormulario::class, 'formulario_id');
    }
}