<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait AvaliaCondicoesVisibilidade
{
    protected function normalizarTextoComparacao($valor): string
    {
        $str = mb_strtolower(trim((string) $valor), 'UTF-8');
        return Str::ascii($str);
    }

    /**
     * Obtém os grupos de regras de visibilidade do campo (suporta o novo formato avançado JSON
     * em configuracoes['condicoes_visibilidade'] e mantém retrocompatibilidade com depende_de).
     */
    protected function extrairGruposCondicionaisDoCampo($campo): array
    {
        $cfg = is_string($campo->configuracoes)
            ? (json_decode($campo->configuracoes, true) ?? [])
            : ($campo->configuracoes ?? []);

        $cond = $cfg['condicoes_visibilidade'] ?? null;

        if (is_array($cond) && !empty($cond['grupos'])) {
            return $cond['grupos'];
        }

        $op = (string) ($campo->depende_operador ?: '=');
        $temValor = ($campo->depende_valor !== null && $campo->depende_valor !== '')
            || in_array($op, ['empty', 'not_empty'], true);

        if (!empty($campo->depende_de) && $temValor) {
            return [
                [
                    'conector' => 'AND',
                    'regras'   => [
                        [
                            'conector'  => 'AND',
                            'campo'     => (string) $campo->depende_de,
                            'operador'  => $op,
                            'valor'     => (string) ($campo->depende_valor ?? ''),
                            'valor_max' => (string) ($cfg['depende_valor_max'] ?? ''),
                        ],
                    ],
                ],
            ];
        }

        return [];
    }

    /**
     * Avalia se um campo dinâmico está visível no momento, considerando:
     * - Árvore de Grupos (AND / OR) e Regras internas (AND / OR)
     * - Todos os 15 operadores de comparação
     * - Visibilidade recursiva (se o campo gatilho também for dinâmico e estiver oculto, retorna false)
     */
    public function campoEstaVisivel($campo, array $visitados = []): bool
    {
        $grupos = $this->extrairGruposCondicionaisDoCampo($campo);

        if (empty($grupos)) {
            return true;
        }

        // Proteção contra dependência circular (A depende de B e B depende de A)
        if (in_array($campo->name, $visitados, true)) {
            return true;
        }
        $visitados[] = $campo->name;

        $resultadoFinal = null;

        foreach ($grupos as $grupo) {
            $regras = is_array($grupo['regras'] ?? null) ? $grupo['regras'] : [];
            $resultadoGrupo = null;

            foreach ($regras as $regra) {
                $passou = $this->avaliarRegraVisibilidadeIndividual($regra, $visitados);
                $conectorRegra = strtoupper((string) ($regra['conector'] ?? 'AND')) === 'OR' ? 'OR' : 'AND';

                if ($resultadoGrupo === null) {
                    $resultadoGrupo = $passou;
                } else {
                    $resultadoGrupo = ($conectorRegra === 'OR')
                        ? ($resultadoGrupo || $passou)
                        : ($resultadoGrupo && $passou);
                }
            }

            $resultadoGrupo = $resultadoGrupo ?? false;
            $conectorGrupo = strtoupper((string) ($grupo['conector'] ?? 'AND')) === 'OR' ? 'OR' : 'AND';

            if ($resultadoFinal === null) {
                $resultadoFinal = $resultadoGrupo;
            } else {
                $resultadoFinal = ($conectorGrupo === 'OR')
                    ? ($resultadoFinal || $resultadoGrupo)
                    : ($resultadoFinal && $resultadoGrupo);
            }
        }

        return $resultadoFinal ?? true;
    }

    /**
     * Converte strings de data (YYYY-MM-DD ou DD/MM/YYYY, com ou sem hora) em Timestamp UTC.
     * Retorna null se a string não for uma data válida.
     */
    protected function converterParaTimestampSeData($valor): ?int
    {
        $s = trim((string) $valor);
        if ($s === '') return null;

        // ISO: YYYY-MM-DD ou YYYY-MM-DDTHH:MM
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[T\s](\d{2}):(\d{2}))?/', $s, $m)) {
            return gmmktime((int) ($m[4] ?? 0), (int) ($m[5] ?? 0), 0, (int) $m[2], (int) $m[3], (int) $m[1]);
        }

        // BR: DD/MM/YYYY ou DD/MM/YYYY HH:MM
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})(?:\s+(\d{2}):(\d{2}))?/', $s, $m)) {
            return gmmktime((int) ($m[4] ?? 0), (int) ($m[5] ?? 0), 0, (int) $m[2], (int) $m[1], (int) $m[3]);
        }

        return null;
    }

    /**
     * Avalia uma linha individual de condição dentro de um grupo.
     */
    protected function avaliarRegraVisibilidadeIndividual(array $regra, array $visitados = []): bool
    {
        $nomeCampoGatilho = trim((string) ($regra['campo'] ?? ''));
        if ($nomeCampoGatilho === '') {
            return false;
        }

        // Se o campo gatilho for outro campo dinâmico que está oculto na tela, a condição não é atendida
        if (!empty($this->camposDinamicos)) {
            $campoPai = collect($this->camposDinamicos)->firstWhere('name', $nomeCampoGatilho);
            if ($campoPai && !$this->campoEstaVisivel($campoPai, $visitados)) {
                return false;
            }
        }

        $valorGatilho = array_key_exists($nomeCampoGatilho, $this->respostas ?? [])
            ? $this->respostas[$nomeCampoGatilho]
            : (property_exists($this, $nomeCampoGatilho) ? $this->{$nomeCampoGatilho} : null);

        $op = (string) ($regra['operador'] ?? '=');
        $tgt = $this->normalizarTextoComparacao($regra['valor'] ?? '');
        $tgtMax = $this->normalizarTextoComparacao($regra['valor_max'] ?? '');

        // Tratamento quando o campo gatilho é um Array (ex: Checkboxes)
        if (is_array($valorGatilho)) {
            $arrayValores = array_values(array_filter(
                array_map(fn($v) => $this->normalizarTextoComparacao($v), $valorGatilho),
                fn($v) => $v !== ''
            ));

            if ($op === 'empty') return empty($arrayValores);
            if ($op === 'not_empty') return !empty($arrayValores);
            if (empty($arrayValores)) return false;

            $arrayAlvos = array_values(array_filter(
                array_map(fn($v) => $this->normalizarTextoComparacao($v), explode(',', (string) ($regra['valor'] ?? ''))),
                fn($v) => $v !== ''
            ));

            return match ($op) {
                '='            => in_array($tgt, $arrayValores, true),
                '!='           => !in_array($tgt, $arrayValores, true),
                'contains'     => $tgt !== '' && collect($arrayValores)->contains(fn($item) => str_contains($item, $tgt)),
                'not_contains' => $tgt !== '' && collect($arrayValores)->every(fn($item) => !str_contains($item, $tgt)),
                'in'           => !empty(array_intersect($arrayValores, $arrayAlvos)),
                'not_in'       => empty(array_intersect($arrayValores, $arrayAlvos)),
                default        => false,
            };
        }

        // Tratamento para valores escalares (Texto, Número, Data, Select, Radio)
        $val = $this->normalizarTextoComparacao($valorGatilho);

        if ($op === 'empty') return $val === '';
        if ($op === 'not_empty') return $val !== '';
        if ($val === '') return false;

        // Comparação inteligente de Datas (aceita YYYY-MM-DD e DD/MM/YYYY indiferentemente)
        $dtVal = $this->converterParaTimestampSeData($val);
        $dtTgt = $this->converterParaTimestampSeData($tgt);
        $dtMax = $this->converterParaTimestampSeData($tgtMax);

        if ($dtVal !== null && ($dtTgt !== null || in_array($op, ['in', 'not_in'], true))) {
            $arrayAlvosDatas = array_values(array_filter(
                array_map(fn($v) => $this->converterParaTimestampSeData($v), explode(',', (string) ($regra['valor'] ?? ''))),
                fn($v) => $v !== null
            ));

            return match ($op) {
                '='       => $dtTgt !== null && $dtVal === $dtTgt,
                '!='      => $dtTgt !== null && $dtVal !== $dtTgt,
                '>'       => $dtTgt !== null && $dtVal > $dtTgt,
                '>='      => $dtTgt !== null && $dtVal >= $dtTgt,
                '<'       => $dtTgt !== null && $dtVal < $dtTgt,
                '<='      => $dtTgt !== null && $dtVal <= $dtTgt,
                'between' => $dtTgt !== null && $dtMax !== null && $dtVal >= min($dtTgt, $dtMax) && $dtVal <= max($dtTgt, $dtMax),
                'in'      => in_array($dtVal, $arrayAlvosDatas, true),
                'not_in'  => !in_array($dtVal, $arrayAlvosDatas, true),
                default   => false,
            };
        }

        $numVal = is_numeric(str_replace(',', '.', $val)) ? (float) str_replace(',', '.', $val) : null;
        $numTgt = is_numeric(str_replace(',', '.', $tgt)) ? (float) str_replace(',', '.', $tgt) : null;
        $numMax = is_numeric(str_replace(',', '.', $tgtMax)) ? (float) str_replace(',', '.', $tgtMax) : null;

        $arrayAlvos = array_values(array_filter(
            array_map(fn($v) => $this->normalizarTextoComparacao($v), explode(',', (string) ($regra['valor'] ?? ''))),
            fn($v) => $v !== ''
        ));

        return match ($op) {
            '='            => $val === $tgt,
            '!='           => $val !== $tgt,
            'contains'     => $tgt !== '' && str_contains($val, $tgt),
            'not_contains' => $tgt !== '' && !str_contains($val, $tgt),
            'starts_with'  => $tgt !== '' && str_starts_with($val, $tgt),
            'ends_with'    => $tgt !== '' && str_ends_with($val, $tgt),
            'in'           => in_array($val, $arrayAlvos, true),
            'not_in'       => !in_array($val, $arrayAlvos, true),
            '>'            => $numVal !== null && $numTgt !== null && $numVal > $numTgt,
            '>='           => $numVal !== null && $numTgt !== null && $numVal >= $numTgt,
            '<'            => $numVal !== null && $numTgt !== null && $numVal < $numTgt,
            '<='           => $numVal !== null && $numTgt !== null && $numVal <= $numTgt,
            'between'      => $numVal !== null && $numTgt !== null && $numMax !== null
                              && $numVal >= min($numTgt, $numMax)
                              && $numVal <= max($numTgt, $numMax),
            default        => false,
        };
    }

    /**
     * Limpa os valores de campos condicionais (simples ou avançados) que deixaram de estar visíveis.
     */
    protected function limparRespostasCondicionaisInativas(): void
    {
        if (empty($this->camposDinamicos)) {
            return;
        }

        foreach ($this->camposDinamicos->whereNotIn('tipo', ['config', 'html', 'divider', 'media', 'spacer', 'link']) as $campo) {
            $grupos = $this->extrairGruposCondicionaisDoCampo($campo);

            if (!empty($grupos) && !$this->campoEstaVisivel($campo)) {
                $cfgCampo = is_string($campo->configuracoes)
                    ? (json_decode($campo->configuracoes, true) ?? [])
                    : ($campo->configuracoes ?? []);

                $valorVazio = (in_array($campo->tipo, ['check', 'matriz', 'social'], true) || ($campo->tipo === 'file' && !empty($cfgCampo['aceita_multiplos'])))
                    ? []
                    : '';

                if (isset($this->respostas[$campo->name]) && $this->respostas[$campo->name] !== $valorVazio) {
                    $this->respostas[$campo->name] = $valorVazio;
                    $this->resetErrorBag('respostas.' . $campo->name);
                }

                if (isset($this->uploads[$campo->name])) {
                    unset($this->uploads[$campo->name]);
                    $this->resetErrorBag('uploads.' . $campo->name);
                }
            }
        }
    }
}