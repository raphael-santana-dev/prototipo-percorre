<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait ComFiltroAvancado
{
    /**
     * Estrutura unificada de filtros:
     * Ex: [
     *   'curso_id' => ['1', '3'],
     *   'status_inscricao_id' => ['2'],
     *   'data_inscricao' => ['inicio' => '2026-01-01', 'fim' => '2026-10-19'],
     *   'dyn_renda_familiar' => ['Até 1 salário', 'De 1 a 2 salários']
     * ]
     */
    public array $filtrosAvancados = [];

    /**
     * Controla a abertura do painel/drawer de filtros
     */
    public bool $painelFiltrosAberto = true;
    public bool $drawerFiltrosAberto = false;

    public function updatedFiltrosAvancados(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
        if (method_exists($this, 'desmarcarTodas')) {
            $this->desmarcarTodas();
        }
    }

    /**
     * Alterna um valor dentro de um filtro multi-seleção (permite filtrar 2+ vezes a mesma opção)
     */
    public function toggleFiltroValor(string $chave, string $valor): void
    {
        $atuais = $this->filtrosAvancados[$chave] ?? [];
        if (!is_array($atuais)) {
            $atuais = [$atuais];
        }
        $atuais = array_map('strval', $atuais);

        if (in_array((string) $valor, $atuais, true)) {
            $atuais = array_values(array_diff($atuais, [(string) $valor]));
        } else {
            $atuais[] = (string) $valor;
        }

        if (empty($atuais)) {
            unset($this->filtrosAvancados[$chave]);
        } else {
            $this->filtrosAvancados[$chave] = $atuais;
        }

        $this->updatedFiltrosAvancados();
    }

    /**
     * Adiciona uma tag/termo extra em filtros de texto múltiplo
     */
    public function adicionarTermoFiltro(string $chave, string $termo): void
    {
        $termo = trim($termo);
        if ($termo === '') return;

        $atuais = $this->filtrosAvancados[$chave] ?? [];
        if (!is_array($atuais)) {
            $atuais = array_filter([$atuais]);
        }

        if (!in_array($termo, $atuais, true)) {
            $atuais[] = $termo;
        }

        $this->filtrosAvancados[$chave] = array_values($atuais);
        $this->updatedFiltrosAvancados();
    }

    /**
     * Remove um valor específico de um filtro (acionado pelo botão 'X' do Chip)
     */
    public function removerFiltroItem(string $chave, ?string $valorEspecifico = null): void
    {
        if (!isset($this->filtrosAvancados[$chave])) {
            return;
        }

        if ($valorEspecifico === null) {
            unset($this->filtrosAvancados[$chave]);
        } elseif (is_array($this->filtrosAvancados[$chave])) {
            if (isset($this->filtrosAvancados[$chave]['inicio']) || isset($this->filtrosAvancados[$chave]['fim'])) {
                unset($this->filtrosAvancados[$chave]);
            } else {
                $this->filtrosAvancados[$chave] = array_values(array_filter(
                    $this->filtrosAvancados[$chave],
                    fn($v) => (string) $v !== (string) $valorEspecifico
                ));
                if (empty($this->filtrosAvancados[$chave])) {
                    unset($this->filtrosAvancados[$chave]);
                }
            }
        } else {
            unset($this->filtrosAvancados[$chave]);
        }

        $this->updatedFiltrosAvancados();
    }

    public function limparTodosFiltrosAvancados(): void
    {
        $this->filtrosAvancados = [];
        if (property_exists($this, 'filtroNome')) $this->filtroNome = '';
        if (property_exists($this, 'filtroBusca')) $this->filtroBusca = '';
        if (property_exists($this, 'search')) $this->search = '';

        $this->updatedFiltrosAvancados();
    }

    /**
     * Gera a lista de Chips ativos prontos para exibição no topo da tela
     */
    public function obterChipsAtivos(array $esquemaFiltros): array
    {
        $chips = [];
        $mapaEsquema = collect($esquemaFiltros)->keyBy('key');

        foreach ($this->filtrosAvancados as $chave => $valor) {
            if ($valor === '' || $valor === null || $valor === []) continue;

            $config = $mapaEsquema[$chave] ?? null;
            if (!$config) continue;

            $labelGrupo = $config['label'] ?? $chave;
            $opcoes = $config['options'] ?? [];
            $tipo = $config['type'] ?? 'multiselect';

            if ($tipo === 'date_range' && is_array($valor)) {
                $ini = !empty($valor['inicio']) ? date('d/m/Y', strtotime($valor['inicio'])) : null;
                $fim = !empty($valor['fim']) ? date('d/m/Y', strtotime($valor['fim'])) : null;
                if ($ini || $fim) {
                    $chips[] = [
                        'key'   => $chave,
                        'value' => null,
                        'group' => $labelGrupo,
                        'label' => ($ini && $fim) ? "{$ini} até {$fim}" : ($ini ? "A partir de {$ini}" : "Até {$fim}"),
                    ];
                }
                continue;
            }

            $valoresArray = is_array($valor) ? $valor : [$valor];
            foreach ($valoresArray as $v) {
                if ($v === '' || $v === null) continue;
                $nomeValor = $opcoes[$v] ?? $v;
                $chips[] = [
                    'key'   => $chave,
                    'value' => (string) $v,
                    'group' => $labelGrupo,
                    'label' => (string) $nomeValor,
                ];
            }
        }

        return $chips;
    }

    /**
     * Aplica automaticamente todos os filtros (fixos e dinâmicos JSON) na Query Eloquent
     */
    public function aplicarFiltrosAvancadosNaQuery(
        Builder $query,
        array $esquemaFiltros,
        string $tabelaPrincipal = 'inscricoes',
        string $colunaJsonDinamico = 'dados_dinamicos'
    ): Builder {
        $mapaEsquema = collect($esquemaFiltros)->keyBy('key');

        foreach ($this->filtrosAvancados as $chave => $valor) {
            if ($valor === '' || $valor === null || $valor === []) continue;

            $config = $mapaEsquema[$chave] ?? null;
            if (!$config) continue;

            $tipo = $config['type'] ?? 'multiselect';
            $isDinamico = !empty($config['is_dynamic']);
            $colunaBanco = $config['column'] ?? $chave;

            // 1. FILTROS EM CAMPOS DINÂMICOS (JSONB / JSON no PostgreSQL)
            if ($isDinamico) {
                $nomeCampoJson = $config['dynamic_field'] ?? str_replace('dyn_', '', $chave);

                if ($tipo === 'date_range' && is_array($valor)) {
                    if (!empty($valor['inicio'])) {
                        $query->whereRaw("({$tabelaPrincipal}.{$colunaJsonDinamico}->>?)::date >= ?", [$nomeCampoJson, $valor['inicio']]);
                    }
                    if (!empty($valor['fim'])) {
                        $query->whereRaw("({$tabelaPrincipal}.{$colunaJsonDinamico}->>?)::date <= ?", [$nomeCampoJson, $valor['fim']]);
                    }
                } else {
                    $valores = array_values(array_filter(is_array($valor) ? $valor : [$valor], fn($v) => $v !== '' && $v !== null));
                    if (!empty($valores)) {
                        $query->where(function ($sub) use ($tabelaPrincipal, $colunaJsonDinamico, $nomeCampoJson, $valores) {
                            foreach ($valores as $val) {
                                $sub->orWhereRaw("{$tabelaPrincipal}.{$colunaJsonDinamico}->>? ILIKE ?", [$nomeCampoJson, '%' . $val . '%']);
                            }
                        });
                    }
                }
                continue;
            }

            // 2. FILTROS FIXOS DA TABELA
            if ($tipo === 'date_range' && is_array($valor)) {
                if (!empty($valor['inicio'])) {
                    $query->whereDate("{$tabelaPrincipal}.{$colunaBanco}", '>=', $valor['inicio']);
                }
                if (!empty($valor['fim'])) {
                    $query->whereDate("{$tabelaPrincipal}.{$colunaBanco}", '<=', $valor['fim']);
                }
            } elseif ($tipo === 'text_multi') {
                $valores = array_values(array_filter(is_array($valor) ? $valor : [$valor]));
                if (!empty($valores)) {
                    $query->where(function ($sub) use ($tabelaPrincipal, $colunaBanco, $valores) {
                        foreach ($valores as $val) {
                            $sub->orWhere("{$tabelaPrincipal}.{$colunaBanco}", 'ilike', '%' . $val . '%');
                        }
                    });
                }
            } else {
                $valores = array_values(array_filter(is_array($valor) ? $valor : [$valor], fn($v) => $v !== '' && $v !== null));
                if (!empty($valores)) {
                    if ($colunaBanco === 'etapa_atual') {
                        $valoresNormalizados = array_map(fn($v) => $v === 'Finalizado' ? 99 : $v, $valores);
                        $query->whereIn("{$tabelaPrincipal}.{$colunaBanco}", $valoresNormalizados);
                    } else {
                        $query->whereIn("{$tabelaPrincipal}.{$colunaBanco}", $valores);
                    }
                }
            }
        }

        return $query;
    }

    /**
     * Converte uma coleção de CampoFormulario em definições de filtro para o componente
     */
    public function mapearCamposDinamicosParaFiltro(Collection $campos): array
    {
        $esquemaDinamico = [];

        foreach ($campos as $campo) {
            if (in_array($campo->tipo, ['config', 'html', 'divider', 'media', 'spacer', 'link', 'signature', 'file'])) {
                continue;
            }

            $labelLimpo = trim(strip_tags($campo->label)) ?: $campo->name;
            $key = 'dyn_' . $campo->name;

            if (in_array($campo->tipo, ['select', 'radio', 'check'])) {
                $opcoesCruas = is_string($campo->opcoes) ? json_decode($campo->opcoes, true) : $campo->opcoes;
                if (!is_array($opcoesCruas)) {
                    $opcoesCruas = array_filter(array_map('trim', explode("\n", (string) $campo->opcoes)));
                }
                $opcoesFormatadas = [];
                foreach ($opcoesCruas as $op) {
                    if (is_string($op) && trim($op) !== '') {
                        $opcoesFormatadas[trim($op)] = trim($op);
                    }
                }

                $esquemaDinamico[] = [
                    'key'           => $key,
                    'label'         => $labelLimpo,
                    'icon'          => 'ph-list-Checks',
                    'type'          => 'multiselect',
                    'options'       => $opcoesFormatadas,
                    'is_dynamic'    => true,
                    'dynamic_field' => $campo->name,
                    'section'       => 'Campos do Formulário',
                ];
            } elseif ($campo->subtipo === 'date' || $campo->subtipo === 'datetime-local') {
                $esquemaDinamico[] = [
                    'key'           => $key,
                    'label'         => $labelLimpo,
                    'icon'          => 'ph-calendar',
                    'type'          => 'date_range',
                    'is_dynamic'    => true,
                    'dynamic_field' => $campo->name,
                    'section'       => 'Campos do Formulário',
                ];
            } else {
                $esquemaDinamico[] = [
                    'key'           => $key,
                    'label'         => $labelLimpo,
                    'icon'          => 'ph-text-t',
                    'type'          => 'text_multi',
                    'is_dynamic'    => true,
                    'dynamic_field' => $campo->name,
                    'section'       => 'Campos do Formulário',
                ];
            }
        }

        return $esquemaDinamico;
    }
}