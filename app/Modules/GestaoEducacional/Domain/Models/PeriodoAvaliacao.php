<?php
namespace App\Modules\GestaoEducacional\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\RegistraAuditoria;

class PeriodoAvaliacao extends Model
{
    use SoftDeletes;
    use RegistraAuditoria;
    protected $table = 'periodos_avaliacao';
    protected $fillable = ['ano', 'ciclo', 'data_inicio', 'data_fim', 'status', 'trava_fases'];

    public function fases() {
        return $this->hasMany(PeriodoFase::class, 'periodo_id');
    }

    public function criterios() {
        return $this->belongsToMany(CriterioAvaliacao::class, 'periodo_criterios', 'periodo_id', 'criterio_id');
    }
}