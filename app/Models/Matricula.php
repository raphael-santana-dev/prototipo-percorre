<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Student\Domain\Models\Student;

class Matricula extends Model
{
    use SoftDeletes;
    
    protected $table = 'matriculas';
    
    protected $fillable = [
        'numero_matricula', 'student_id', 'curso_id', 'unidade_id', 'turno_id', 'status'
    ];

    // Relacionamento com o Estudante
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    // Relacionamento com Turmas
    public function turmas()
    {
        return $this->belongsToMany(Turma::class, 'matricula_turma', 'matricula_id', 'turma_id');
    }
}