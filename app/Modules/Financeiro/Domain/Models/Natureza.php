<?php

namespace App\Modules\Financeiro\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class Natureza extends Model
{
    use RegistraAuditoria;

    protected $table = 'naturezas';

    protected $fillable = [
        'codigo',
        'descricao',
        'disponivel_orcamento',
    ];

    protected $casts = [
        'disponivel_orcamento' => 'boolean',
    ];
}