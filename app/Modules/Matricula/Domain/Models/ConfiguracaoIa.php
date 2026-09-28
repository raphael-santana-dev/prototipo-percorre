<?php

namespace App\Modules\Matricula\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class ConfiguracaoIa extends Model
{
    use RegistraAuditoria;
    protected $table = 'configuracao_ias';
    protected $guarded = ['id'];
}