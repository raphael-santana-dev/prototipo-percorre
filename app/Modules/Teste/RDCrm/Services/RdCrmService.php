<?php

namespace App\Modules\Teste\RDCrm\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Modules\Teste\RDCrm\Domain\Models\RdCrmPipeline;
use App\Modules\Teste\RDCrm\Domain\Models\RdCrmDealStage;
use App\Modules\Teste\RDCrm\Domain\Models\RdCrmDeal;

class RdCrmService
{
    /**
     * Retorna a URL base já com o Token injetado
     */
    private static function getBaseUrl($endpoint)
    {
        $token = env('RD_CRM_TOKEN');
        return "https://crm.rdstation.com/api/v1/{$endpoint}?token={$token}";
    }

    /**
     * MÉTODOS GET: Busca e salva todos os Funis (Pipelines) e suas Etapas
     */
    public static function sincronizarFunis()
    {
        try {
            $response = Http::timeout(30)->get(self::getBaseUrl('deal_pipelines'));

            if ($response->successful()) {
                $pipelines = $response->json();
                $processados = 0;

                foreach ($pipelines as $pipe) {
                    // 1. Salva ou Atualiza o Funil
                    $funil = RdCrmPipeline::updateOrCreate(
                        ['rd_id' => $pipe['id']],
                        [
                            'nome' => $pipe['name'],
                            'ordem' => $pipe['order'] ?? 0,
                        ]
                    );

                    // 2. Salva as Etapas (Deal Stages) daquele Funil
                    if (isset($pipe['deal_stages']) && is_array($pipe['deal_stages'])) {
                        foreach ($pipe['deal_stages'] as $stage) {
                            RdCrmDealStage::updateOrCreate(
                                ['rd_id' => $stage['id']],
                                [
                                    'rd_crm_pipeline_id' => $funil->id,
                                    'nome' => $stage['name'],
                                    'nickname' => $stage['nickname'] ?? null,
                                    'ordem' => $stage['order'] ?? 0,
                                ]
                            );
                        }
                    }
                    $processados++;
                }

                return ['sucesso' => true, 'mensagem' => "{$processados} Funis sincronizados com sucesso."];
            }

            Log::error("Erro RD CRM GET Funis", ['status' => $response->status(), 'body' => $response->body()]);
            return ['sucesso' => false, 'mensagem' => "Erro na API do RD CRM: " . $response->status()];

        } catch (\Exception $e) {
            Log::error("Exceção RD CRM GET", ['erro' => $e->getMessage()]);
            return ['sucesso' => false, 'mensagem' => "Erro de conexão com o RD Station CRM."];
        }
    }

    public static function cadastrarFunilVendas($nomeFunil, $etapasNomes)
    {
        $dealStages = [];
        
        // 1. Formata as etapas do Livewire para o padrão do RD
        foreach ($etapasNomes as $index => $etapa) {
            $dealStages[] = [
                "name" => $etapa['nome'],
                // Usa o ajudante do Laravel para gerar o slug (Ex: "Em Negociação" vira "em_negociacao")
                "nickname" => \Illuminate\Support\Str::slug($etapa['nome'], '_')
            ];
        }

        // 2. O Segredo: Empacotar tudo dentro de "deal_pipeline"
        $payload = [
            "deal_pipeline" => [
                "name" => $nomeFunil,
                "deal_stages" => $dealStages
            ]
        ];

        try {
            // 3. Envia o POST de criação
            $response = Http::timeout(30)->post(self::getBaseUrl('deal_pipelines'), $payload);

            if ($response->successful()) {
                $dados = $response->json();
                $pipelineId = $dados['id'] ?? $dados['_id'];

                $etapasPadroes = $dados['deal_stages'] ?? [];

                foreach ($etapasNomes as $index => $etapaCustom) {
                    $slug = \Illuminate\Support\Str::slug($etapaCustom['nome'], '_');

                    $payloadEtapa = [
                        "deal_stage" => [
                            "name" => $etapaCustom['nome'],
                            "nickname" => $slug,
                        ]
                    ];

                    if (isset($etapasPadroes[$index])) {
                        $stageId = $etapasPadroes[$index]['id'] ?? $etapasPadroes[$index]['_id'];
                        Http::timeout(30)->put(self::getBaseUrl("deal_stages/{$stageId}"), $payloadEtapa);
                    } else {
                        $payloadEtapa["deal_stage"]["deal_pipeline_id"] = $pipelineId;
                        Http::timeout(30)->post(self::getBaseUrl('deal_stages'), $payloadEtapa);
                    }
                }

                // 3. CENÁRIO C: O usuário pediu menos de 5 etapas. Precisamos deletar a sobra do padrão (DELETE).
                $qtdCustomizadas = count($etapasNomes);
                if (count($etapasPadroes) > $qtdCustomizadas) {
                    for ($i = $qtdCustomizadas; $i < count($etapasPadroes); $i++) {
                        $stageId = $etapasPadroes[$i]['id'] ?? $etapasPadroes[$i]['_id'];
                        Http::timeout(30)->delete(self::getBaseUrl("deal_stages/{$stageId}"));
                    }
                }

                return ['sucesso' => true, 'mensagem' => 'Funil criado e etapas sincronizadas perfeitamente!'];
            }

            Log::error("Erro RD CRM POST Funil", ['body' => $response->body()]);
            return ['sucesso' => false, 'mensagem' => 'O RD Station rejeitou a criação do funil.'];

        } catch (\Exception $e) {
            Log::error("Exceção RD CRM POST Funil", ['erro' => $e->getMessage()]);
            return ['sucesso' => false, 'mensagem' => 'Erro de conexão com o RD CRM.'];
        }
    }
    
    public static function enviarNegociacoesPendentes()
    {
        // Pega todos os Deals (com o Contato correspondente) que ainda não foram pro RD
        $dealsPendentes = RdCrmDeal::with('contact')->where('sincronizado', false)->get();
        $enviados = 0;

        foreach ($dealsPendentes as $deal) {
            $contato = $deal->contact;

            // 1. Monta os dados do Contato rigorosamente limpos
            $dadosContato = ["name" => $contato->nome];
            
            if (!empty($contato->email)) {
                $dadosContato["emails"] = [["email" => $contato->email]];
            }
            if (!empty($contato->telefone)) {
                $dadosContato["phones"] = [["phone" => preg_replace('/\D/', '', $contato->telefone), "type" => "cellphone"]];
            }
            if (!empty($contato->data_nascimento)) {
                $dataStr = \Carbon\Carbon::parse($contato->data_nascimento);
                $dadosContato['birthday'] = [
                    "day" => $dataStr->day, "month" => $dataStr->month, "year" => $dataStr->year
                ];
            }

            // 2. Monta os dados da Negociação (Deal) limpando os nulos
            $dadosDeal = [
                "name" => $deal->nome,
                "rating" => $deal->rating,
            ];

            if (!empty($deal->deal_stage_id)) {
                $dadosDeal["deal_stage_id"] = $deal->deal_stage_id;
            }
            if (!empty($deal->campos_customizados)) {
                $dadosDeal["deal_custom_fields"] = $deal->campos_customizados;
            }

            // 3. Empacota tudo para envio
            $payload = [
                "deal" => $dadosDeal,
                "contacts" => [$dadosContato]
            ];

            if (!empty($deal->campaign_id)) {
                $payload['campaign'] = ["_id" => $deal->campaign_id];
            }

            try {
                // Envia o POST para a API do RD
                $response = Http::timeout(30)->post(self::getBaseUrl('deals'), $payload);

                if ($response->successful()) {
                    $retornoRd = $response->json();
                    
                    $deal->update([
                        'sincronizado' => true,
                        'rd_id' => $retornoRd['id'] ?? null
                    ]);

                    $enviados++;
                } else {
                    // SE O RD REJEITAR, GRAVA O MOTIVO EXATO!
                    Log::error("Erro RD CRM POST Deal ID {$deal->id}", [
                        'status' => $response->status(),
                        'resposta_do_rd' => $response->body()
                    ]);
                }

            } catch (\Exception $e) {
                Log::error("Exceção RD CRM POST Deal ID {$deal->id}", ['erro' => $e->getMessage()]);
            }
        }

        return ['sucesso' => true, 'enviados' => $enviados];
    }
}