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
        'apenas_estudantes', 'roles_permitidas', 'users_permitidos'
    ];

    protected $casts = [
        'status' => 'boolean',
        'acesso_livre' => 'boolean',
        'apenas_estudantes' => 'boolean',
        'roles_permitidas' => 'array',
        'users_permitidos' => 'array',
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