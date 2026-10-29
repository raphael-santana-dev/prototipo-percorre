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
use App\Models\AuditoriaLog;

class ProtheusOrcamentoService
{
    /**
     * Função auxiliar para forçar o registo na tabela auditoria_logs para ações de API
     */
    private static function registrarLogIntegracao(string $acao, string $tabelaAfetada, ?int $registroId, array $payloadEnviado, $respostaApi): void
    {
        try {
            $usuario = auth()->user();
            
            AuditoriaLog::create([
                'tabela_alterada'     => $tabelaAfetada,
                'registro_id'         => $registroId,
                'acao'                => $acao,
                'informacao_anterior' => $payloadEnviado,
                'nova_informacao'     => is_string($respostaApi) ? ['resposta_bruta' => $respostaApi] : (array) $respostaApi,
                'usuario_id'          => $usuario?->id,
                'usuario_nome'        => $usuario?->name ?? 'Ação de Sistema (API Protheus)',
                'usuario_role'        => ($usuario && method_exists($usuario, 'getRoleNames')) ? $usuario->getRoleNames()->first() : 'N/A',
                'usuario_login'       => $usuario?->email,
                'ip'                  => request()->ip(),
                'navegador'           => request()->userAgent(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao gravar AuditoriaLog da integração Protheus: ' . $e->getMessage());
        }
    }

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
                    $erroMsg = 'Erro na API de Centros de Custo do Protheus (HTTP ' . $response->status() . ').';
                    self::registrarLogIntegracao('integracao_erro_ccusto', 'centros_custo', null, ['url' => $url], $response->body());
                    return ['sucesso' => false, 'mensagem' => $erroMsg];
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
            self::registrarLogIntegracao('integracao_excecao_ccusto', 'centros_custo', null, [], $e->getMessage());
            return ['sucesso' => false, 'mensagem' => 'Falha ao sincronizar Centros de Custo: ' . $e->getMessage()];
        }
    }

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
                    $erroMsg = 'Erro na API de Naturezas do Protheus (HTTP ' . $response->status() . ').';
                    self::registrarLogIntegracao('integracao_erro_natureza', 'naturezas', null, ['url' => $url], $response->body());
                    return ['sucesso' => false, 'mensagem' => $erroMsg];
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
            self::registrarLogIntegracao('integracao_excecao_natureza', 'naturezas', null, [], $e->getMessage());
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

    public static function sincronizar(): array
    {
        $url = rtrim((string) env('PROTHEUS_API_URL'), '/');
        if (!$url) {
            return ['sucesso' => false, 'mensagem' => 'URL da API Protheus (PROTHEUS_API_URL) ausente no .env.'];
        }

        try {
            $response = self::httpClient()->get($url);

            if (!$response->successful()) {
                $erroMsg = "Erro na API do Protheus (HTTP " . $response->status() . ").";
                self::registrarLogIntegracao('integracao_erro_orcamentos', 'orcamentos', null, ['url' => $url], $response->body());
                return ['sucesso' => false, 'mensagem' => $erroMsg];
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

                if ($orcamento->status === 'Importado' && $orcamento->itens()->count() === 0 && $totalApi > 0) {
                    $metaAtual = is_array($orcamento->dados_consolidados) ? $orcamento->dados_consolidados : [];
                    $metaAtual['total'] = $totalApi;
                    $metaAtual['descricao'] = $descricaoCC ?: ($metaAtual['descricao'] ?? '');
                    $orcamento->updateQuietly(['dados_consolidados' => $metaAtual]);
                }

                if (!empty($item['despesas']) && is_array($item['despesas'])) {
                    self::processarDespesasDoOrcamento($orcamento, $item['despesas']);
                }

                $processados++;
            }

            self::registrarLogIntegracao('integracao_sucesso_orcamentos', 'orcamentos', null, ['url' => $url], "{$processados} processados.");
            return ['sucesso' => true, 'mensagem' => "Sincronização concluída! {$processados} orçamento(s) listado(s)."];
        } catch (\Throwable $e) {
            self::registrarLogIntegracao('integracao_excecao_orcamentos', 'orcamentos', null, ['url' => $url], $e->getMessage());
            return ['sucesso' => false, 'mensagem' => "Erro de ligação à API do Protheus: " . $e->getMessage()];
        }
    }

    public static function sincronizarDetalhesOrcamento(Orcamento $orcamento, bool $forcarAtualizacao = false): array
    {
        $qtdItensLocais = $orcamento->itens()->count();
        if (!$forcarAtualizacao && $qtdItensLocais > 0) {
            return ['sucesso' => true, 'mensagem' => 'Itens já carregados na base local.'];
        }

        $baseUrl = rtrim((string) env('PROTHEUS_API_URL'), '/');
        if (!$baseUrl) {
            return ['sucesso' => false, 'mensagem' => 'PROTHEUS_API_URL não configurada.'];
        }

        $urlComParams = $baseUrl . '/?ano=' . urlencode((string) $orcamento->ano) . '&ccusto=' . urlencode((string) $orcamento->ccusto);

        try {
            $response = self::httpClient()->get($urlComParams);

            if (!$response->successful()) {
                $erroMsg = 'Não foi possível obter os itens detalhados na API do Protheus (HTTP ' . $response->status() . ').';
                self::registrarLogIntegracao('integracao_erro_detalhes_orcamento', 'orcamento_itens', $orcamento->id, ['url' => $urlComParams], $response->body());
                return ['sucesso' => false, 'mensagem' => $erroMsg];
            }

            $dados = $response->json();

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

            self::registrarLogIntegracao('integracao_erro_detalhes_orcamento', 'orcamento_itens', $orcamento->id, ['url' => $urlComParams], 'Retorno da API sem chave "despesas".');
            return ['sucesso' => false, 'mensagem' => 'A consulta com parâmetros não retornou a chave "despesas".'];
        } catch (\Throwable $e) {
            Log::warning("Falha ao buscar detalhes do orçamento #{$orcamento->id} com parâmetros na API Protheus: " . $e->getMessage());
            self::registrarLogIntegracao('integracao_excecao_detalhes_orcamento', 'orcamento_itens', $orcamento->id, ['url' => $urlComParams], $e->getMessage());
            return ['sucesso' => false, 'mensagem' => $e->getMessage()];
        }
    }

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

    public function integrarOrcamentoComProtheus(Orcamento $orcamento): array
    {
        $payload = $this->consolidarParaTabelaIntermediaria($orcamento);
        $orcamento->updateQuietly(['dados_consolidados' => $payload]);

        $baseUrl = rtrim((string) env('PROTHEUS_API_URL'), '/');
        if (!$baseUrl) {
            $msg = 'URL da API Protheus (PROTHEUS_API_URL) não configurada no .env.';
            self::registrarLogIntegracao('integracao_erro_env', 'orcamentos', $orcamento->id, $payload, $msg);
            return [
                'sucesso'  => false,
                'mensagem' => $msg,
                'payload'  => $payload,
            ];
        }

        $urlComParams = $baseUrl . '/?ano=' . urlencode((string) $orcamento->ano) . '&ccusto=' . urlencode(trim((string) $orcamento->ccusto));

        try {
            $response = self::httpClient()->put($urlComParams, $payload);

            if (!$response->successful()) {
                $erroDetalhe = $response->body();
                Log::error("[INTEGRAÇÃO PROTHEUS - ERRO PUT] Orçamento #{$orcamento->id} | HTTP {$response->status()}", [
                    'url'      => $urlComParams,
                    'payload'  => $payload,
                    'resposta' => $erroDetalhe,
                ]);

                // Dispara o log no banco para aparecer na tela AuditoriaManager
                self::registrarLogIntegracao('integracao_erro_put', 'orcamentos', $orcamento->id, ['url' => $urlComParams, 'payload' => $payload], $erroDetalhe);

                // Como a API do Protheus retornou JSON (ex: {"errorCode":409,"errorMessage":"..."}), podemos tentar extrair a mensagem limpa
                $erroDecoded = json_decode($erroDetalhe, true);
                $mensagemProtheus = $erroDecoded['errorMessage'] ?? (\Illuminate\Support\Str::limit($erroDetalhe, 160));

                return [
                    'sucesso'  => false,
                    'mensagem' => "Falha ao enviar para o Protheus (HTTP {$response->status()}): " . $mensagemProtheus,
                    'payload'  => $payload,
                ];
            }

            Log::info("[INTEGRAÇÃO PROTHEUS - PUT SUCESSO] Orçamento #{$orcamento->id} integrado ao Protheus", [
                'url'      => $urlComParams,
                'resposta' => $response->json() ?? $response->body(),
            ]);

            self::registrarLogIntegracao('integracao_sucesso_put', 'orcamentos', $orcamento->id, ['url' => $urlComParams, 'payload' => $payload], $response->json() ?? $response->body());

            OrcamentoAvaliacao::create([
                'orcamento_id'    => $orcamento->id,
                'user_id'         => auth()->id() ?? 1,
                'user_nome'       => auth()->user()->name ?? 'Sistema',
                'status_aplicado' => 'Finalizado – Integrado ao Protheus',
                'comentario'      => "Orçamento enviado via PUT e integrado com sucesso à Tabela Intermediária do Protheus (" . count($payload['despesas']) . " itens).",
                'tipo_evento'     => 'integracao_protheus',
            ]);

            return [
                'sucesso'  => true,
                'mensagem' => "Orçamento #{$orcamento->id} integrado com sucesso ao Protheus!",
                'payload'  => $payload,
                'resposta' => $response->json(),
            ];
        } catch (\Throwable $e) {
            Log::error("[INTEGRAÇÃO PROTHEUS - EXCEÇÃO PUT] Orçamento #{$orcamento->id}: " . $e->getMessage());
            
            self::registrarLogIntegracao('integracao_excecao_put', 'orcamentos', $orcamento->id, ['url' => $urlComParams, 'payload' => $payload], $e->getMessage());

            return [
                'sucesso'  => false,
                'mensagem' => 'Erro de comunicação ao enviar PUT para o Protheus: ' . $e->getMessage(),
                'payload'  => $payload,
            ];
        }
    }

    public function enviarParaProtheusSimulado(Orcamento $orcamento): array
    {
        $payload = $this->consolidarParaTabelaIntermediaria($orcamento);
        $orcamento->updateQuietly(['dados_consolidados' => $payload]);
        return $payload;
    }
}