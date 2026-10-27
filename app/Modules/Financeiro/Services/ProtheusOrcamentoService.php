<?php

namespace App\Modules\Financeiro\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\PendingRequest;
use App\Modules\Financeiro\Domain\Models\Orcamento;
use App\Modules\Financeiro\Domain\Models\OrcamentoItem;
use App\Modules\Financeiro\Domain\Models\Natureza;
use App\Modules\Financeiro\Domain\Models\CentroCusto;
use App\Modules\Financeiro\Domain\Models\OrcamentoAvaliacao;

class ProtheusOrcamentoService
{
    /**
     * Monta o cliente HTTP padrão para o Protheus com o header 'x-api-key'
     */
    private static function httpClient(): PendingRequest
    {
        $apiKey = env('PROTHEUS_API_KEY', '');
        $user = env('PROTHEUS_API_USER');
        $password = env('PROTHEUS_API_PASSWORD');

        $headers = [
            'Content-Type' => 'application/json',
            'Accept'       => '*/*',
        ];

        if (!empty($apiKey)) {
            $headers['x-api-key'] = $apiKey;
        }

        $client = Http::withHeaders($headers)
            ->timeout(60)
            ->withOptions(['verify' => false]);

        if (!empty($user) && !empty($password)) {
            $client = $client->withBasicAuth($user, $password);
        }

        return $client;
    }

    /**
     * Sincroniza Centros de Custo e salva na tabela 'centros_custo'
     */
    public static function sincronizarCentrosCusto(?array $dadosJson = null): array
    {
        try {
            if ($dadosJson === null) {
                $url = env('PROTHEUS_API_CCUSTO_URL', env('PROTHEUS_API_URL'));
                if (!$url) {
                    return ['sucesso' => false, 'mensagem' => 'URL da API de Centros de Custo (PROTHEUS_API_CCUSTO_URL) não configurada no .env.'];
                }

                $response = self::httpClient()->get($url);
                if (!$response->successful()) {
                    return ['sucesso' => false, 'mensagem' => 'Erro na API de Centros de Custo do Protheus (HTTP ' . $response->status() . ').'];
                }
                $dadosJson = $response->json();
            }

            $lista = $dadosJson['centrosCusto'] ?? (is_array($dadosJson) && isset($dadosJson[0]['codigo']) ? $dadosJson : []);
            if (empty($lista) || !is_array($lista)) {
                return ['sucesso' => false, 'mensagem' => 'Nenhum Centro de Custo retornado pela API do Protheus.'];
            }

            $processados = 0;
            foreach ($lista as $item) {
                $codigo = trim((string) ($item['codigo'] ?? ''));
                if ($codigo === '') continue;

                $descricao = trim((string) ($item['descricao'] ?? $codigo));
                $statusRaw = strtoupper(trim((string) ($item['status'] ?? 'INATIVO')));
                $disponivel = in_array($statusRaw, ['DISPONIVEL', 'DISPONÍVEL', '1', 'S', 'SIM', 'TRUE'], true);

                CentroCusto::updateOrCreate(
                    ['codigo' => $codigo],
                    [
                        'nome'                 => $descricao,
                        'disponivel_orcamento' => $disponivel,
                    ]
                );
                $processados++;
            }

            return ['sucesso' => true, 'mensagem' => "{$processados} Centro(s) de Custo sincronizado(s) e salvos no banco de dados!"];
        } catch (\Throwable $e) {
            return ['sucesso' => false, 'mensagem' => 'Falha ao sincronizar Centros de Custo: ' . $e->getMessage()];
        }
    }

    /**
     * Sincroniza Naturezas Financeiras e salva na tabela 'naturezas'
     */
    public static function sincronizarNaturezas(?array $dadosJson = null): array
    {
        try {
            if ($dadosJson === null) {
                $url = env('PROTHEUS_API_NATUREZAS_URL', env('PROTHEUS_API_URL'));
                if (!$url) {
                    return ['sucesso' => false, 'mensagem' => 'URL da API de Naturezas (PROTHEUS_API_NATUREZAS_URL) não configurada no .env.'];
                }

                $response = self::httpClient()->get($url);
                if (!$response->successful()) {
                    return ['sucesso' => false, 'mensagem' => 'Erro na API de Naturezas do Protheus (HTTP ' . $response->status() . ').'];
                }
                $dadosJson = $response->json();
            }

            $lista = $dadosJson['naturezas'] ?? (is_array($dadosJson) && isset($dadosJson[0]['codigo']) ? $dadosJson : []);
            if (empty($lista) || !is_array($lista)) {
                return ['sucesso' => false, 'mensagem' => 'Nenhuma Natureza Financeira retornada pela API do Protheus.'];
            }

            $processados = 0;
            foreach ($lista as $item) {
                $codigo = trim((string) ($item['codigo'] ?? ''));
                if ($codigo === '') continue;

                $descricao = trim((string) ($item['descricao'] ?? $codigo));
                $categoria = trim((string) ($item['categoria'] ?? '')) ?: null;
                $tipo      = strtoupper(trim((string) ($item['tipo'] ?? 'DESPESA')));
                $statusRaw = strtoupper(trim((string) ($item['status'] ?? 'INATIVO')));
                $disponivel = in_array($statusRaw, ['DISPONIVEL', 'DISPONÍVEL', '1', 'S', 'SIM', 'TRUE'], true);

                Natureza::updateOrCreate(
                    ['codigo' => $codigo],
                    [
                        'descricao'            => $descricao,
                        'categoria'            => $categoria,
                        'tipo'                 => $tipo,
                        'disponivel_orcamento' => $disponivel,
                    ]
                );
                $processados++;
            }

            return ['sucesso' => true, 'mensagem' => "{$processados} Natureza(s) Financeira(s) sincronizada(s) e salvas no banco de dados!"];
        } catch (\Throwable $e) {
            return ['sucesso' => false, 'mensagem' => 'Falha ao sincronizar Naturezas: ' . $e->getMessage()];
        }
    }

    /**
     * Sincroniza Cadastros Base (Centros de Custo + Naturezas) de uma só vez
     */
    public static function sincronizarCadastrosBase(): array
    {
        $resCC = self::sincronizarCentrosCusto();
        $resNat = self::sincronizarNaturezas();

        if ($resCC['sucesso'] && $resNat['sucesso']) {
            return ['sucesso' => true, 'mensagem' => $resCC['mensagem'] . ' | ' . $resNat['mensagem']];
        }

        if ($resCC['sucesso']) return $resCC;
        if ($resNat['sucesso']) return $resNat;

        return ['sucesso' => false, 'mensagem' => $resCC['mensagem'] . ' / ' . $resNat['mensagem']];
    }

    /**
     * Sincroniza os Orçamentos disponibilizados na Tabela Intermediária do Protheus
     */
    public static function sincronizar()
    {
        $url = env('PROTHEUS_API_URL');
        if (!$url) {
            return ['sucesso' => false, 'mensagem' => 'URL da API Protheus (PROTHEUS_API_URL) ausente no .env.'];
        }

        try {
            $response = self::httpClient()->get($url);

            if ($response->successful()) {
                $dados = $response->json();

                if (isset($dados['centrosCusto'])) {
                    self::sincronizarCentrosCusto($dados);
                }
                if (isset($dados['naturezas'])) {
                    self::sincronizarNaturezas($dados);
                }
                
                $orcamentosApi = isset($dados['orcamentos']) ? $dados['orcamentos'] : (is_array($dados) && !isset($dados['ano']) ? $dados : [$dados]);
                
                $processados = 0;

                foreach ($orcamentosApi as $item) {
                    $ano = trim((string) ($item['ano'] ?? ''));
                    $ccusto = trim((string) ($item['ccusto'] ?? ''));
                    $descricaoCC = trim((string) ($item['descricao'] ?? ''));
                    $filial = trim((string) ($item['filial'] ?? '0101'));

                    if (empty($ano) || empty($ccusto)) continue;

                    CentroCusto::firstOrCreate(
                        ['codigo' => $ccusto],
                        ['nome' => $descricaoCC ?: "Centro de Custo {$ccusto}", 'disponivel_orcamento' => true]
                    );

                    $chaveHeader = "{$filial}_{$ano}_{$ccusto}";

                    $orcamento = Orcamento::firstOrCreate(
                        ['chave_composta' => $chaveHeader],
                        [
                            'filial'            => $filial, 
                            'ano'               => $ano, 
                            'ccusto'            => $ccusto,
                            'descricao_despesa' => $descricaoCC,
                            'status'            => 'Importado',
                        ]
                    );

                    if (isset($item['despesas']) && is_array($item['despesas'])) {
                        $seq = 1;
                        foreach ($item['despesas'] as $despesa) {
                            $idDespesa = str_pad(trim((string) ($despesa['id'] ?? $seq)), 3, '0', STR_PAD_LEFT);
                            $naturezaCod = trim((string) ($despesa['natureza'] ?? ''));
                            $naturezaDesc = trim((string) ($despesa['descricaoNatureza'] ?? 'Natureza Importada'));
                            $cenario = trim((string) ($despesa['cenario'] ?? '1')) ?: '1';
                            
                            if (!empty($naturezaCod)) {
                                Natureza::firstOrCreate(
                                    ['codigo' => $naturezaCod],
                                    ['descricao' => $naturezaDesc, 'tipo' => 'DESPESA', 'disponivel_orcamento' => true]
                                );
                            }

                            $valores = $despesa['valores'] ?? array_fill(0, 12, 0);
                            $descricaoDespesa = trim((string) ($despesa['descricao'] ?? $naturezaDesc));

                            $itemOrc = OrcamentoItem::updateOrCreate(
                                [
                                    'orcamento_id' => $orcamento->id,
                                    'id_despesa'   => $idDespesa,
                                ],
                                [
                                    'natureza_codigo' => $naturezaCod,
                                    'cenario'         => $cenario,
                                    'descricao'       => $descricaoDespesa,
                                    'status'          => 'Importado',
                                    'previsto_jan'    => $valores[0] ?? 0, 'valor_jan' => $valores[0] ?? 0,
                                    'previsto_fev'    => $valores[1] ?? 0, 'valor_fev' => $valores[1] ?? 0,
                                    'previsto_mar'    => $valores[2] ?? 0, 'valor_mar' => $valores[2] ?? 0,
                                    'previsto_abr'    => $valores[3] ?? 0, 'valor_abr' => $valores[3] ?? 0,
                                    'previsto_mai'    => $valores[4] ?? 0, 'valor_mai' => $valores[4] ?? 0,
                                    'previsto_jun'    => $valores[5] ?? 0, 'valor_jun' => $valores[5] ?? 0,
                                    'previsto_jul'    => $valores[6] ?? 0, 'valor_jul' => $valores[6] ?? 0,
                                    'previsto_ago'    => $valores[7] ?? 0, 'valor_ago' => $valores[7] ?? 0,
                                    'previsto_set'    => $valores[8] ?? 0, 'valor_set' => $valores[8] ?? 0,
                                    'previsto_out'    => $valores[9] ?? 0, 'valor_out' => $valores[9] ?? 0,
                                    'previsto_nov'    => $valores[10] ?? 0, 'valor_nov' => $valores[10] ?? 0,
                                    'previsto_dez'    => $valores[11] ?? 0, 'valor_dez' => $valores[11] ?? 0,
                                ]
                            );

                            if (!empty(trim((string) ($despesa['comentario'] ?? '')))) {
                                OrcamentoAvaliacao::firstOrCreate(
                                    [
                                        'orcamento_id'      => $orcamento->id,
                                        'orcamento_item_id' => $itemOrc->id,
                                        'tipo_evento'       => 'comentario_importacao',
                                    ],
                                    [
                                        'user_id'         => auth()->id() ?? 1,
                                        'user_nome'       => 'Importação Protheus',
                                        'status_aplicado' => 'Importado',
                                        'comentario'      => trim((string) $despesa['comentario']),
                                    ]
                                );
                            }
                            $seq++;
                        }
                    }
                    $processados++;
                }
                return ['sucesso' => true, 'mensagem' => "Sincronização concluída! {$processados} orçamento(s) processado(s)."];
            }
            return ['sucesso' => false, 'mensagem' => "Erro na API do Protheus (HTTP " . $response->status() . ")."];
        } catch (\Exception $e) {
            return ['sucesso' => false, 'mensagem' => "Erro de ligação à API do Protheus: " . $e->getMessage()];
        }
    }

    // Mantenha abaixo os métodos consolidarParaTabelaIntermediaria() e enviarParaProtheusSimulado() que enviamos na Etapa 1

    /**
     * Gera a estrutura consolidada da Tabela Intermediária (Header + Despesas + Aglutinado por Natureza)
     */
    public function consolidarParaTabelaIntermediaria(Orcamento $orcamento): array
    {
        $orcamento->loadMissing(['centroCusto', 'itens.natureza']);

        $ccCodigo = trim((string) $orcamento->ccusto);
        $ccDescricaoVirtual = $orcamento->centroCusto ? $orcamento->centroCusto->nome : 'Centro de Custo (' . $ccCodigo . ')';

        $itensValidos = $orcamento->itens->whereNotIn('status', ['Reprovado', 'Não Aprovado', 'Não Aprovada']);

        // 1. Formato de retorno para a Tabela Intermediária (Despesas detalhadas com ID 001, Cenário e Valores)
        $despesasIntermediarias = [];
        $seq = 1;
        foreach ($itensValidos as $item) {
            $natObj = $item->natureza;
            $valoresMes = [
                round((float) $item->valor_jan, 2),
                round((float) $item->valor_fev, 2),
                round((float) $item->valor_mar, 2),
                round((float) $item->valor_abr, 2),
                round((float) $item->valor_mai, 2),
                round((float) $item->valor_jun, 2),
                round((float) $item->valor_jul, 2),
                round((float) $item->valor_ago, 2),
                round((float) $item->valor_set, 2),
                round((float) $item->valor_out, 2),
                round((float) $item->valor_nov, 2),
                round((float) $item->valor_dez, 2),
            ];

            $despesasIntermediarias[] = [
                'id'                => $item->id_despesa ?: str_pad((string) $seq, 3, '0', STR_PAD_LEFT),
                'descricao'         => trim((string) $item->descricao),
                'natureza'          => trim((string) $item->natureza_codigo),
                'descricaoNatureza' => $natObj ? $natObj->descricao : '',
                'categoria'         => $natObj ? ($natObj->categoria ?? '') : '',
                'cenario'           => (string) ($item->cenario ?: '1'),
                'comentario'        => '',
                'valores'           => $valoresMes,
                'total'             => round(array_sum($valoresMes), 2),
            ];
            $seq++;
        }

        // 2. Aglutinação por Natureza Financeira (SE7)
        $itensAgrupados = $itensValidos->groupBy('natureza_codigo');
        $linhasAglutinadas = [];

        foreach ($itensAgrupados as $codigoNatureza => $grupoItens) {
            $primeiroItem = $grupoItens->first();
            $natObj = $primeiroItem->natureza;

            $naturezaDescricaoVirtual = $natObj ? $natObj->descricao : 'Natureza Importada (' . $codigoNatureza . ')';
            $categoria = $orcamento->xcat ?: ($natObj->categoria ?? 'Despesa Orçamentária');

            $descricoes = $grupoItens->pluck('descricao')
                ->map(fn($d) => trim((string) $d))
                ->filter()
                ->unique()
                ->implode(' | ');

            $linhasAglutinadas[] = [
                'ano'                      => (int) $orcamento->ano,
                'codigo_centro_custo'      => $ccCodigo,
                'descricao_centro_custo'   => $ccDescricaoVirtual,
                'codigo_natureza'          => trim((string) $codigoNatureza),
                'descricao_natureza'       => $naturezaDescricaoVirtual,
                'categoria'                => $categoria,
                'descricao_despesa'        => $descricoes ?: $naturezaDescricaoVirtual,
                'status'                   => '3', // 3 = Aprovado na Tabela Intermediária Protheus (Slide 15)
                'valor_previsto_janeiro'   => round((float) $grupoItens->sum('valor_jan'), 2),
                'valor_previsto_fevereiro' => round((float) $grupoItens->sum('valor_fev'), 2),
                'valor_previsto_marco'     => round((float) $grupoItens->sum('valor_mar'), 2),
                'valor_previsto_abril'     => round((float) $grupoItens->sum('valor_abr'), 2),
                'valor_previsto_maio'      => round((float) $grupoItens->sum('valor_mai'), 2),
                'valor_previsto_junho'     => round((float) $grupoItens->sum('valor_jun'), 2),
                'valor_previsto_julho'     => round((float) $grupoItens->sum('valor_jul'), 2),
                'valor_previsto_agosto'    => round((float) $grupoItens->sum('valor_ago'), 2),
                'valor_previsto_setembro'  => round((float) $grupoItens->sum('valor_set'), 2),
                'valor_previsto_outubro'   => round((float) $grupoItens->sum('valor_out'), 2),
                'valor_previsto_novembro'  => round((float) $grupoItens->sum('valor_nov'), 2),
                'valor_previsto_dezembro'  => round((float) $grupoItens->sum('valor_dez'), 2),
            ];
        }

        return [
            'ano'             => (string) $orcamento->ano,
            'ccusto'          => $ccCodigo,
            'descricao'       => $ccDescricaoVirtual,
            'status'          => '3',
            'statusDescricao' => 'Aprovado',
            'total'           => round((float) $orcamento->valor_total, 2),
            'despesas'        => $despesasIntermediarias,
            'tabela_intermediaria' => $linhasAglutinadas,
            'meta' => [
                'orcamento_web_id'     => $orcamento->id,
                'filial'               => $orcamento->filial,
                'status_orcamento_web' => $orcamento->status,
                'total_naturezas'      => count($linhasAglutinadas),
                'data_consolidacao'    => now()->toIso8601String(),
                'integrado_por'        => auth()->user()->name ?? 'Sistema',
            ],
        ];
    }

    public function enviarParaProtheusSimulado(Orcamento $orcamento): array
    {
        $payload = $this->consolidarParaTabelaIntermediaria($orcamento);

        $orcamento->updateQuietly(['dados_consolidados' => $payload]);

        Log::info("[INTEGRAÇÃO PROTHEUS] Payload da Tabela Intermediária consolidado para o Orçamento #{$orcamento->id}", $payload);

        OrcamentoAvaliacao::create([
            'orcamento_id'    => $orcamento->id,
            'user_id'         => auth()->id() ?? 1,
            'user_nome'       => auth()->user()->name ?? 'Sistema',
            'status_aplicado' => 'Finalizado – Integrado ao Protheus',
            'comentario'      => "Orçamento consolidado e preparado para a Tabela Intermediária do Protheus (" . count($payload['despesas']) . " despesas / " . count($payload['tabela_intermediaria']) . " naturezas).",
            'tipo_evento'     => 'integracao_protheus',
        ]);

        return $payload;
    }
}