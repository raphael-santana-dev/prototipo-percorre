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
                'mensagem' => 'As credenciais ou o URL da API do Protheus não estão configuradas no arquivo .env.'
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

                foreach ($orcamentos as $item) {
                    
                    $filial = trim($item['filial'] ?? '');
                    $ano = trim($item['ano'] ?? '');
                    // Faz o trim para limpar os espaços em branco que vêm da API
                    $naturezaCod = trim($item['natureza'] ?? ''); 
                    $ccusto = trim($item['ccusto'] ?? '');

                    // --- AUTOCADASTRO DA NATUREZA ---
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
                    // --------------------------------

                    // A chave composta deve manter a rastreabilidade exata do Protheus
                    $chaveComposta = "{$filial}_{$ano}_{$naturezaCod}_{$ccusto}";

                    // 1. Cria ou atualiza o Cabeçalho (Header) do Orçamento
                    $orcamento = Orcamento::firstOrCreate(
                        ['chave_composta' => $chaveComposta],
                        [
                            'filial'    => $filial,
                            'ano'       => $ano,
                            'natureza'  => $naturezaCod,
                            'ccusto'    => $ccusto,
                            'moeda'     => $item['moeda'] ?? null,
                            'cmoeda'    => trim($item['cmoeda'] ?? ''),
                            'xcat'      => trim($item['xcat'] ?? ''),
                            'status'    => 'Criado', 
                            'valor_jan' => 0, 'valor_fev' => 0, 'valor_mar' => 0,
                            'valor_abr' => 0, 'valor_mai' => 0, 'valor_jun' => 0,
                            'valor_jul' => 0, 'valor_ago' => 0, 'valor_set' => 0,
                            'valor_out' => 0, 'valor_nov' => 0, 'valor_dez' => 0,
                        ]
                    );

                    // 2. Lança o "Item Base" na tabela orcamento_itens, APENAS se estiver vazio
                    if ($orcamento->itens()->count() === 0) {
                        OrcamentoItem::create([
                            'orcamento_id' => $orcamento->id,
                            'descricao'    => 'Orçamento Base (Importado do Protheus)',
                            'valor_jan'    => $item['valor_jan'] ?? 0,
                            'valor_fev'    => $item['valor_fev'] ?? 0,
                            'valor_mar'    => $item['valor_mar'] ?? 0,
                            'valor_abr'    => $item['valor_abr'] ?? 0,
                            'valor_mai'    => $item['valor_mai'] ?? 0,
                            'valor_jun'    => $item['valor_jun'] ?? 0,
                            'valor_jul'    => $item['valor_jul'] ?? 0,
                            'valor_ago'    => $item['valor_ago'] ?? 0,
                            'valor_set'    => $item['valor_set'] ?? 0,
                            'valor_out'    => $item['valor_out'] ?? 0,
                            'valor_nov'    => $item['valor_nov'] ?? 0,
                            'valor_dez'    => $item['valor_dez'] ?? 0,
                        ]);
                    }

                    $processados++;
                }

                $msgComplemento = $naturezasCriadas > 0 ? " ({$naturezasCriadas} novas naturezas cadastradas automaticamente)." : ".";

                return [
                    'sucesso' => true,
                    'mensagem' => "Sincronização concluída! {$processados} orçamentos processados" . $msgComplemento,
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
                'mensagem' => "Erro de conexão ao tentar ler a API do Protheus."
            ];
        }
    }
}