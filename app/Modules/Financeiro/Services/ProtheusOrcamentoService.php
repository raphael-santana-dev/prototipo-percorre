<?php

namespace App\Modules\Financeiro\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Modules\Financeiro\Domain\Models\Orcamento;
use App\Modules\Financeiro\Domain\Models\OrcamentoItem;
use App\Modules\Financeiro\Domain\Models\Natureza;
use Illuminate\Support\Facades\Storage;

class ProtheusOrcamentoService
{
    public static function sincronizar()
    {
        $url = env('PROTHEUS_API_URL');
        $user = env('PROTHEUS_API_USER');
        $password = env('PROTHEUS_API_PASSWORD');

        if (!$url || !$user || !$password) {
            return ['sucesso' => false, 'mensagem' => 'Credenciais Protheus ausentes.'];
        }

        try {
            $response = Http::withBasicAuth($user, $password)->timeout(60)->withOptions(['verify' => false])->get($url);

            if ($response->successful()) {
                $dados = $response->json();
                if (!isset($dados['orcamentos']) || !is_array($dados['orcamentos'])) return ['sucesso' => false, 'mensagem' => 'Formato de resposta inválido.'];

                $orcamentos = $dados['orcamentos'];
                $processados = 0;
                $orcamentosAgrupados = [];

                foreach ($orcamentos as $item) {
                    $filial = trim($item['filial'] ?? '');
                    $ano = trim($item['ano'] ?? '');
                    $naturezaCod = trim($item['natureza'] ?? ''); 
                    $ccusto = trim($item['ccusto'] ?? '');

                    if (!empty($naturezaCod)) Natureza::firstOrCreate(['codigo' => $naturezaCod], ['descricao' => 'Natureza Importada (API)', 'disponivel_orcamento' => true]);

                    $chaveHeader = "{$filial}_{$ano}_{$ccusto}";

                    if (!isset($orcamentosAgrupados[$chaveHeader])) {
                        $orcamentosAgrupados[$chaveHeader] = [
                            'filial' => $filial, 'ano' => $ano, 'ccusto' => $ccusto,
                            'moeda' => $item['moeda'] ?? null, 'cmoeda' => trim($item['cmoeda'] ?? ''),
                            'xcat' => trim($item['xcat'] ?? ''), 'itens' => []
                        ];
                    }

                    $orcamentosAgrupados[$chaveHeader]['itens'][] = [
                        'natureza_codigo' => $naturezaCod,
                        'valor_jan' => $item['valor_jan'] ?? 0, 'valor_fev' => $item['valor_fev'] ?? 0,
                        'valor_mar' => $item['valor_mar'] ?? 0, 'valor_abr' => $item['valor_abr'] ?? 0,
                        'valor_mai' => $item['valor_mai'] ?? 0, 'valor_jun' => $item['valor_jun'] ?? 0,
                        'valor_jul' => $item['valor_jul'] ?? 0, 'valor_ago' => $item['valor_ago'] ?? 0,
                        'valor_set' => $item['valor_set'] ?? 0, 'valor_out' => $item['valor_out'] ?? 0,
                        'valor_nov' => $item['valor_nov'] ?? 0, 'valor_dez' => $item['valor_dez'] ?? 0,
                    ];
                }

                foreach ($orcamentosAgrupados as $chaveComposta => $dados) {
                    $orcamento = Orcamento::firstOrCreate(['chave_composta' => $chaveComposta], [
                        'filial' => $dados['filial'], 'ano' => $dados['ano'], 'ccusto' => $dados['ccusto'],
                        'moeda' => $dados['moeda'], 'cmoeda' => $dados['cmoeda'], 'xcat' => $dados['xcat'], 'status' => 'Criado'
                    ]);

                    if ($orcamento->itens()->count() === 0) {
                        foreach ($dados['itens'] as $linha) {
                            $naturezaObj = Natureza::where('codigo', $linha['natureza_codigo'])->first();
                            OrcamentoItem::create([
                                'orcamento_id'    => $orcamento->id,
                                'natureza_codigo' => $linha['natureza_codigo'],
                                'descricao'       => $naturezaObj ? $naturezaObj->descricao : 'Natureza Importada',
                                'previsto_jan'    => $linha['valor_jan'], 'valor_jan' => $linha['valor_jan'],
                                'previsto_fev'    => $linha['valor_fev'], 'valor_fev' => $linha['valor_fev'],
                                'previsto_mar'    => $linha['valor_mar'], 'valor_mar' => $linha['valor_mar'],
                                'previsto_abr'    => $linha['valor_abr'], 'valor_abr' => $linha['valor_abr'],
                                'previsto_mai'    => $linha['valor_mai'], 'valor_mai' => $linha['valor_mai'],
                                'previsto_jun'    => $linha['valor_jun'], 'valor_jun' => $linha['valor_jun'],
                                'previsto_jul'    => $linha['valor_jul'], 'valor_jul' => $linha['valor_jul'],
                                'previsto_ago'    => $linha['valor_ago'], 'valor_ago' => $linha['valor_ago'],
                                'previsto_set'    => $linha['valor_set'], 'valor_set' => $linha['valor_set'],
                                'previsto_out'    => $linha['valor_out'], 'valor_out' => $linha['valor_out'],
                                'previsto_nov'    => $linha['valor_nov'], 'valor_nov' => $linha['valor_nov'],
                                'previsto_dez'    => $linha['valor_dez'], 'valor_dez' => $linha['valor_dez'],
                            ]);
                        }
                    }
                    $processados++;
                }
                return ['sucesso' => true, 'mensagem' => "Sincronização concluída! {$processados} Centros de Custo importados."];
            }
            return ['sucesso' => false, 'mensagem' => "Erro na API do Protheus."];
        } catch (\Exception $e) {
            return ['sucesso' => false, 'mensagem' => "Erro de ligação à API do Protheus."];
        }
    }

    /**
     * Consolida os itens do orçamento no formato exato da Tabela Intermediária do Protheus.
     */
    public function consolidarParaTabelaIntermediaria(Orcamento $orcamento): array
    {
        $orcamento->loadMissing(['centroCusto', 'itens.natureza']);

        $ccCodigo = trim((string) $orcamento->ccusto);
        $ccDescricaoVirtual = $orcamento->centroCusto 
            ? $orcamento->centroCusto->nome 
            : 'Centro de Custo (' . $ccCodigo . ')';

        // Agrupa por código de natureza caso existam linhas com a mesma natureza no orçamento
        $itensAgrupados = $orcamento->itens->groupBy('natureza_codigo');
        $linhasIntermediarias = [];

        foreach ($itensAgrupados as $codigoNatureza => $grupoItens) {
            $primeiroItem = $grupoItens->first();
            $natObj = $primeiroItem->natureza;

            $naturezaDescricaoVirtual = $natObj 
                ? $natObj->descricao 
                : 'Natureza Importada (' . $codigoNatureza . ')';

            $categoria = $natObj->categoria ?? ($primeiroItem->categoria ?? 'Despesa Orçamentária');

            // Concatena as observações caso mais de uma linha da mesma natureza tenha anotações
            $descricoes = $grupoItens->pluck('descricao')
                ->map(fn($d) => trim((string) $d))
                ->filter()
                ->unique()
                ->implode(' | ');

            $linhasIntermediarias[] = [
                'ano'                        => (int) $orcamento->ano,
                'codigo_centro_custo'        => $ccCodigo,
                'descricao_centro_custo'     => $ccDescricaoVirtual, // Campo virtual
                'codigo_natureza'            => trim((string) $codigoNatureza),
                'descricao_natureza'         => $naturezaDescricaoVirtual, // Campo virtual
                'categoria'                  => $categoria,
                'descricao_despesa'          => $descricoes ?: $naturezaDescricaoVirtual,
                'status'                     => $orcamento->status, // Criado | Em elaboração | Finalizado
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
                'status_enviado'       => $orcamento->status,
                'total_linhas'         => count($linhasIntermediarias),
                'data_sincronizacao'   => now()->toIso8601String(),
                'usuario_responsavel'  => auth()->user()->name ?? 'Sistema',
                'codigo_user_protheus' => auth()->user()->codigo_protheus ?? null,
            ],
            'tabela_intermediaria' => $linhasIntermediarias,
        ];
    }

    /**
     * Simula o envio para o WebService do Protheus gravando o JSON em disco e no Log.
     */
    public function enviarParaProtheusSimulado(Orcamento $orcamento): array
    {
        $payload = $this->consolidarParaTabelaIntermediaria($orcamento);

        // Grava o arquivo JSON em storage/app/protheus_sync/ para inspeção e documentação da API
        $fileName = sprintf(
            'protheus_sync/orcamento_cc_%s_ano_%s_id_%s.json',
            $orcamento->ccusto,
            $orcamento->ano,
            $orcamento->id
        );

        Storage::disk('local')->put(
            $fileName,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        Log::info("[SIMULAÇÃO PROTHEUS] Tabela Intermediária atualizada para o Orçamento #{$orcamento->id}", $payload);

        // Registra no histórico de auditoria do orçamento
        OrcamentoAvaliacao::create([
            'orcamento_id'    => $orcamento->id,
            'user_id'         => auth()->id(),
            'user_nome'       => auth()->user()->name ?? 'Sistema',
            'status_aplicado' => 'Sincronizado (Protheus Mock)',
            'comentario'      => "Dados consolidados (" . count($payload['tabela_intermediaria']) . " naturezas) enviados para a Tabela Intermediária do Protheus. Arquivo gerado: storage/app/{$fileName}",
            'tipo_evento'     => 'integracao_protheus',
        ]);

        return $payload;
    }
}