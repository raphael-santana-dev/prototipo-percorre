<?php
namespace App\Modules\GestaoEducacional\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Student\Domain\Models\Student;
use App\Models\Turma; // Ajuste o namespace da Turma conforme o seu projeto

class AlunoAvaliacao extends Model
{
    use SoftDeletes;
    protected $table = 'aluno_avaliacoes';
    protected $fillable = ['periodo_id', 'student_id', 'turma_id', 'fase', 'status', 'data_resposta', 'hora_resposta'];

    public function periodo() { return $this->belongsTo(PeriodoAvaliacao::class); }
    public function student() { return $this->belongsTo(Student::class, 'student_id'); }
    public function turma() { return $this->belongsTo(Turma::class); }
    public function itens() { return $this->hasMany(AlunoAvaliacaoItem::class, 'aluno_avaliacao_id'); }
}