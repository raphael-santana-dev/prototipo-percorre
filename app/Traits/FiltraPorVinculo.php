<?php

namespace App\Traits;

use Illuminate\Support\Facades\Schema;

trait FiltraPorVinculo
{
    public function scopeApenasVinculosPermitidos($query)
    {
        $user = auth()->user();
        $tabela = $this->getTable(); 

        $modulo = property_exists($this, 'moduloPermissao') ? $this->moduloPermissao : $tabela;

        if (!$user || $user->temVisaoGlobal($modulo)) {
            return $query;
        }

        $unidadesIds = $user->unidades->pluck('id')->toArray();
        $cursosIds = $user->cursos->pluck('id')->toArray();
        $turnosIds = $user->turnos->pluck('id')->toArray();
        
        if (Schema::hasColumn($tabela, 'unidade_id')) {
            if (count($unidadesIds) > 0) {
                $query->whereIn("$tabela.unidade_id", $unidadesIds);
            } else {
                $query->whereRaw('1 = 0'); 
            }
        }

        if (Schema::hasColumn($tabela, 'curso_id') && count($cursosIds) > 0) {
            $query->whereIn("$tabela.curso_id", $cursosIds);
        }

        if (Schema::hasColumn($tabela, 'turno_id') && count($turnosIds) > 0) {
            $query->whereIn("$tabela.turno_id", $turnosIds);
        }

        return $query;
    }
}