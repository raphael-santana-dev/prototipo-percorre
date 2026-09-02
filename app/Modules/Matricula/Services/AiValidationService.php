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
            // Roteamento baseado na escolha do Painel
            if ($config->provedor === 'gemini') {
                return self::chamarGemini($config->api_key, $promptFinal, $mimeType, $base64);
            } elseif ($config->provedor === 'openai') {
                return self::chamarOpenAiCompatible('https://api.openai.com/v1/chat/completions', 'gpt-4o-mini', $config->api_key, $promptFinal, $mimeType, $base64);
            } elseif ($config->provedor === 'deepseek') {
                return self::chamarOpenAiCompatible('https://api.deepseek.com/chat/completions', 'deepseek-chat', $config->api_key, $promptFinal, $mimeType, $base64);
            }
            
            return ['valido' => false, 'motivo_rejeicao' => 'Provedor de IA selecionado ainda não foi configurado no código.'];

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
            "generationConfig" => [
                "response_mime_type" => "application/json"
            ]
        ];

        $response = Http::timeout(30)->post($url, $payload);

        if ($response->successful()) {
            $jsonRaw = $response->json('candidates.0.content.parts.0.text');
            $resultado = json_decode($jsonRaw, true);
            
            if (is_array($resultado)) {
                return [
                    'valido' => (bool) ($resultado['valido'] ?? false),
                    'motivo_rejeicao' => $resultado['motivo_rejeicao'] ?? 'Documento ilegível ou incorreto.',
                    'raw' => $resultado
                ];
            }
        }

        return ['valido' => false, 'motivo_rejeicao' => 'A IA não conseguiu interpretar o documento.'];
    }

    // Método Universal que serve para OpenAI, DeepSeek, Grok, etc.
    private static function chamarOpenAiCompatible($url, $modelo, $apiKey, $prompt, $mimeType, $base64)
    {
        $payload = [
            "model" => $modelo,
            "messages" => [
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
        dd($response->body()); // Debug: mostra a resposta da IA para análise
        if ($response->successful()) {
            $conteudo = $response->json('choices.0.message.content');
            
            // IAs costumam responder envelopando o JSON em markdown. Esse regex limpa isso.
            $conteudo = preg_replace('/```json\s*(.*?)\s*```/s', '$1', $conteudo);
            $conteudo = preg_replace('/```\s*(.*?)\s*```/s', '$1', $conteudo);
            
            $resultado = json_decode(trim($conteudo), true);
            
            if (is_array($resultado)) {
                return [
                    'valido' => (bool) ($resultado['valido'] ?? false),
                    'motivo_rejeicao' => $resultado['motivo_rejeicao'] ?? 'Erro na validação.',
                    'raw' => $resultado
                ];
            }
        }

        Log::error("Erro API IA Compatível: " . $response->body());
        return ['valido' => false, 'motivo_rejeicao' => 'A IA rejeitou o envio. O provedor pode não suportar análise de imagens.'];
    }
}