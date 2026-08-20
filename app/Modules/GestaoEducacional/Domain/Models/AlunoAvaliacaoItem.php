<?php
namespace App\Modules\GestaoEducacional\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class AlunoAvaliacaoItem extends Model
{
    use RegistraAuditoria;
    protected $table = 'aluno_avaliacao_itens';
    protected $fillable = ['aluno_avaliacao_id', 'criterio_id', 'nivel_nps', 'aval_metas'];

    public function criterio() { return $this->belongsTo(CriterioAvaliacao::class); }
}