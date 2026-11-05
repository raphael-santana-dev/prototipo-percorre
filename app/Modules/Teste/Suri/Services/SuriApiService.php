<?php

namespace App\Modules\Teste\Suri\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;

class SuriApiService
{
    protected string $baseUrl;
    protected string $token;
    protected string $channelId;

    public function __construct()
    {
        $this->baseUrl   = rtrim((string) env('SURI_API_URL', ''), '/');
        $this->token     = (string) env('SURI_API_TOKEN', '');
        $this->channelId = (string) env('SURI_CHANNEL_ID', '');
    }

    public function getConfigResumo(): array
    {
        return [
            'base_url'   => $this->baseUrl ?: 'Não configurada',
            'channel_id' => $this->channelId ?: 'Não configurado',
            'tem_token'  => !empty($this->token),
        ];
    }

    /**
     * Sincroniza um EmailTemplate local criando-o na Suri.
     */
    public function syncTemplate(EmailTemplate $template): ?string
    {
        $cleanBody = strip_tags(html_entity_decode((string) $template->corpo, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        preg_match_all('/\{\{([a-zA-Z0-9_]+)\}\}/', $cleanBody, $matches);
        $variaveisNomeadas = array_values(array_unique($matches[1] ?? []));

        if (empty($variaveisNomeadas)) {
            return $this->cadastrarTemplateSimples($template, $cleanBody);
        }

        return $this->cadastrarTemplateComVariaveis($template, $cleanBody, $variaveisNomeadas);
    }

    private function cadastrarTemplateSimples(EmailTemplate $template, string $cleanBody): ?string
    {
        $payload = [
            'template' => [
                'title'     => Str::limit(Str::slug($template->nome, '_'), 50, ''),
                'body'      => trim($cleanBody),
                'subject'   => Str::limit((string) ($template->assunto ?: $template->nome), 50, ''),
                'channelId' => $this->channelId,
                'category'  => 'MARKETING',
            ],
            'templateExample' => trim($cleanBody),
            'bodyExamples'    => [],
        ];

        return $this->enviarRequisicaoTemplate($payload);
    }

    private function cadastrarTemplateComVariaveis(EmailTemplate $template, string $cleanBody, array $variaveisNomeadas): ?string
    {
        $suriBody = $cleanBody;
        $templateExample = $cleanBody;
        $bodyExamples = [];

        foreach ($variaveisNomeadas as $index => $varNome) {
            $numeroSuri = $index + 1;
            $exemploTexto = strtoupper($varNome);

            $suriBody = str_replace('{{' . $varNome . '}}', '{{' . $numeroSuri . '}}', $suriBody);
            $templateExample = str_replace('{{' . $varNome . '}}', '[' . $exemploTexto . ']', $templateExample);
            $bodyExamples[] = $exemploTexto;
        }

        $payload = [
            'template' => [
                'title'     => Str::limit(Str::slug($template->nome, '_'), 50, ''),
                'body'      => trim($suriBody),
                'subject'   => Str::limit((string) ($template->assunto ?: $template->nome), 50, ''),
                'channelId' => $this->channelId,
                'category'  => 'MARKETING',
            ],
            'templateExample' => trim($templateExample),
            'bodyExamples'    => $bodyExamples,
        ];

        return $this->enviarRequisicaoTemplate($payload);
    }

    private function enviarRequisicaoTemplate(array $payload): ?string
    {
        $res = $this->postEndpoint('/api/templates', $payload);

        if ($res['sucesso']) {
            return data_get($res['resposta'], 'data.id')
                ?? data_get($res['resposta'], 'id');
        }

        return null;
    }

    // =========================================================================
    // 1. IMPORTAR CONTATO: POST {{baseUrl}}/api/contacts
    // =========================================================================
    public function importarContato(
        string $name,
        string $phone,
        int $gender = 0,
        ?string $channelId = null,
        int $channelType = 1
    ): array {
        $payload = [
            'name'        => trim($name),
            'phone'       => preg_replace('/\D/', '', $phone),
            'gender'      => $gender,
            'channelId'   => $channelId ?: $this->channelId,
            'channelType' => $channelType,
        ];

        return $this->postEndpoint('/api/contacts', $payload);
    }

    // =========================================================================
    // 2. ENVIAR MENSAGEM COM TEMPLATE SIMPLES: POST {{baseUrl}}/api/messages/send
    // =========================================================================
    public function enviarTemplateSimples(
        string $userId,
        string $templateId,
        ?string $flowId = null,
        bool $ignorePortfolio = false,
        int $responseActionType = 1
    ): array {
        $payload = [
            'userId'  => trim($userId),
            'message' => [
                'templateId' => trim($templateId),
            ],
        ];

        if (!empty(trim((string) $flowId))) {
            $payload['responseAction'] = [
                'type'            => $responseActionType,
                'sendTo'          => trim($flowId),
                'ignorePortfolio' => $ignorePortfolio,
            ];
        }

        return $this->postEndpoint('/api/messages/send', $payload);
    }

    // =========================================================================
    // 3. ENVIAR MENSAGEM COM TEMPLATE COM BOTÕES: POST {{baseUrl}}/api/messages/send
    // =========================================================================
    public function enviarTemplateComBotoes(
        string $userName,
        string $userPhone,
        string $templateId,
        ?string $email = null,
        int $gender = 0,
        ?string $channelId = null,
        int $channelType = 1,
        ?string $defaultDepartmentId = null
    ): array {
        $payload = [
            'user' => [
                'name'                => trim($userName),
                'phone'               => preg_replace('/\D/', '', $userPhone),
                'email'               => !empty($email) ? trim($email) : null,
                'gender'              => $gender,
                'channelId'           => $channelId ?: $this->channelId,
                'channelType'         => $channelType,
                'defaultDepartmentId' => !empty($defaultDepartmentId) ? trim($defaultDepartmentId) : null,
            ],
            'message' => [
                'templateId' => trim($templateId),
            ],
        ];

        return $this->postEndpoint('/api/messages/send', $payload);
    }

    // =========================================================================
    // 4. ENVIAR TEMPLATE COM VARIÁVEIS: POST {{baseUrl}}/api/messages/send
    // =========================================================================
    public function enviarTemplateComVariaveis(
        string $userName,
        string $userPhone,
        string $templateId,
        array $bodyParameters = [],
        ?string $flowId = null,
        bool $ignorePortfolio = false,
        int $responseActionType = 1,
        ?string $email = null,
        int $gender = 0,
        ?string $channelId = null,
        int $channelType = 1,
        ?string $defaultDepartmentId = null
    ): array {
        $payload = [
            'user' => [
                'name'                => trim($userName),
                'phone'               => preg_replace('/\D/', '', $userPhone),
                'email'               => !empty($email) ? trim($email) : null,
                'gender'              => $gender,
                'channelId'           => $channelId ?: $this->channelId,
                'channelType'         => $channelType,
                'defaultDepartmentId' => !empty($defaultDepartmentId) ? trim($defaultDepartmentId) : null,
            ],
            'message' => [
                'templateId'     => trim($templateId),
                'bodyParameters' => array_values(array_map('strval', $bodyParameters)),
            ],
        ];

        if (!empty(trim((string) $flowId))) {
            $payload['responseAction'] = [
                'type'            => $responseActionType,
                'sendTo'          => trim($flowId),
                'ignorePortfolio' => $ignorePortfolio,
            ];
        }

        return $this->postEndpoint('/api/messages/send', $payload);
    }

    // =========================================================================
    // 5. ENVIAR MENSAGEM SIMPLES SEM TEMPLATE: POST {{baseUrl}}/api/messages/send
    // =========================================================================
    public function enviarMensagemSimples(
        string $userId,
        string $texto,
        ?string $contextMsgId = null
    ): array {
        $messageData = [
            'text' => $texto,
        ];

        if (!empty(trim((string) $contextMsgId))) {
            $messageData['context'] = [
                'msgId' => trim($contextMsgId),
            ];
        }

        $payload = [
            'userId'  => trim($userId),
            'message' => $messageData,
        ];

        return $this->postEndpoint('/api/messages/send', $payload);
    }

    /**
     * Disparo padronizado POST para a API da Suri com captura de logs e métricas.
     */
    public function postEndpoint(string $endpoint, array $payload): array
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');

        if (empty($this->baseUrl) || empty($this->token)) {
            return [
                'sucesso'     => false,
                'http_status' => 0,
                'url'         => $url,
                'payload'     => $payload,
                'resposta'    => ['erro' => 'Variáveis SURI_API_URL ou SURI_API_TOKEN ausentes no .env.'],
            ];
        }

        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->timeout(30)
                ->post($url, $payload);

            $json = $response->json();
            $sucesso = $response->successful() && ($json['success'] ?? true) !== false;

            if (!$sucesso) {
                Log::error("Erro na requisição Suri [{$endpoint}]", [
                    'status'   => $response->status(),
                    'payload'  => $payload,
                    'response' => $json ?? $response->body(),
                ]);
            }

            return [
                'sucesso'     => $sucesso,
                'http_status' => $response->status(),
                'url'         => $url,
                'payload'     => $payload,
                'resposta'    => $json ?? ['raw_body' => $response->body()],
            ];
        } catch (\Throwable $e) {
            Log::error("Exceção ao comunicar com a Suri [{$endpoint}]: " . $e->getMessage());

            return [
                'sucesso'     => false,
                'http_status' => 500,
                'url'         => $url,
                'payload'     => $payload,
                'resposta'    => ['exception' => $e->getMessage()],
            ];
        }
    }
}