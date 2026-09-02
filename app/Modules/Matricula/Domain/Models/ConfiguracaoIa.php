<?php

namespace App\Modules\Matricula\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracaoIa extends Model
{
    protected $table = 'configuracoes_ia';
    protected $guarded = ['id'];
}