<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class ImportacaoConfig extends Model
{
    use RegistraAuditoria;
    protected $fillable = ['coluna', 'model_class', 'campo_busca', 'auto_cadastro', 'payload_padrao'];

    protected $casts = [
        'auto_cadastro' => 'boolean',
        'payload_padrao' => 'array'
    ];
}