<?php

namespace App\Traits;

use Illuminate\Support\Facades\Schema;

trait FiltraPorVinculo
{
    /**
     * Scope local para isolamento de dados Multi-tenancy.
     * Uso nas queries: Model::apenasVinculosPermitidos()->get();
     */
    public function scopeApenasVinculosPermitidos($query)
    {
        $user = auth()->user();
        $tabela = $this->getTable(); 

        // Lê o nome do módulo definido no Model. Se não existir, usa o nome da tabela por padrão.
        $modulo = property_exists($this, 'moduloPermissao') ? $this->moduloPermissao : $tabela;

        // Checa a permissão exata: ex: 'estudantes.visao_global'
        if (!$user || $user->temVisaoGlobal($modulo)) {
            return $query;
        }

        // Coleta as chaves dos vínculos do usuário
        $unidadesIds = $user->unidades->pluck('id')->toArray();
        $cursosIds = $user->cursos->pluck('id')->toArray();
        $turnosIds = $user->turnos->pluck('id')->toArray();
        
        // Regra de Isolamento por Unidade
        if (Schema::hasColumn($tabela, 'unidade_id')) {
            if (count($unidadesIds) > 0) {
                $query->whereIn("$tabela.unidade_id", $unidadesIds);
            } else {
                $query->whereRaw('1 = 0'); // Trava de segurança total
            }
        }

        // Regra de Isolamento por Curso
        if (Schema::hasColumn($tabela, 'curso_id') && count($cursosIds) > 0) {
            $query->whereIn("$tabela.curso_id", $cursosIds);
        }

        // Regra de Isolamento por Turno
        if (Schema::hasColumn($tabela, 'turno_id') && count($turnosIds) > 0) {
            $query->whereIn("$tabela.turno_id", $turnosIds);
        }

        return $query;
    }
}