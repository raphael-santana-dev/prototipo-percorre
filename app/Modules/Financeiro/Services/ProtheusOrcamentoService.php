<?php

namespace App\Modules\Financeiro\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Modules\Financeiro\Domain\Models\Orcamento;
use App\Modules\Financeiro\Domain\Models\OrcamentoItem;

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

                foreach ($orcamentos as $item) {
                    
                    $filial = trim($item['filial'] ?? '');
                    $ano = trim($item['ano'] ?? '');
                    $natureza = trim($item['natureza'] ?? '');
                    $ccusto = trim($item['ccusto'] ?? '');

                    $chaveComposta = "{$filial}_{$ano}_{$natureza}_{$ccusto}";

                    // 1. Cria ou atualiza o Cabeçalho (Header) do Orçamento
                    // Usamos firstOrCreate para não sobrescrever o status caso o gestor já esteja a editar
                    $orcamento = Orcamento::firstOrCreate(
                        ['chave_composta' => $chaveComposta],
                        [
                            'filial'    => $filial,
                            'ano'       => $ano,
                            'natureza'  => $natureza,
                            'ccusto'    => $ccusto,
                            'moeda'     => $item['moeda'] ?? null,
                            'cmoeda'    => trim($item['cmoeda'] ?? ''),
                            'xcat'      => trim($item['xcat'] ?? ''),
                            'status'    => 'Criado', // Status inicial web
                            // Os valores agregados já não ficam no cabeçalho. 
                            // Podem ficar a 0, pois a soma virá dinamicamente dos itens.
                            'valor_jan' => 0, 'valor_fev' => 0, 'valor_mar' => 0,
                            'valor_abr' => 0, 'valor_mai' => 0, 'valor_jun' => 0,
                            'valor_jul' => 0, 'valor_ago' => 0, 'valor_set' => 0,
                            'valor_out' => 0, 'valor_nov' => 0, 'valor_dez' => 0,
                        ]
                    );

                    // 2. Lança o "Item Base" na tabela orcamento_itens
                    // Apenas insere os dados da API se este orçamento ainda não tiver nenhum item.
                    // Isto protege o trabalho do Gestor de ser apagado numa futura sincronização.
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

                return [
                    'sucesso' => true,
                    'mensagem' => "Sincronização concluída com sucesso! {$processados} orçamentos importados.",
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
                'mensagem' => "Erro de ligação ao tentar aceder à API do Protheus."
            ];
        }
    }
}