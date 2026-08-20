<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Student\Domain\Models\Student;
use App\Traits\RegistraAuditoria;

class Matricula extends Model
{
    use SoftDeletes;
    use RegistraAuditoria;
    
    protected $table = 'matriculas';
    
    protected $fillable = [
        'numero_matricula', 'student_id', 'curso_id', 'unidade_id', 'turno_id', 'status'
    ];

    // Relacionamento com o Estudante
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    // Relacionamento com Turmas (Enturmação)
    public function turmas()
    {
        return $this->belongsToMany(Turma::class, 'matricula_turma', 'matricula_id', 'turma_id');
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
}