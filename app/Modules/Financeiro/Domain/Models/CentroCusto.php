<?php

namespace App\Modules\Financeiro\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class CentroCusto extends Model
{
    use RegistraAuditoria; // Mantemos o tracking de auditoria de alterações

    protected $table = 'centros_custo';

    protected $fillable = [
        'codigo',
        'nome',
        'disponivel_orcamento',
    ];

    protected $casts = [
        'disponivel_orcamento' => 'boolean',
    ];
}