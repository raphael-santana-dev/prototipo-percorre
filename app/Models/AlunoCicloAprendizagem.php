<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class AlunoCicloAprendizagem extends Model
{
    use RegistraAuditoria;
    protected $table = 'aluno_ciclo_aprendizagem';
    protected $guarded = ['id'];

    public function ciclo()
    {
        return $this->belongsTo(CicloAprendizagem::class, 'ciclo_aprendizagem_id');
    }

    public function student()
    {
        // Ajuste o namespace do Student caso necessário
        return $this->belongsTo(\App\Modules\Student\Domain\Models\Student::class, 'student_id');
    }

    public function faseAtual()
    {
        return $this->belongsTo(CicloAprendizagemFase::class, 'fase_atual_id');
    }
}