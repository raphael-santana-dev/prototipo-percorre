<?php

namespace App\Modules\Turno\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class Turno extends Model
{
    protected $fillable = [
        'nome',
        'horario_inicio',
        'horario_fim',
    ];

    // Opcional: formatação automática para as views futuramente
    protected $casts = [
        'horario_inicio' => 'datetime:H:i',
        'horario_fim' => 'datetime:H:i',
    ];
}