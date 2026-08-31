<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportacaoConfig extends Model
{
    protected $fillable = ['coluna', 'model_class', 'campo_busca', 'auto_cadastro', 'payload_padrao'];

    protected $casts = [
        'auto_cadastro' => 'boolean',
        'payload_padrao' => 'array'
    ];
}