<?php

namespace App\Modules\Turno\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class Turno extends Model
{
    use RegistraAuditoria;
    protected $fillable = [
        'nome',
        'horario_inicio',
        'horario_fim',
        'slug'
    ];

    // Opcional: formatação automática para as views futuramente
    protected $casts = [
        'horario_inicio' => 'datetime:H:i',
        'horario_fim' => 'datetime:H:i',
    ];
}