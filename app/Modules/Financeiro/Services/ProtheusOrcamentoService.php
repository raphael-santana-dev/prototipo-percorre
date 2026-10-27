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
     * Cliente HTTP padrão com o header 'x-api-key'
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

            return ['sucesso' => true, 'mensagem' => "{$processados} Centro(s) de Custo sincronizado(s)!"];
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

            return ['sucesso' => true, 'mensagem' => "{$processados} Natureza(s) Financeira(s) sincronizada(s)!"];
        } catch (\Throwable $e) {
            return ['sucesso' => false, 'mensagem' => 'Falha ao sincronizar Naturezas: ' . $e->getMessage()];
        }
    }

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
     * 1. CONSULTA SEM PARÂMETROS (Listagem de Orçamentos: /rest/wstabint)
     * Retorna { "orcamentos": [ { "ano": "2026", "ccusto": "20121", "descricao": "TECNOLOGIA", "status": "2", "statusDescricao": "...", "total": 246783.79 } ] }
     */
    public static function sincronizar(): array
    {
        $url = rtrim((string) env('PROTHEUS_API_URL'), '/');
        if (!$url) {
            return ['sucesso' => false, 'mensagem' => 'URL da API Protheus (PROTHEUS_API_URL) ausente no .env.'];
        }

        try {
            $response = self::httpClient()->get($url);

            if (!$response->successful()) {
                return ['sucesso' => false, 'mensagem' => "Erro na API do Protheus (HTTP " . $response->status() . ")."];
            }

            $dados = $response->json();

            if (isset($dados['centrosCusto'])) {
                self::sincronizarCentrosCusto($dados);
            }
            if (isset($dados['naturezas'])) {
                self::sincronizarNaturezas($dados);
            }
            
            $orcamentosApi = isset($dados['orcamentos'])
                ? $dados['orcamentos']
                : (is_array($dados) && !isset($dados['ano']) ? $dados : [$dados]);
            
            $processados = 0;

            foreach ($orcamentosApi as $item) {
                $ano = trim((string) ($item['ano'] ?? ''));
                $ccusto = trim((string) ($item['ccusto'] ?? ''));
                $descricaoCC = trim((string) ($item['descricao'] ?? ''));
                $filial = trim((string) ($item['filial'] ?? '0101'));
                $totalApi = isset($item['total']) ? round((float) $item['total'], 2) : 0.0;

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
                        'dados_consolidados'=> [
                            'ano'             => $ano,
                            'ccusto'          => $ccusto,
                            'descricao'       => $descricaoCC,
                            'status'          => (string) ($item['status'] ?? '2'),
                            'statusDescricao' => (string) ($item['statusDescricao'] ?? 'Em revisão/atualização'),
                            'total'           => $totalApi,
                        ],
                    ]
                );

                // Se o orçamento ainda está como 'Importado' e não tem itens locais, atualiza o resumo do total da API
                if ($orcamento->status === 'Importado' && $orcamento->itens()->count() === 0 && $totalApi > 0) {
                    $metaAtual = is_array($orcamento->dados_consolidados) ? $orcamento->dados_consolidados : [];
                    $metaAtual['total'] = $totalApi;
                    $metaAtual['descricao'] = $descricaoCC ?: ($metaAtual['descricao'] ?? '');
                    $orcamento->updateQuietly(['dados_consolidados' => $metaAtual]);
                }

                // Caso o JSON já traga despesas diretamente, importa-as
                if (!empty($item['despesas']) && is_array($item['despesas'])) {
                    self::processarDespesasDoOrcamento($orcamento, $item['despesas']);
                }

                $processados++;
            }

            return ['sucesso' => true, 'mensagem' => "Sincronização concluída! {$processados} orçamento(s) listado(s)."];
        } catch (\Throwable $e) {
            return ['sucesso' => false, 'mensagem' => "Erro de ligação à API do Protheus: " . $e->getMessage()];
        }
    }

    /**
     * 2. CONSULTA COM PARÂMETROS (Ao abrir um orçamento: /rest/wstabint/?ano=2026&ccusto=20121)
     * Busca as despesas detalhadas do orçamento específico no Protheus.
     */
    public static function sincronizarDetalhesOrcamento(Orcamento $orcamento, bool $forcarAtualizacao = false): array
    {
        // Só consulta na API se o orçamento ainda não possuir itens salvos localmente ou se estiver no status inicial 'Importado'/'Criado'
        $qtdItensLocais = $orcamento->itens()->count();
        if (!$forcarAtualizacao && $qtdItensLocais > 0) {
            return ['sucesso' => true, 'mensagem' => 'Itens já carregados na base local.'];
        }

        $baseUrl = rtrim((string) env('PROTHEUS_API_URL'), '/');
        if (!$baseUrl) {
            return ['sucesso' => false, 'mensagem' => 'PROTHEUS_API_URL não configurada.'];
        }

        try {
            $urlComParams = $baseUrl . '/?ano=' . urlencode((string) $orcamento->ano) . '&ccusto=' . urlencode((string) $orcamento->ccusto);
            $response = self::httpClient()->get($urlComParams);

            if (!$response->successful()) {
                return ['sucesso' => false, 'mensagem' => 'Não foi possível obter os itens detalhados na API do Protheus (HTTP ' . $response->status() . ').'];
            }

            $dados = $response->json();

            // Suporta retorno direto { "ano": "2026", "ccusto": "20121", "despesas": [...] } ou dentro de "orcamentos"[0]
            $payloadOrcamento = $dados;
            if (!isset($payloadOrcamento['despesas']) && isset($dados['orcamentos'][0])) {
                $payloadOrcamento = $dados['orcamentos'][0];
            }

            if (!empty($payloadOrcamento['descricao'])) {
                CentroCusto::updateOrCreate(
                    ['codigo' => trim((string) $orcamento->ccusto)],
                    ['nome' => trim((string) $payloadOrcamento['descricao']), 'disponivel_orcamento' => true]
                );
            }

            if (isset($payloadOrcamento['despesas']) && is_array($payloadOrcamento['despesas'])) {
                self::processarDespesasDoOrcamento($orcamento, $payloadOrcamento['despesas']);
                return ['sucesso' => true, 'mensagem' => count($payloadOrcamento['despesas']) . ' despesa(s) carregada(s) da API do Protheus.'];
            }

            return ['sucesso' => false, 'mensagem' => 'A consulta com parâmetros não retornou a chave "despesas".'];
        } catch (\Throwable $e) {
            Log::warning("Falha ao buscar detalhes do orçamento #{$orcamento->id} com parâmetros na API Protheus: " . $e->getMessage());
            return ['sucesso' => false, 'mensagem' => $e->getMessage()];
        }
    }

    /**
     * Grava ou atualiza os itens (despesas) e seus comentários iniciais vindos do JSON com parâmetros
     */
    private static function processarDespesasDoOrcamento(Orcamento $orcamento, array $despesas): void
    {
        $seq = 1;
        foreach ($despesas as $despesa) {
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
                    'previsto_jan'    => (float) ($valores[0] ?? 0),  'valor_jan' => (float) ($valores[0] ?? 0),
                    'previsto_fev'    => (float) ($valores[1] ?? 0),  'valor_fev' => (float) ($valores[1] ?? 0),
                    'previsto_mar'    => (float) ($valores[2] ?? 0),  'valor_mar' => (float) ($valores[2] ?? 0),
                    'previsto_abr'    => (float) ($valores[3] ?? 0),  'valor_abr' => (float) ($valores[3] ?? 0),
                    'previsto_mai'    => (float) ($valores[4] ?? 0),  'valor_mai' => (float) ($valores[4] ?? 0),
                    'previsto_jun'    => (float) ($valores[5] ?? 0),  'valor_jun' => (float) ($valores[5] ?? 0),
                    'previsto_jul'    => (float) ($valores[6] ?? 0),  'valor_jul' => (float) ($valores[6] ?? 0),
                    'previsto_ago'    => (float) ($valores[7] ?? 0),  'valor_ago' => (float) ($valores[7] ?? 0),
                    'previsto_set'    => (float) ($valores[8] ?? 0),  'valor_set' => (float) ($valores[8] ?? 0),
                    'previsto_out'    => (float) ($valores[9] ?? 0),  'valor_out' => (float) ($valores[9] ?? 0),
                    'previsto_nov'    => (float) ($valores[10] ?? 0), 'valor_nov' => (float) ($valores[10] ?? 0),
                    'previsto_dez'    => (float) ($valores[11] ?? 0), 'valor_dez' => (float) ($valores[11] ?? 0),
                ]
            );

            $comentarioImportado = trim((string) ($despesa['comentario'] ?? ''));
            if ($comentarioImportado !== '') {
                OrcamentoAvaliacao::updateOrCreate(
                    [
                        'orcamento_id'      => $orcamento->id,
                        'orcamento_item_id' => $itemOrc->id,
                        'tipo_evento'       => 'comentario_importacao',
                    ],
                    [
                        'user_id'         => auth()->id() ?? 1,
                        'user_nome'       => 'Importação Protheus',
                        'status_aplicado' => 'Comentário Original',
                        'comentario'      => $comentarioImportado,
                    ]
                );
            }
            $seq++;
        }
    }

    /**
     * Consolida os dados do orçamento no formato exato de 'comparams.json' (sem a chave 'tabela_intermediaria')
     */
    public function consolidarParaTabelaIntermediaria(Orcamento $orcamento): array
    {
        $orcamento->loadMissing(['centroCusto', 'itens.natureza', 'itens.avaliacoes']);

        $ccCodigo = trim((string) $orcamento->ccusto);
        $ccDescricao = $orcamento->centroCusto ? $orcamento->centroCusto->nome : ($orcamento->descricao_despesa ?: '');

        $itensValidos = $orcamento->itens()
            ->with(['natureza', 'avaliacoes'])
            ->whereNotIn('status', ['Reprovado', 'Não Aprovado', 'Não Aprovada'])
            ->orderBy('id', 'asc')
            ->get();

        $despesas = [];
        $totalOrcamento = 0.0;
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

            $totalItem = round(array_sum($valoresMes), 2);
            $totalOrcamento += $totalItem;

            // Concatena os comentários do item para enviar no campo "comentario" do Protheus
            $comentariosTexto = $item->avaliacoes
                ->filter(fn($av) => !empty(trim((string) $av->comentario)))
                ->sortBy('id')
                ->map(fn($av) => ($av->tipo_evento === 'comentario_importacao')
                    ? trim((string) $av->comentario)
                    : "{$av->user_nome}:\r\n" . trim((string) $av->comentario)
                )
                ->implode("\r\n\r\n");

            $despesas[] = [
                'id'                => $item->id_despesa ?: str_pad((string) $seq, 3, '0', STR_PAD_LEFT),
                'descricao'         => trim((string) $item->descricao),
                'natureza'          => trim((string) $item->natureza_codigo),
                'descricaoNatureza' => $natObj ? $natObj->descricao : '',
                'cenario'           => (string) ($item->cenario ?: '1'),
                'comentario'        => $comentariosTexto,
                'valores'           => $valoresMes,
                'total'             => $totalItem,
            ];
            $seq++;
        }

        return [
            'ano'             => (string) $orcamento->ano,
            'ccusto'          => $ccCodigo,
            'descricao'       => $ccDescricao,
            'status'          => '3',
            'statusDescricao' => 'Aprovado',
            'total'           => round($totalOrcamento, 2),
            'despesas'        => $despesas,
        ];
    }

    public function enviarParaProtheusSimulado(Orcamento $orcamento): array
    {
        $payload = $this->consolidarParaTabelaIntermediaria($orcamento);

        $orcamento->updateQuietly(['dados_consolidados' => $payload]);

        Log::info("[INTEGRAÇÃO PROTHEUS] JSON consolidado gerado para o Orçamento #{$orcamento->id}", $payload);

        OrcamentoAvaliacao::create([
            'orcamento_id'    => $orcamento->id,
            'user_id'         => auth()->id() ?? 1,
            'user_nome'       => auth()->user()->name ?? 'Sistema',
            'status_aplicado' => 'Finalizado – Integrado ao Protheus',
            'comentario'      => "Orçamento consolidado para o Protheus (" . count($payload['despesas']) . " despesas).",
            'tipo_evento'     => 'integracao_protheus',
        ]);

        return $payload;
    }
}