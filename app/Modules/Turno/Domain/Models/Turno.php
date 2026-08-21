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
        'slug',
        'status'
    ];

    // Opcional: formatação automática para as views futuramente
    protected $casts = [
        'horario_inicio' => 'datetime:H:i',
        'horario_fim' => 'datetime:H:i',
    ];

    // Relacionamento com os Ciclos (Processos Seletivos)
    public function ciclos()
    {
        return $this->belongsToMany(\App\Models\Ciclo::class, 'ciclo_turno', 'turno_id', 'ciclo_id');
    }
}