<?php

namespace App\Modules\Matricula\Services;

use App\Modules\Matricula\Domain\Models\ConfiguracaoIa;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiValidationService
{
    public static function validarDocumento($inscricao, $documentoExigido, $arquivoPath)
    {
        $config = ConfiguracaoIa::first();

        if (!$config || !$config->is_ativa || empty($config->api_key)) {
            return ['valido' => false, 'motivo_rejeicao' => 'Validação automática desativada. Enviado para análise manual.'];
        }

        $dadosCandidato = "Nome: {$inscricao->nome} | CPF: {$inscricao->cpf}";
        $promptFinal = $config->prompt_documentos . "\n\nDados oficiais do candidato:\n{$dadosCandidato}\n\nDocumento que deve ser analisado: {$documentoExigido->nome}";

        $caminhoAbsoluto = storage_path('app/private/' . $arquivoPath);
        if (!file_exists($caminhoAbsoluto)) {
            return ['valido' => false, 'motivo_rejeicao' => 'Arquivo físico não encontrado no servidor.'];
        }

        $mimeType = mime_content_type($caminhoAbsoluto);
        $base64 = base64_encode(file_get_contents($caminhoAbsoluto));

        try {
            if ($config->provedor === 'gemini') {
                return self::chamarGemini($config->api_key, $promptFinal, $mimeType, $base64);
            } elseif ($config->provedor === 'openai') {
                return self::chamarOpenAiCompatible('https://api.openai.com/v1/chat/completions', 'gpt-4o-mini', $config->api_key, $promptFinal, $mimeType, $base64);
            } elseif ($config->provedor === 'deepseek') {
                return self::chamarOpenAiCompatible('https://api.deepseek.com/chat/completions', 'deepseek-v4-flash-vision-exp', $config->api_key, $promptFinal, $mimeType, $base64);
            } elseif ($config->provedor === 'grok') {
                return self::chamarOpenAiCompatible('https://api.x.ai/v1/chat/completions', 'grok-vision-beta', $config->api_key, $promptFinal, $mimeType, $base64);
            }
            
            return ['valido' => false, 'motivo_rejeicao' => 'Provedor de IA selecionado não configurado.'];

        } catch (\Exception $e) {
            Log::error("Erro na API da IA: " . $e->getMessage());
            return ['valido' => false, 'motivo_rejeicao' => 'Servidor de IA indisponível no momento.'];
        }
    }

    private static function chamarGemini($apiKey, $prompt, $mimeType, $base64)
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";

        $payload = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt],
                        ["inline_data" => ["mime_type" => $mimeType, "data" => $base64]]
                    ]
                ]
            ],
            // TRAVA DO GEMINI: Define o Schema JSON exato (Nome e Tipo das Colunas)
            "generationConfig" => [
                "response_mime_type" => "application/json",
                "response_schema" => [
                    "type" => "OBJECT",
                    "properties" => [
                        "valido" => [
                            "type" => "BOOLEAN",
                            "description" => "True se o documento for autêntico e pertencer ao candidato."
                        ],
                        "motivo_rejeicao" => [
                            "type" => "STRING",
                            "description" => "Justificativa caso seja inválido. Deixe vazio se for válido."
                        ]
                    ],
                    "required" => ["valido", "motivo_rejeicao"]
                ]
            ]
        ];

        $response = Http::timeout(30)->post($url, $payload);

        if ($response->successful()) {
            $resultado = json_decode($response->json('candidates.0.content.parts.0.text'), true);
            if (is_array($resultado)) {
                return [
                    'valido' => (bool) ($resultado['valido'] ?? false),
                    'motivo_rejeicao' => $resultado['motivo_rejeicao'] ?? '',
                    'raw' => $resultado
                ];
            }
        }
        return ['valido' => false, 'motivo_rejeicao' => 'A IA não conseguiu interpretar o documento.'];
    }

    private static function chamarOpenAiCompatible($url, $modelo, $apiKey, $prompt, $mimeType, $base64)
    {
        $payload = [
            "model" => $modelo,
            // TRAVA UNIVERSAL: Força as APIs Open-Source a não usarem formatação Markdown
            "response_format" => ["type" => "json_object"], 
            "messages" => [
                [
                    "role" => "system",
                    "content" => "Você é um auditor de RH rigoroso. Você OBRIGATORIAMENTE deve responder em formato JSON limpo contendo apenas as chaves boolean 'valido' e string 'motivo_rejeicao'."
                ],
                [
                    "role" => "user",
                    "content" => [
                        ["type" => "text", "text" => $prompt],
                        ["type" => "image_url", "image_url" => ["url" => "data:{$mimeType};base64,{$base64}"]]
                    ]
                ]
            ]
        ];

        $response = Http::withToken($apiKey)->timeout(45)->post($url, $payload);

        if ($response->successful()) {
            // O Regex foi completamente removido pois a chave 'json_object' garante a tipagem
            $resultado = json_decode($response->json('choices.0.message.content'), true);
            
            if (is_array($resultado)) {
                return [
                    'valido' => (bool) ($resultado['valido'] ?? false),
                    'motivo_rejeicao' => $resultado['motivo_rejeicao'] ?? '',
                    'raw' => $resultado
                ];
            }
        }

        Log::error("Erro API IA Compatível: " . $response->body());
        return ['valido' => false, 'motivo_rejeicao' => 'A IA rejeitou o envio da imagem.'];
    }
}