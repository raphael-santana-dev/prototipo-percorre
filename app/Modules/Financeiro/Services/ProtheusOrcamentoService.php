<?php

namespace App\Modules\Financeiro\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Modules\Financeiro\Domain\Models\Orcamento;
use App\Modules\Financeiro\Domain\Models\OrcamentoItem;
use App\Modules\Financeiro\Domain\Models\Natureza;
use App\Modules\Financeiro\Domain\Models\OrcamentoAvaliacao;

class ProtheusOrcamentoService
{
    public static function sincronizar()
    {
        $url = env('PROTHEUS_API_URL');
        $user = env('PROTHEUS_API_USER');
        $password = env('PROTHEUS_API_PASSWORD');

        if (!$url || !$user || !$password) {
            return ['sucesso' => false, 'mensagem' => 'Credenciais Protheus ausentes nas variáveis de ambiente.'];
        }

        try {
            $response = Http::withBasicAuth($user, $password)->timeout(60)->withOptions(['verify' => false])->get($url);

            if ($response->successful()) {
                $dados = $response->json();
                
                // Permite ler tanto um objeto único quanto uma lista de objetos do JSON
                $orcamentosApi = isset($dados['orcamentos']) ? $dados['orcamentos'] : (is_array($dados) && !isset($dados['ano']) ? $dados : [$dados]);
                
                $processados = 0;

                foreach ($orcamentosApi as $item) {
                    $ano = trim($item['ano'] ?? '');
                    $ccusto = trim($item['ccusto'] ?? '');
                    $descricaoCC = trim($item['descricao'] ?? '');
                    $filial = '0101'; // Fallback padrão caso a API não envie a filial no novo formato

                    if(empty($ano) || empty($ccusto)) continue;

                    $chaveHeader = "{$filial}_{$ano}_{$ccusto}";

                    $orcamento = Orcamento::firstOrCreate(['chave_composta' => $chaveHeader], [
                        'filial' => $filial, 
                        'ano' => $ano, 
                        'ccusto' => $ccusto,
                        'descricao_despesa' => $descricaoCC,
                        'status' => 'Criado'
                    ]);

                    if (isset($item['despesas']) && is_array($item['despesas'])) {
                        foreach ($item['despesas'] as $despesa) {
                            $naturezaCod = trim($despesa['natureza'] ?? '');
                            $naturezaDesc = trim($despesa['descricaoNatureza'] ?? 'Natureza Importada');
                            
                            if (!empty($naturezaCod)) {
                                Natureza::firstOrCreate(['codigo' => $naturezaCod], ['descricao' => $naturezaDesc, 'disponivel_orcamento' => true]);
                            }

                            // O novo JSON traz os meses num array 'valores' de 0 a 11
                            $valores = $despesa['valores'] ?? array_fill(0, 12, 0);

                            OrcamentoItem::updateOrCreate([
                                'orcamento_id' => $orcamento->id,
                                'natureza_codigo' => $naturezaCod,
                                'descricao' => trim($despesa['descricao'] ?? '')
                            ], [
                                'previsto_jan' => $valores[0] ?? 0, 'valor_jan' => $valores[0] ?? 0,
                                'previsto_fev' => $valores[1] ?? 0, 'valor_fev' => $valores[1] ?? 0,
                                'previsto_mar' => $valores[2] ?? 0, 'valor_mar' => $valores[2] ?? 0,
                                'previsto_abr' => $valores[3] ?? 0, 'valor_abr' => $valores[3] ?? 0,
                                'previsto_mai' => $valores[4] ?? 0, 'valor_mai' => $valores[4] ?? 0,
                                'previsto_jun' => $valores[5] ?? 0, 'valor_jun' => $valores[5] ?? 0,
                                'previsto_jul' => $valores[6] ?? 0, 'valor_jul' => $valores[6] ?? 0,
                                'previsto_ago' => $valores[7] ?? 0, 'valor_ago' => $valores[7] ?? 0,
                                'previsto_set' => $valores[8] ?? 0, 'valor_set' => $valores[8] ?? 0,
                                'previsto_out' => $valores[9] ?? 0, 'valor_out' => $valores[9] ?? 0,
                                'previsto_nov' => $valores[10] ?? 0, 'valor_nov' => $valores[10] ?? 0,
                                'previsto_dez' => $valores[11] ?? 0, 'valor_dez' => $valores[11] ?? 0,
                            ]);
                        }
                    }
                    $processados++;
                }
                return ['sucesso' => true, 'mensagem' => "Sincronização concluída! {$processados} orçamentos processados."];
            }
            return ['sucesso' => false, 'mensagem' => "Erro na API do Protheus."];
        } catch (\Exception $e) {
            return ['sucesso' => false, 'mensagem' => "Erro de ligação à API do Protheus: " . $e->getMessage()];
        }
    }

    public function consolidarParaTabelaIntermediaria(Orcamento $orcamento): array
    {
        $orcamento->loadMissing(['centroCusto', 'itens.natureza']);

        $ccCodigo = trim((string) $orcamento->ccusto);
        $ccDescricaoVirtual = $orcamento->centroCusto ? $orcamento->centroCusto->nome : 'Centro de Custo (' . $ccCodigo . ')';

        $itensValidos = $orcamento->itens->where('status', '!=', 'Reprovado');
        $itensAgrupados = $itensValidos->groupBy('natureza_codigo');
        $linhasIntermediarias = [];

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

            $linhasIntermediarias[] = [
                'ano'                        => (int) $orcamento->ano,
                'codigo_centro_custo'        => $ccCodigo,
                'descricao_centro_custo'     => $ccDescricaoVirtual,
                'codigo_natureza'            => trim((string) $codigoNatureza),
                'descricao_natureza'         => $naturezaDescricaoVirtual,
                'categoria'                  => $categoria,
                'descricao_despesa'          => $descricoes ?: $naturezaDescricaoVirtual,
                'status'                     => 'Finalizado', 
                'valor_previsto_janeiro'     => round((float) $grupoItens->sum('valor_jan'), 2),
                'valor_previsto_fevereiro'   => round((float) $grupoItens->sum('valor_fev'), 2),
                'valor_previsto_marco'       => round((float) $grupoItens->sum('valor_mar'), 2),
                'valor_previsto_abril'       => round((float) $grupoItens->sum('valor_abr'), 2),
                'valor_previsto_maio'        => round((float) $grupoItens->sum('valor_mai'), 2),
                'valor_previsto_junho'       => round((float) $grupoItens->sum('valor_jun'), 2),
                'valor_previsto_julho'       => round((float) $grupoItens->sum('valor_jul'), 2),
                'valor_previsto_agosto'      => round((float) $grupoItens->sum('valor_ago'), 2),
                'valor_previsto_setembro'    => round((float) $grupoItens->sum('valor_set'), 2),
                'valor_previsto_outubro'     => round((float) $grupoItens->sum('valor_out'), 2),
                'valor_previsto_novembro'    => round((float) $grupoItens->sum('valor_nov'), 2),
                'valor_previsto_dezembro'    => round((float) $grupoItens->sum('valor_dez'), 2),
            ];
        }

        return [
            'meta' => [
                'orcamento_web_id'     => $orcamento->id,
                'filial'               => $orcamento->filial,
                'ano'                  => (int) $orcamento->ano,
                'centro_custo'         => $ccCodigo,
                'status_orcamento_web' => $orcamento->status,
                'total_naturezas'      => count($linhasIntermediarias),
                'data_consolidacao'    => now()->toIso8601String(),
                'aprovado_por'         => auth()->user()->name ?? 'Sistema',
            ],
            'tabela_intermediaria' => $linhasIntermediarias,
        ];
    }

    public function enviarParaProtheusSimulado(Orcamento $orcamento): array
    {
        $payload = $this->consolidarParaTabelaIntermediaria($orcamento);

        $orcamento->updateQuietly(['dados_consolidados' => $payload]);

        Log::info("[SIMULAÇÃO PROTHEUS] Tabela Intermediária consolidada após aprovação do Orçamento #{$orcamento->id}", $payload);

        OrcamentoAvaliacao::create([
            'orcamento_id'    => $orcamento->id,
            'user_id'         => auth()->id() ?? 1,
            'user_nome'       => auth()->user()->name ?? 'Sistema',
            'status_aplicado' => 'Consolidado p/ Protheus',
            'comentario'      => "Orçamento aprovado e consolidado (" . count($payload['tabela_intermediaria']) . " naturezas) para a Tabela Intermediária do Protheus.",
            'tipo_evento'     => 'integracao_protheus',
        ]);

        return $payload;
    }
}