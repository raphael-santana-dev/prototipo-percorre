<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Unidade\Domain\Models\Unidade;
use App\Modules\Turno\Domain\Models\Turno;

class Curso extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'nome', 'slug', 'status', 'turnos', 'min_idade', 'max_idade', 'permite_estado_diferente'
    ];

    protected $casts = [
        'turnos' => 'array',
        'permite_estado_diferente' => 'boolean',
    ];

    public function unidades()
    {
        return $this->belongsToMany(\App\Modules\Unidade\Domain\Models\Unidade::class, 'curso_unidade');
    }

    public function turnosVinculados() 
    {
        return $this->belongsToMany(\App\Modules\Turno\Domain\Models\Turno::class, 'curso_turno');
    }

    public function ciclos()
    {
        return $this->belongsToMany(Ciclo::class, 'ciclo_curso');
    }

    public function inscricoes()
    {
        return $this->hasMany(Inscricao::class, 'curso_id');
    }
}