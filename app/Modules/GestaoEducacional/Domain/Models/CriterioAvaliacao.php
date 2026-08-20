<?php
namespace App\Modules\GestaoEducacional\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CriterioAvaliacao extends Model
{
    use SoftDeletes;
    protected $table = 'criterios_avaliacao';
    protected $fillable = ['codigo', 'nome', 'status'];
}