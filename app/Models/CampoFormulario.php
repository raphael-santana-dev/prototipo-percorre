<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class CampoFormulario extends Model
{
    use RegistraAuditoria;
    protected $fillable = [
        'ciclo_id', 'formulario_id', 'etapa', 'ordem', 'label', 'name', 'tipo', 'largura', 
        'subtipo', 'tamanho_min', 'tamanho_max', 'regex_mascara', 'opcoes', 
        'obrigatorio', 'regras_validacao', 'depende_de', 'depende_operador', 'depende_valor','configuracoes'
    ];

    protected $casts = [
        'opcoes' => 'array',
        'obrigatorio' => 'boolean',
        'configuracoes' => 'array',
    ];

    public function ciclo()
    {
        return $this->belongsTo(Ciclo::class);
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
     * Verifica no Backend se o campo está visível diante das respostas enviadas pelo candidato.
     */
    public function estaVisivelParaRespostas(array $respostas, array $dadosFixos = []): bool
    {
        $cfg = is_string($this->configuracoes) ? json_decode($this->configuracoes, true) : ($this->configuracoes ?? []);
        $cond = $cfg['condicoes_visibilidade'] ?? null;

        $grupos = [];
        if (is_array($cond) && !empty($cond['grupos'])) {
            $grupos = $cond['grupos'];
        } elseif (!empty($this->depende_de)) {
            $grupos = [[
                'conector' => 'AND',
                'regras'   => [[
                    'conector'  => 'AND',
                    'campo'     => $this->depende_de,
                    'operador'  => $this->depende_operador ?: '=',
                    'valor'     => (string) $this->depende_valor,
                    'valor_max' => (string) ($cfg['depende_valor_max'] ?? ''),
                ]],
            ]];
        }

        if (empty($grupos)) {
            return true;
        }

        $norm = function ($v): string {
            $s = mb_strtolower(trim((string) $v), 'UTF-8');
            $u = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
            return $u !== false ? $u : $s;
        };

        $avaliar = function (array $r) use ($respostas, $dadosFixos, $norm): bool {
            $campo = $r['campo'] ?? '';
            if ($campo === '') return false;

            $op = $r['operador'] ?? '=';
            $atual = $respostas[$campo] ?? $dadosFixos[$campo] ?? '';

            if (is_array($atual)) {
                $atual = implode(',', $atual);
            }

            $valNorm = $norm($atual);
            $alvoNorm = $norm($r['valor'] ?? '');
            $maxNorm = $norm($r['valor_max'] ?? '');

            if ($op === 'empty') return $valNorm === '';
            if ($op === 'not_empty') return $valNorm !== '';
            if ($valNorm === '') return false;

            // Comparação inteligente de Datas (aceita YYYY-MM-DD e DD/MM/YYYY indiferentemente)
            $dtVal = $this->converterParaTimestampSeData($val);
            $dtTgt = $this->converterParaTimestampSeData($tgt);
            $dtMax = $this->converterParaTimestampSeData($tgtMax);

            if ($dtVal !== null && $dtTgt !== null) {
                return match ($op) {
                    '='       => $dtVal === $dtTgt,
                    '!='      => $dtVal !== $dtTgt,
                    '>'       => $dtVal > $dtTgt,
                    '>='      => $dtVal >= $dtTgt,
                    '<'       => $dtVal < $dtTgt,
                    '<='      => $dtVal <= $dtTgt,
                    'between' => $dtMax !== null && $dtVal >= min($dtTgt, $dtMax) && $dtVal <= max($dtTgt, $dtMax),
                    default   => false,
                };
            }

            $atuaisArr = array_filter(array_map($norm, explode(',', (string) $atual)), fn($i) => $i !== '');
            $alvosArr  = array_filter(array_map($norm, explode(',', (string) ($r['valor'] ?? ''))), fn($i) => $i !== '');

            $numVal  = is_numeric(str_replace(',', '.', $valNorm)) ? (float) str_replace(',', '.', $valNorm) : null;
            $numAlvo = is_numeric(str_replace(',', '.', $alvoNorm)) ? (float) str_replace(',', '.', $alvoNorm) : null;
            $numMax  = is_numeric(str_replace(',', '.', $maxNorm)) ? (float) str_replace(',', '.', $maxNorm) : null;

            return match ($op) {
                '='            => $valNorm === $alvoNorm || in_array($alvoNorm, $atuaisArr, true),
                '!='           => $valNorm !== $alvoNorm && !in_array($alvoNorm, $atuaisArr, true),
                'contains'     => $alvoNorm !== '' && str_contains($valNorm, $alvoNorm),
                'not_contains' => $alvoNorm !== '' && !str_contains($valNorm, $alvoNorm),
                'starts_with'  => $alvoNorm !== '' && str_starts_with($valNorm, $alvoNorm),
                'ends_with'    => $alvoNorm !== '' && str_ends_with($valNorm, $alvoNorm),
                'in'           => !empty(array_intersect($atuaisArr, $alvosArr)) || in_array($valNorm, $alvosArr, true),
                'not_in'       => empty(array_intersect($atuaisArr, $alvosArr)) && !in_array($valNorm, $alvosArr, true),
                '>'            => $numVal !== null && $numAlvo !== null && $numVal > $numAlvo,
                '>='           => $numVal !== null && $numAlvo !== null && $numVal >= $numAlvo,
                '<'            => $numVal !== null && $numAlvo !== null && $numVal < $numAlvo,
                '<='           => $numVal !== null && $numAlvo !== null && $numVal <= $numAlvo,
                'between'      => $numVal !== null && $numAlvo !== null && $numMax !== null && $numVal >= min($numAlvo, $numMax) && $numVal <= max($numAlvo, $numMax),
                default        => false,
            };
        };

        $resFinal = null;
        foreach ($grupos as $g) {
            $resGrupo = null;
            foreach ($g['regras'] ?? [] as $regra) {
                $ok = $avaliar($regra);
                $conR = strtoupper($regra['conector'] ?? 'AND') === 'OR' ? 'OR' : 'AND';
                $resGrupo = $resGrupo === null ? $ok : ($conR === 'OR' ? ($resGrupo || $ok) : ($resGrupo && $ok));
            }
            $resGrupo = $resGrupo ?? false;
            $conG = strtoupper($g['conector'] ?? 'AND') === 'OR' ? 'OR' : 'AND';
            $resFinal = $resFinal === null ? $resGrupo : ($conG === 'OR' ? ($resFinal || $resGrupo) : ($resFinal && $resGrupo));
        }

        return $resFinal ?? true;
    }
}