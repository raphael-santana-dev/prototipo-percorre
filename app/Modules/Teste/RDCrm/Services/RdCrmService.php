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

    /**
     * MÉTODOS POST: Varre a tabela local e envia os contatos não sincronizados para o RD
     */
    public static function enviarNegociacoesPendentes()
    {
        // Pega todos os Deals (com o Contato correspondente) que ainda não foram pro RD
        $dealsPendentes = RdCrmDeal::with('contact')->where('sincronizado', false)->get();
        $enviados = 0;

        foreach ($dealsPendentes as $deal) {
            $contato = $deal->contact;

            // Formata o Aniversário se existir
            $birthdayArray = null;
            if ($contato->data_nascimento) {
                $dataStr = \Carbon\Carbon::parse($contato->data_nascimento);
                $birthdayArray = [
                    "day" => $dataStr->day,
                    "month" => $dataStr->month,
                    "year" => $dataStr->year
                ];
            }

            // Monta o Payload JSON rigorosamente no formato do RD CRM
            $payload = [
                "deal" => [
                    "name" => $deal->nome,
                    "rating" => $deal->rating,
                    "deal_stage_id" => $deal->deal_stage_id,
                    "deal_custom_fields" => $deal->campos_customizados ?? []
                ],
                "contacts" => [
                    [
                        "name" => $contato->nome,
                        "emails" => $contato->email ? [["email" => $contato->email]] : [],
                        "phones" => $contato->telefone ? [["phone" => preg_replace('/\D/', '', $contato->telefone), "type" => "cellphone"]] : [],
                    ]
                ]
            ];

            if ($birthdayArray) {
                $payload['contacts'][0]['birthday'] = $birthdayArray;
            }

            if ($deal->campaign_id) {
                $payload['campaign'] = ["_id" => $deal->campaign_id];
            }

            try {
                // Envia o POST para a API do RD
                $response = Http::timeout(30)->post(self::getBaseUrl('deals'), $payload);

                if ($response->successful()) {
                    // Se a API retornar sucesso, a gente marca como enviado e guarda o ID gerado pelo RD
                    $retornoRd = $response->json();
                    
                    $deal->update([
                        'sincronizado' => true,
                        'rd_id' => $retornoRd['id'] ?? null
                    ]);

                    $enviados++;
                } else {
                    Log::error("Erro RD CRM POST Deal ID {$deal->id}", [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                }

            } catch (\Exception $e) {
                Log::error("Exceção RD CRM POST Deal ID {$deal->id}", ['erro' => $e->getMessage()]);
            }
        }

        return ['sucesso' => true, 'enviados' => $enviados];
    }
}