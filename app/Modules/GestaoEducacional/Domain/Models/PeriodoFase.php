<?php
namespace App\Modules\GestaoEducacional\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class PeriodoFase extends Model
{
    use RegistraAuditoria;
    protected $table = 'periodo_fases';
    protected $fillable = ['periodo_id', 'fase', 'responsavel'];
}