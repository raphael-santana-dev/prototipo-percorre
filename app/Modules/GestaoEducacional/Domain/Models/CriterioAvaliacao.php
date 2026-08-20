<?php
namespace App\Modules\GestaoEducacional\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\RegistraAuditoria;

class CriterioAvaliacao extends Model
{
    use SoftDeletes;
    use RegistraAuditoria;
    protected $table = 'criterios_avaliacao';
    protected $fillable = ['codigo', 'nome', 'status'];
}