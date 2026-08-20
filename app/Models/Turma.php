<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Turma extends Model
{
    use SoftDeletes;
    
    protected $table = 'turmas';
    
    protected $fillable = [
        'nome', 'ano', 'ciclo_id', 'curso_id', 'unidade_id', 'turno_id', 'status'
    ];

    // Relacionamento com Professores
    public function professores()
    {
        return $this->belongsToMany(User::class, 'professor_turma', 'turma_id', 'user_id');
    }

    // Relacionamento com Matrículas
    public function matriculas()
    {
        return $this->belongsToMany(Matricula::class, 'matricula_turma', 'turma_id', 'matricula_id');
    }

    // --- ADICIONE ESTE BLOCO AQUI ---
    // Relacionamento com o Curso
    public function curso()
    {
        return $this->belongsTo(Curso::class, 'curso_id');
    }
}