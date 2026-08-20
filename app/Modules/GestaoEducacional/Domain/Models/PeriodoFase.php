<?php
namespace App\Modules\GestaoEducacional\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodoFase extends Model
{
    protected $table = 'periodo_fases';
    protected $fillable = ['periodo_id', 'fase', 'responsavel'];
}