<?php

namespace App\Modules\Matricula\Services;

use App\Modules\Matricula\Domain\Models\ConfiguracaoIa;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiValidationService
{
    // 1. Validação Individual (Já existia)
    public static function validarDocumento($inscricao, $documentoExigido, $arquivoPath)
    {
        $config = ConfiguracaoIa::first();
        if (!$config || !$config->is_ativa || empty($config->api_key)) {
            return ['valido' => false, 'motivo_rejeicao' => 'Validação automática desativada. Enviado para análise manual.'];
        }

        $promptFinal = $config->prompt_documentos . "\n\nDados oficiais do candidato:\nNome: {$inscricao->nome} | CPF: {$inscricao->cpf}\n\nDocumento que deve ser analisado: {$documentoExigido->nome}";
        return self::processarEnvioIa($config, $promptFinal, $arquivoPath, 'individual');
    }

    // 2. Classificador de Lote (NOVO MÉTOD0)
    public static function classificarDocumentoLote($inscricao, $documentosExigidos, $arquivoPath)
    {
        $config = ConfiguracaoIa::first();
        if (!$config || !$config->is_ativa || empty($config->api_key)) {
            return ['documento_id' => 0, 'valido' => false, 'motivo_rejeicao' => 'Validação automática desativada.'];
        }

        $listaDocs = "";
        foreach($documentosExigidos as $d) {
            $listaDocs .= "- ID: {$d->id} | Documento: {$d->nome} | Descrição: {$d->descricao}\n";
        }

        $promptFinal = "Você é um classificador automático de matrículas.\nLista de documentos exigidos:\n{$listaDocs}\n\nDados do titular: Nome: {$inscricao->nome} | CPF: {$inscricao->cpf}\n\nAnalise a imagem: 1) Identifique a qual ID de documento da lista ela corresponde (retorne 0 se for foto aleatória ou ilegível). 2) Se identificou o ID, valide se a foto é autêntica e se os dados batem com o titular.";
        return self::processarEnvioIa($config, $promptFinal, $arquivoPath, 'lote');
    }

    // --- ROTEADOR UNIVERSAL ---
    private static function processarEnvioIa($config, $promptFinal, $arquivoPath, $modo)
    {
        $caminhoAbsoluto = storage_path('app/private/' . $arquivoPath);
        if (!file_exists($caminhoAbsoluto)) {
            return ['documento_id' => 0, 'valido' => false, 'motivo_rejeicao' => 'Arquivo físico não encontrado no servidor.'];
        }

        $mimeType = mime_content_type($caminhoAbsoluto);
        $base64 = base64_encode(file_get_contents($caminhoAbsoluto));

        try {
            if ($config->provedor === 'gemini') {
                return self::chamarGemini($config->api_key, $promptFinal, $mimeType, $base64, $modo);
            } elseif ($config->provedor === 'openai') {
                return self::chamarOpenAiCompatible('https://api.openai.com/v1/chat/completions', 'gpt-4o-mini', $config->api_key, $promptFinal, $mimeType, $base64, $modo);
            } elseif ($config->provedor === 'deepseek') {
                return self::chamarOpenAiCompatible('https://api.deepseek.com/chat/completions', 'deepseek-v4-flash-vision-exp', $config->api_key, $promptFinal, $mimeType, $base64, $modo);
            } elseif ($config->provedor === 'grok') {
                return self::chamarOpenAiCompatible('https://api.x.ai/v1/chat/completions', 'grok-vision-beta', $config->api_key, $promptFinal, $mimeType, $base64, $modo);
            }
            return ['documento_id' => 0, 'valido' => false, 'motivo_rejeicao' => 'Provedor de IA selecionado não configurado.'];

        } catch (\Exception $e) {
            Log::error("Erro na API da IA: " . $e->getMessage());
            return ['documento_id' => 0, 'valido' => false, 'motivo_rejeicao' => 'Servidor de IA indisponível no momento.'];
        }
    }

    // --- ADAPTADORES DE API COM STRUCTURED OUTPUTS ---
    private static function chamarGemini($apiKey, $prompt, $mimeType, $base64, $modo)
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";

        $propriedades = [
            "valido" => ["type" => "BOOLEAN", "description" => "True se o documento for válido e pertencer ao candidato."],
            "motivo_rejeicao" => ["type" => "STRING", "description" => "Vazio se válido, ou o motivo do erro."]
        ];
        $required = ["valido", "motivo_rejeicao"];

        if ($modo === 'lote') {
            $propriedades["documento_id"] = ["type" => "INTEGER", "description" => "O ID do documento que a imagem representa. Retorne 0 se a imagem não for um documento válido da lista."];
            $required[] = "documento_id";
        }

        $payload = [
            "contents" => [
                ["parts" => [["text" => $prompt], ["inline_data" => ["mime_type" => $mimeType, "data" => $base64]]]]
            ],
            "generationConfig" => [
                "response_mime_type" => "application/json",
                "response_schema" => ["type" => "OBJECT", "properties" => $propriedades, "required" => $required]
            ]
        ];

        $response = Http::timeout(30)->post($url, $payload);

        if ($response->successful()) {
            $resultado = json_decode($response->json('candidates.0.content.parts.0.text'), true);
            if (is_array($resultado)) {
                return [
                    'documento_id' => (int) ($resultado['documento_id'] ?? 0),
                    'valido' => (bool) ($resultado['valido'] ?? false),
                    'motivo_rejeicao' => $resultado['motivo_rejeicao'] ?? '',
                    'raw' => $resultado
                ];
            }
        }
        return ['documento_id' => 0, 'valido' => false, 'motivo_rejeicao' => 'Falha na interpretação da IA.'];
    }

    private static function chamarOpenAiCompatible($url, $modelo, $apiKey, $prompt, $mimeType, $base64, $modo)
    {
        $systemMsg = "Você OBRIGATORIAMENTE deve responder em formato JSON limpo contendo apenas as chaves boolean 'valido' e string 'motivo_rejeicao'.";
        if ($modo === 'lote') {
            $systemMsg = "Você OBRIGATORIAMENTE deve responder em formato JSON limpo contendo as chaves: integer 'documento_id' (use 0 se não reconhecer a foto), boolean 'valido' e string 'motivo_rejeicao'.";
        }

        $payload = [
            "model" => $modelo,
            "response_format" => ["type" => "json_object"], 
            "messages" => [
                ["role" => "system", "content" => $systemMsg],
                ["role" => "user", "content" => [
                    ["type" => "text", "text" => $prompt],
                    ["type" => "image_url", "image_url" => ["url" => "data:{$mimeType};base64,{$base64}"]]
                ]]
            ]
        ];

        $response = Http::withToken($apiKey)->timeout(45)->post($url, $payload);

        if ($response->successful()) {
            $resultado = json_decode($response->json('choices.0.message.content'), true);
            if (is_array($resultado)) {
                return [
                    'documento_id' => (int) ($resultado['documento_id'] ?? 0),
                    'valido' => (bool) ($resultado['valido'] ?? false),
                    'motivo_rejeicao' => $resultado['motivo_rejeicao'] ?? '',
                    'raw' => $resultado
                ];
            }
        }
        return ['documento_id' => 0, 'valido' => false, 'motivo_rejeicao' => 'A IA rejeitou o envio da imagem.'];
    }
}