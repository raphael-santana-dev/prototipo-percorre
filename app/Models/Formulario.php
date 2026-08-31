<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Formulario extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo', 'slug', 'descricao', 'status', 'tipo',
        'data_inicio', 'data_fim', 'acesso_livre', 
        'apenas_estudantes', 'roles_permitidas', 'users_permitidos',
        'unidades_permitidas', 'cursos_permitidos', 'turnos_permitidas', 'exigir_email'
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

    public function campos()
    {
        return $this->hasMany(CampoFormulario::class, 'formulario_id');
    }
    
    public function respostas()
    {
        return $this->hasMany(RespostaFormulario::class, 'formulario_id');
    }
}