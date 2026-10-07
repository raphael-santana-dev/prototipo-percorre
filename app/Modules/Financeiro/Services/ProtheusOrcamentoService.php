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
            return [
                'sucesso' => false,
                'mensagem' => 'As credenciais ou o URL da API do Protheus não estão configuradas no ficheiro .env.'
            ];
        }

        try {
            $response = Http::withBasicAuth($user, $password)
                ->timeout(60) 
                ->withOptions(['verify' => false])
                ->get($url);

            if ($response->successful()) {
                $dados = $response->json();
                
                if (!isset($dados['orcamentos']) || !is_array($dados['orcamentos'])) {
                    return [
                        'sucesso' => false,
                        'mensagem' => 'O formato da resposta não contém a chave "orcamentos" ou está vazio.'
                    ];
                }

                $orcamentos = $dados['orcamentos'];
                $processados = 0;
                $naturezasCriadas = 0;

                // 1. Agrupar os dados por Filial + Ano + Centro de Custo
                $orcamentosAgrupados = [];

                foreach ($orcamentos as $item) {
                    $filial = trim($item['filial'] ?? '');
                    $ano = trim($item['ano'] ?? '');
                    $naturezaCod = trim($item['natureza'] ?? ''); 
                    $ccusto = trim($item['ccusto'] ?? '');

                    // Auto-cadastro da Natureza caso não exista
                    if (!empty($naturezaCod)) {
                        $naturezaModel = Natureza::firstOrCreate(
                            ['codigo' => $naturezaCod],
                            [
                                'descricao' => 'Natureza Importada (API)', 
                                'disponivel_orcamento' => true
                            ]
                        );
                        
                        if ($naturezaModel->wasRecentlyCreated) {
                            $naturezasCriadas++;
                        }
                    }

                    // A chave do cabeçalho agora é independente da Natureza
                    $chaveHeader = "{$filial}_{$ano}_{$ccusto}";

                    if (!isset($orcamentosAgrupados[$chaveHeader])) {
                        $orcamentosAgrupados[$chaveHeader] = [
                            'filial' => $filial,
                            'ano'    => $ano,
                            'ccusto' => $ccusto,
                            'moeda'  => $item['moeda'] ?? null,
                            'cmoeda' => trim($item['cmoeda'] ?? ''),
                            'xcat'   => trim($item['xcat'] ?? ''),
                            'itens'  => []
                        ];
                    }

                    // Adiciona a Natureza como uma linha (Item) dentro deste Cabeçalho
                    $orcamentosAgrupados[$chaveHeader]['itens'][] = [
                        'natureza_codigo' => $naturezaCod,
                        'valor_jan' => $item['valor_jan'] ?? 0,
                        'valor_fev' => $item['valor_fev'] ?? 0,
                        'valor_mar' => $item['valor_mar'] ?? 0,
                        'valor_abr' => $item['valor_abr'] ?? 0,
                        'valor_mai' => $item['valor_mai'] ?? 0,
                        'valor_jun' => $item['valor_jun'] ?? 0,
                        'valor_jul' => $item['valor_jul'] ?? 0,
                        'valor_ago' => $item['valor_ago'] ?? 0,
                        'valor_set' => $item['valor_set'] ?? 0,
                        'valor_out' => $item['valor_out'] ?? 0,
                        'valor_nov' => $item['valor_nov'] ?? 0,
                        'valor_dez' => $item['valor_dez'] ?? 0,
                    ];
                }

                // 2. Gravar os Cabeçalhos e as linhas da Planilha
                foreach ($orcamentosAgrupados as $chaveComposta => $dados) {
                    
                    $orcamento = Orcamento::firstOrCreate(
                        ['chave_composta' => $chaveComposta],
                        [
                            'filial'    => $dados['filial'],
                            'ano'       => $dados['ano'],
                            'ccusto'    => $dados['ccusto'],
                            'moeda'     => $dados['moeda'],
                            'cmoeda'    => $dados['cmoeda'],
                            'xcat'      => $dados['xcat'],
                            'status'    => 'Criado', 
                        ]
                    );

                    // Apenas insere os itens da API se o orçamento estiver vazio.
                    // Isso evita apagar o trabalho do gestor nas futuras sincronizações.
                    if ($orcamento->itens()->count() === 0) {
                        foreach ($dados['itens'] as $linha) {
                            
                            // Obtém a descrição da Natureza para popular a linha de forma legível
                            $naturezaObj = Natureza::where('codigo', $linha['natureza_codigo'])->first();
                            $descricaoLinha = $naturezaObj ? $naturezaObj->descricao : 'Natureza Importada';

                            OrcamentoItem::create([
                                'orcamento_id'    => $orcamento->id,
                                'natureza_codigo' => $linha['natureza_codigo'],
                                'descricao'       => $descricaoLinha,
                                'valor_jan'       => $linha['valor_jan'],
                                'valor_fev'       => $linha['valor_fev'],
                                'valor_mar'       => $linha['valor_mar'],
                                'valor_abr'       => $linha['valor_abr'],
                                'valor_mai'       => $linha['valor_mai'],
                                'valor_jun'       => $linha['valor_jun'],
                                'valor_jul'       => $linha['valor_jul'],
                                'valor_ago'       => $linha['valor_ago'],
                                'valor_set'       => $linha['valor_set'],
                                'valor_out'       => $linha['valor_out'],
                                'valor_nov'       => $linha['valor_nov'],
                                'valor_dez'       => $linha['valor_dez'],
                            ]);
                        }
                    }

                    $processados++;
                }

                $msgComplemento = $naturezasCriadas > 0 ? " ({$naturezasCriadas} novas naturezas registadas)." : ".";

                return [
                    'sucesso' => true,
                    'mensagem' => "Sincronização concluída! {$processados} Centros de Custo processados" . $msgComplemento,
                    'total' => $processados
                ];

            }

            Log::error("Erro API Protheus Orçamentos HTTP", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'sucesso' => false,
                'mensagem' => "A API do Protheus retornou um erro HTTP: " . $response->status()
            ];

        } catch (\Exception $e) {
            Log::error("Exceção Crítica API Protheus", ['erro' => $e->getMessage()]);
            return [
                'sucesso' => false,
                'mensagem' => "Erro de ligação ao tentar ler a API do Protheus."
            ];
        }
    }
}