<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Traits\RegistraAuditoria;
use App\Modules\Turno\Domain\Models\Turno;

class Curso extends Model
{
    use HasFactory, SoftDeletes, RegistraAuditoria;
    
    protected $fillable = [
        'nome', 'slug', 'status', 'turnos', 'permite_estado_diferente'
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
        return $this->belongsToMany(Turno::class, 'curso_turno');
    }

    /**
     * Retorna apenas os Turnos habilitados para este Curso dentro de uma Unidade específica.
     */
    public function turnosNaUnidade(?int $unidadeId = null): Collection
    {
        if ($unidadeId && Schema::hasTable('unidade_curso_turno')) {
            $idsTurnos = DB::table('unidade_curso_turno')
                ->where('unidade_id', $unidadeId)
                ->where('curso_id', $this->id)
                ->pluck('turno_id')
                ->toArray();

            if (!empty($idsTurnos)) {
                return Turno::whereIn('id', $idsTurnos)
                    ->where('status', true)
                    ->orderBy('horario_inicio')
                    ->orderBy('nome')
                    ->get();
            }

            // Se a tabela ternária já possui registros para este curso, mas nenhum nesta unidade, retorna vazio
            $cursoUsaMatrizTernaria = DB::table('unidade_curso_turno')
                ->where('curso_id', $this->id)
                ->exists();

            if ($cursoUsaMatrizTernaria) {
                return collect();
            }
        }

        $turnos = $this->relationLoaded('turnosVinculados')
            ? $this->turnosVinculados
            : $this->turnosVinculados()->where('status', true)->orderBy('horario_inicio')->get();

        return $turnos->sortBy('horario_inicio')->values();
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