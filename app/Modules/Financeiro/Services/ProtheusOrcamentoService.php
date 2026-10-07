<?php

namespace App\Modules\Financeiro\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Modules\Financeiro\Domain\Models\Orcamento;
use App\Modules\Financeiro\Domain\Models\OrcamentoItem;
use App\Modules\Financeiro\Domain\Models\Natureza;

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
}