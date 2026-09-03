<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class ConfiguracaoGeral extends Model
{
    use RegistraAuditoria;

    protected $table = 'configuracoes_gerais';
    protected $fillable = ['chave', 'valor', 'grupo'];
}