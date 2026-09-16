<?php

namespace App\Modules\Financeiro\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Modules\Financeiro\Domain\Models\Orcamento;

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
                'mensagem' => 'Credenciais ou URL da API do Protheus não estão configuradas no arquivo .env.'
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

                foreach ($orcamentos as $item) {
                    
                    $filial = trim($item['filial'] ?? '');
                    $ano = trim($item['ano'] ?? '');
                    $natureza = trim($item['natureza'] ?? '');
                    $ccusto = trim($item['ccusto'] ?? '');

                    $chaveComposta = "{$filial}_{$ano}_{$natureza}_{$ccusto}";

                    Orcamento::updateOrCreate(
                        ['chave_composta' => $chaveComposta],
                        [
                            'filial'    => $filial,
                            'ano'       => $ano,
                            'natureza'  => $natureza,
                            'moeda'     => $item['moeda'] ?? null,
                            'cmoeda'    => trim($item['cmoeda'] ?? ''),
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
                            'ccusto'    => $ccusto,
                            'xcat'      => trim($item['xcat'] ?? ''),
                        ]
                    );

                    $processados++;
                }

                return [
                    'sucesso' => true,
                    'mensagem' => "Sincronização concluída! {$processados} orçamentos atualizados.",
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