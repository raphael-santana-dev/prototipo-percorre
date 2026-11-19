<?php

namespace App\Traits;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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

        $combinacoes = method_exists($user, 'obterCombinacoesVinculoAtivas')
            ? $user->obterCombinacoesVinculoAtivas()
            : [];

        // Se possui combinações exatas cadastradas na tabela user_vinculos_academicos
        if (!empty($combinacoes)) {
            $temUnidade = Schema::hasColumn($tabela, 'unidade_id');
            $temCurso   = Schema::hasColumn($tabela, 'curso_id');
            $temTurno   = Schema::hasColumn($tabela, 'turno_id');

            // Caso 1: Tabelas com a trinca completa (ex: inscricoes, ofertas_vagas)
            if ($temUnidade && $temCurso && $temTurno) {
                return $query->where(function ($subQuery) use ($tabela, $combinacoes) {
                    foreach ($combinacoes as $comb) {
                        $subQuery->orWhere(function ($qPar) use ($tabela, $comb) {
                            $qPar->where("{$tabela}.unidade_id", $comb['unidade_id'])
                                 ->where("{$tabela}.curso_id", $comb['curso_id'])
                                 ->where("{$tabela}.turno_id", $comb['turno_id']);
                        });
                    }
                });
            }

            // Caso 2: Tabela de Estudantes (students) — filtra pelas inscrições do estudante que batem exatamente com as combinações A ou B
            if ($tabela === 'students') {
                return $query->whereExists(function ($sub) use ($combinacoes) {
                    $sub->select(DB::raw(1))
                        ->from('inscricoes')
                        ->whereColumn('inscricoes.student_id', 'students.id')
                        ->whereNull('inscricoes.deleted_at')
                        ->where(function ($qComb) use ($combinacoes) {
                            foreach ($combinacoes as $comb) {
                                $qComb->orWhere(function ($qPar) use ($comb) {
                                    $qPar->where('inscricoes.unidade_id', $comb['unidade_id'])
                                         ->where('inscricoes.curso_id', $comb['curso_id'])
                                         ->where('inscricoes.turno_id', $comb['turno_id']);
                                });
                            }
                        });
                });
            }

            // Caso 3: Outras tabelas parciais
            $unidadesIds = array_values(array_unique(array_column($combinacoes, 'unidade_id')));
            $cursosIds   = array_values(array_unique(array_column($combinacoes, 'curso_id')));
            $turnosIds   = array_values(array_unique(array_column($combinacoes, 'turno_id')));

            if ($temUnidade) $query->whereIn("{$tabela}.unidade_id", $unidadesIds);
            if ($temCurso)   $query->whereIn("{$tabela}.curso_id", $cursosIds);
            if ($temTurno)   $query->whereIn("{$tabela}.turno_id", $turnosIds);

            return $query;
        }

        // Se o usuário está marcado com "Travar por vínculo" e não possui nenhuma combinação ativa (ou todas expiraram), bloqueia listagem
        if (method_exists($user, 'possuiTravaPorVinculoAtiva') && $user->possuiTravaPorVinculoAtiva()) {
            return $query->whereRaw('1 = 0');
        }

        return $query;
    }
}