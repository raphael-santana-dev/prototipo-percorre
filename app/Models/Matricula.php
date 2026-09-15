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

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function turmas()
    {
        return $this->belongsToMany(Turma::class, 'matricula_turma', 'matricula_id', 'turma_id');
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'curso_id');
    }

    public function unidade()
    {
        return $this->belongsTo(\App\Modules\Unidade\Domain\Models\Unidade::class, 'unidade_id');
    }

    public function turno()
    {
        return $this->belongsTo(\App\Modules\Turno\Domain\Models\Turno::class, 'turno_id');
    }
}