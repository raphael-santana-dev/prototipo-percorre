<?php

namespace App\Modules\Matricula\Services;

use App\Modules\Matricula\Domain\Models\ConfiguracaoIa;
use App\Modules\Matricula\Domain\Models\AiModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class AiValidationService
{
    private static function getApiKey($provedor)
    {
        if (!$provedor || empty($provedor->api_key)) return null;
        
        try {
            return Crypt::decryptString($provedor->api_key);
        } catch (DecryptException $e) {
            return $provedor->api_key;
        }
    }

    public static function validarDocumento($inscricao, $documentoExigido, $arquivoPath)
    {
        $config = ConfiguracaoIa::first();
        if (!$config || !$config->is_ativa || !$config->ai_model_id) {
            return ['valido' => false, 'motivo_rejeicao' => 'Validação automática desativada ou Modelo de IA não selecionado.'];
        }

        $modelo = AiModel::with('provider')->find($config->ai_model_id);
        if (!$modelo || !$modelo->provider) {
             return ['valido' => false, 'motivo_rejeicao' => 'Provedor ou modelo de IA não encontrado no sistema.'];
        }

        $provedor = $modelo->provider;
        $apiKey = self::getApiKey($provedor);

        $promptFinal = $config->prompt_documentos . "\n\nDados oficiais do candidato:\nNome: {$inscricao->nome} | CPF: {$inscricao->cpf}\n\nDocumento que deve ser analisado: {$documentoExigido->nome}";
        
        return self::processarEnvioIa($provedor->driver, $provedor->api_url, $modelo->codigo, $apiKey, $promptFinal, $arquivoPath, 'individual');
    }

    public static function classificarDocumentoLote($inscricao, $documentosExigidos, $arquivoPath)
    {
        $config = ConfiguracaoIa::first();
        if (!$config || !$config->is_ativa || !$config->ai_model_id) {
            return ['documento_id' => 0, 'valido' => false, 'motivo_rejeicao' => 'Validação automática desativada.'];
        }

        $modelo = AiModel::with('provider')->find($config->ai_model_id);
        if (!$modelo || !$modelo->provider) {
             return ['documento_id' => 0, 'valido' => false, 'motivo_rejeicao' => 'Provedor/Modelo não configurado.'];
        }

        $provedor = $modelo->provider;
        $apiKey = self::getApiKey($provedor);

        $listaDocs = "";
        foreach($documentosExigidos as $d) {
            $listaDocs .= "- ID: {$d->id} | Documento: {$d->nome} | Descrição: {$d->descricao}\n";
        }

        $promptFinal = "Você é um classificador automático de matrículas.\nLista de documentos exigidos:\n{$listaDocs}\n\nDados do titular: Nome: {$inscricao->nome} | CPF: {$inscricao->cpf}\n\nAnalise a imagem: 1) Identifique a qual ID de documento da lista ela corresponde (retorne 0 se for foto aleatória ou ilegível). 2) Se identificou o ID, valide se a foto é autêntica e se os dados batem com o titular.";
        
        return self::processarEnvioIa($provedor->driver, $provedor->api_url, $modelo->codigo, $apiKey, $promptFinal, $arquivoPath, 'lote');
    }

    private static function processarEnvioIa($driver, $apiUrl, $codigoModelo, $apiKey, $promptFinal, $arquivoPath, $modo)
    {
        $caminhoAbsoluto = storage_path('app/private/' . $arquivoPath);
        if (!file_exists($caminhoAbsoluto)) {
            return ['documento_id' => 0, 'valido' => false, 'motivo_rejeicao' => 'Arquivo físico não encontrado no servidor.'];
        }

        $mimeType = mime_content_type($caminhoAbsoluto);
        $base64 = base64_encode(file_get_contents($caminhoAbsoluto));

        try {
            if ($driver === 'gemini') {
                return self::chamarGemini($apiUrl, $codigoModelo, $apiKey, $promptFinal, $mimeType, $base64, $modo);
            } else {
                return self::chamarOpenAiCompatible($apiUrl, $codigoModelo, $apiKey, $promptFinal, $mimeType, $base64, $modo);
            }
        } catch (\Exception $e) {
            Log::error("Erro na API da IA: " . $e->getMessage());
            return ['documento_id' => 0, 'valido' => false, 'motivo_rejeicao' => 'Servidor de IA indisponível no momento.'];
        }
    }

    private static function chamarGemini($apiUrl, $codigoModelo, $apiKey, $prompt, $mimeType, $base64, $modo)
    {
        $baseUrl = rtrim($apiUrl ?: 'https://generativelanguage.googleapis.com/v1beta', '/');
        $url = "{$baseUrl}/models/{$codigoModelo}:generateContent?key={$apiKey}";

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

    private static function chamarOpenAiCompatible($apiUrl, $codigoModelo, $apiKey, $prompt, $mimeType, $base64, $modo)
    {
        $url = $apiUrl;
        if (empty($url)) {
            $url = 'https://api.openai.com/v1/chat/completions';
        } elseif (!str_contains($url, 'chat/completions')) {
            $url = rtrim($url, '/') . '/chat/completions';
        }

        $systemMsg = "Você OBRIGATORIAMENTE deve responder em formato JSON limpo contendo apenas as chaves boolean 'valido' e string 'motivo_rejeicao'.";
        if ($modo === 'lote') {
            $systemMsg = "Você OBRIGATORIAMENTE deve responder em formato JSON limpo contendo as chaves: integer 'documento_id' (use 0 se não reconhecer a foto), boolean 'valido' e string 'motivo_rejeicao'.";
        }

        $payload = [
            "model" => $codigoModelo,
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