<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\RegistraAuditoria;

class Turma extends Model
{
    use SoftDeletes;
    use RegistraAuditoria;
    
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

    // Relacionamento com Curso
    public function curso()
    {
        return $this->belongsTo(Curso::class, 'curso_id');
    }

    // Relacionamento com Unidade
    public function unidade()
    {
        return $this->belongsTo(\App\Modules\Unidade\Domain\Models\Unidade::class, 'unidade_id');
    }

    // Relacionamento com Turno
    public function turno()
    {
        return $this->belongsTo(\App\Modules\Turno\Domain\Models\Turno::class, 'turno_id');
    }

    // Relacionamento com Ciclo Letivo
    public function ciclo()
    {
        return $this->belongsTo(Ciclo::class, 'ciclo_id');
    }
}