<?php

namespace App\Modules\Teste\Suri\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
     * Monta o payload completo de criação de Template na Suri respeitando todas as regras da Meta:
     * - Variáveis {{1}}, {{2}} + templateExample [exemplo] + bodyExamples
     * - Rodapé (footer)
     * - Cabeçalho (headerType: 0 = Imagem c/ url, 2 = Documento PDF c/ base64File)
     * - Botões (type 0 = URL estática/dinâmica, 1 = Ligar, 2 = Resposta rápida, 3 = Copiar código)
     */
    public function montarPayloadTemplate(EmailTemplate $template): array
    {
        $cfg = is_string($template->configuracoes_suri)
            ? (json_decode($template->configuracoes_suri, true) ?? [])
            : ($template->configuracoes_suri ?? []);

        $botoesRaw = is_string($template->botoes)
            ? (json_decode($template->botoes, true) ?? [])
            : ($template->botoes ?? []);

        // Converte quebras de parágrafo do Quill em quebras de linha limpas
        $htmlCorpo = (string) $template->corpo;
        $htmlCorpo = preg_replace('/<\/(p|div|h1|h2|h3|li)>/i', "\n", $htmlCorpo);
        $htmlCorpo = preg_replace('/<br\s*\/?>/i', "\n", $htmlCorpo);
        $cleanBody = trim(strip_tags(html_entity_decode($htmlCorpo, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $cleanBody = preg_replace("/\n{3,}/", "\n\n", $cleanBody);

        // Extrai variáveis {{variavel}} ou {{1}} na ordem de aparição
        preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $cleanBody, $matches);
        $variaveisEncontradas = [];
        foreach ($matches[1] ?? [] as $v) {
            if (!in_array($v, $variaveisEncontradas, true)) {
                $variaveisEncontradas[] = $v;
            }
        }

        $exemplosSalvos = is_array($cfg['body_examples'] ?? null) ? $cfg['body_examples'] : [];
        $suriBody = $cleanBody;
        $templateExample = $cleanBody;
        $bodyExamples = [];

        foreach ($variaveisEncontradas as $index => $varNome) {
            $numeroSuri = $index + 1;
            $valorExemplo = trim((string) ($exemplosSalvos[$varNome] ?? $exemplosSalvos[$index] ?? $this->gerarExemploPadraoVariavel($varNome)));

            $suriBody = preg_replace('/\{\{\s*' . preg_quote($varNome, '/') . '\s*\}\}/', '{{' . $numeroSuri . '}}', $suriBody);
            $templateExample = preg_replace('/\{\{\s*' . preg_quote($varNome, '/') . '\s*\}\}/', '[' . $valorExemplo . ']', $templateExample);
            $bodyExamples[] = $valorExemplo;
        }

        $templateNode = [
            'title'     => trim((string) $template->nome),
            'body'      => trim($suriBody),
            'subject'   => trim((string) ($template->assunto ?: 'TESTE')),
            'channelId' => !empty($cfg['channel_id']) ? $cfg['channel_id'] : $this->channelId,
            'category'  => in_array($cfg['category'] ?? '', ['MARKETING', 'UTILITY'], true) ? $cfg['category'] : 'MARKETING',
        ];

        // Rodapé opcional
        if (!empty(trim((string) ($cfg['footer'] ?? '')))) {
            $templateNode['footer'] = trim((string) $cfg['footer']);
        }

        // Cabeçalho opcional (0 = Imagem, 2 = Documento PDF)
        $headerMode = $cfg['header_mode'] ?? 'none';
        $sampleFile = null;

        if ($headerMode === 'image') {
            $templateNode['headerType'] = 0;
            $sampleFile = [
                'url'      => trim((string) ($cfg['sample_file_url'] ?? '')),
                'fileName' => trim((string) ($cfg['sample_file_name'] ?? 'imagem.png')) ?: 'imagem.png',
            ];
        } elseif ($headerMode === 'document') {
            $templateNode['headerType'] = 2;
            $sampleFile = [
                'base64File' => trim((string) ($cfg['sample_file_base64'] ?? '')),
                'fileName'   => trim((string) ($cfg['sample_file_name'] ?? 'documento.pdf')) ?: 'documento.pdf',
            ];
        }

        // Montagem dos Botões (Ordenados: Ligar/Copiar/URL primeiro, Resposta Rápida por último)
        $botoesAcao = [];
        $botoesUrl = [];
        $botoesQuickReply = [];

        foreach ($botoesRaw as $btn) {
            $textoBtn = trim((string) ($btn['texto'] ?? $btn['text'] ?? ''));
            if ($textoBtn === '') continue;

            $tipoBtn = isset($btn['suri_type']) ? (int) $btn['suri_type'] : (empty($btn['url']) ? 2 : 0);

            if ($tipoBtn === 1) {
                // Botão de Ligar (type: 1)
                $fone = trim((string) ($btn['phone'] ?? ''));
                if ($fone !== '' && !str_starts_with($fone, '+')) {
                    $fone = '+' . preg_replace('/\D/', '', $fone);
                }
                $botoesAcao[] = [
                    'text'  => $textoBtn,
                    'type'  => 1,
                    'phone' => $fone,
                ];
            } elseif ($tipoBtn === 3) {
                // Botão de Copiar Código (type: 3)
                $botoesAcao[] = [
                    'text'    => $textoBtn,
                    'type'    => 3,
                    'example' => trim((string) ($btn['example'] ?? 'CODIGO10')) ?: 'CODIGO10',
                ];
            } elseif ($tipoBtn === 0) {
                // Botão de URL Estática ou Dinâmica (type: 0)
                $urlBtn = trim((string) ($btn['url'] ?? ''));
                $itemUrl = [
                    'text' => $textoBtn,
                    'type' => 0,
                    'url'  => $urlBtn,
                ];
                if (!empty(trim((string) ($btn['example'] ?? ''))) || str_ends_with($urlBtn, '{{1}}')) {
                    $itemUrl['example'] = trim((string) ($btn['example'] ?? 'detalhe')) ?: 'detalhe';
                }
                $botoesUrl[] = $itemUrl;
            } else {
                // Botão de Resposta Rápida (type: 2) - sempre por último
                $botoesQuickReply[] = [
                    'text' => $textoBtn,
                    'type' => 2,
                ];
            }
        }

        $botoesFinais = array_slice(array_merge($botoesAcao, $botoesUrl, $botoesQuickReply), 0, 10);
        if (!empty($botoesFinais)) {
            $templateNode['buttons'] = $botoesFinais;
        }

        $payload = [
            'template'        => $templateNode,
            'templateExample' => trim($templateExample),
            'bodyExamples'    => $bodyExamples,
        ];

        if ($sampleFile !== null) {
            $payload['sampleFile'] = $sampleFile;
        }

        return $payload;
    }

    private function gerarExemploPadraoVariavel(string $varNome): string
    {
        $lower = strtolower($varNome);
        return match (true) {
            str_contains($lower, 'nome')  => 'Maria',
            str_contains($lower, 'valor') || str_contains($lower, 'fatura') => 'R$ 249,90',
            str_contains($lower, 'data') || str_contains($lower, 'venc')    => '10/08/2026',
            str_contains($lower, 'curso') => 'Administração',
            default                       => strtoupper($varNome),
        };
    }

    /**
     * Sincroniza um EmailTemplate local criando-o na Suri e retorna os detalhes completos da chamada.
     */
    public function syncTemplateComDetalhes(EmailTemplate $template): array
    {
        $payload = $this->montarPayloadTemplate($template);
        $res = $this->postEndpoint('/api/templates', $payload);

        $suriId = null;
        if ($res['sucesso']) {
            $suriId = data_get($res['resposta'], 'data.id')
                ?? data_get($res['resposta'], 'id');
        }

        $res['suri_template_id'] = $suriId;
        return $res;
    }

    public function syncTemplate(EmailTemplate $template): ?string
    {
        $res = $this->syncTemplateComDetalhes($template);
        return $res['suri_template_id'] ?? null;
    }

    // =========================================================================
    // TEMPLATES NA SURI: GET /api/templates E GET /api/templates/:template_id
    // =========================================================================

    public function listarTemplates(array $queryParams = []): array
    {
        return $this->getEndpoint('/api/templates', $queryParams);
    }

    public function obterTemplate(string $templateId): array
    {
        $idEncoded = urlencode(trim($templateId));
        return $this->getEndpoint("/api/templates/{$idEncoded}");
    }

    public function criarTemplateDireto(array $payload): array
    {
        return $this->postEndpoint('/api/templates', $payload);
    }

    // =========================================================================
    // CONTATOS E MENSAGENS NA SURI
    // =========================================================================

    public function listarContatos(?string $continuationToken = null, string|int $limit = '20'): array
    {
        $queryParams = ['limit' => (string) $limit];
        if ($continuationToken !== null && trim($continuationToken) !== '') {
            $queryParams['continuationToken'] = trim($continuationToken);
        }
        return $this->getEndpoint('/api/contacts', $queryParams);
    }

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
        ?string $defaultDepartmentId = null,
        array $buttonsParameters = []
    ): array {
        $messageNode = [
            'templateId'     => trim($templateId),
            'bodyParameters' => array_values(array_map('strval', $bodyParameters)),
        ];

        if (!empty($buttonsParameters)) {
            $messageNode['buttonsParameters'] = array_values(array_map('strval', $buttonsParameters));
        }

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
            'message' => $messageNode,
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

    public function enviarMensagemSimples(
        string $userId,
        string $texto,
        ?string $contextMsgId = null
    ): array {
        $messageData = ['text' => $texto];

        if (!empty(trim((string) $contextMsgId))) {
            $messageData['context'] = ['msgId' => trim($contextMsgId)];
        }

        $payload = [
            'userId'  => trim($userId),
            'message' => $messageData,
        ];

        return $this->postEndpoint('/api/messages/send', $payload);
    }

    /**
     * Reexecuta (Replay) qualquer requisição salva no histórico de logs da Suri.
     */
    public function reexecutarLog(string $metodo, string $urlCompleta, array $payload = []): array
    {
        $metodoUpper = strtoupper($metodo);
        $path = parse_url($urlCompleta, PHP_URL_PATH) ?: '/api/messages/send';

        if ($metodoUpper === 'GET') {
            return $this->getEndpoint($path, $payload);
        }

        return $this->postEndpoint($path, $payload);
    }

    public function getEndpoint(string $endpoint, array $queryParams = []): array
    {
        $urlBase = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $urlCompleta = !empty($queryParams) ? ($urlBase . '?' . http_build_query($queryParams)) : $urlBase;

        if (empty($this->baseUrl) || empty($this->token)) {
            return [
                'sucesso'     => false,
                'metodo'      => 'GET',
                'http_status' => 0,
                'url'         => $urlCompleta,
                'payload'     => $queryParams,
                'resposta'    => ['erro' => 'Variáveis SURI_API_URL ou SURI_API_TOKEN ausentes no .env.'],
            ];
        }

        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->timeout(30)
                ->get($urlBase, $queryParams);

            $json = $response->json();
            $sucesso = $response->successful() && ($json['success'] ?? true) !== false;

            return [
                'sucesso'     => $sucesso,
                'metodo'      => 'GET',
                'http_status' => $response->status(),
                'url'         => $urlCompleta,
                'payload'     => $queryParams,
                'resposta'    => $json ?? ['raw_body' => $response->body()],
            ];
        } catch (\Throwable $e) {
            Log::error("Exceção GET Suri [{$endpoint}]: " . $e->getMessage());

            return [
                'sucesso'     => false,
                'metodo'      => 'GET',
                'http_status' => 500,
                'url'         => $urlCompleta,
                'payload'     => $queryParams,
                'resposta'    => ['exception' => $e->getMessage()],
            ];
        }
    }

    public function postEndpoint(string $endpoint, array $payload): array
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');

        if (empty($this->baseUrl) || empty($this->token)) {
            return [
                'sucesso'     => false,
                'metodo'      => 'POST',
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
                'metodo'      => 'POST',
                'http_status' => $response->status(),
                'url'         => $url,
                'payload'     => $payload,
                'resposta'    => $json ?? ['raw_body' => $response->body()],
            ];
        } catch (\Throwable $e) {
            Log::error("Exceção ao comunicar com a Suri [{$endpoint}]: " . $e->getMessage());

            return [
                'sucesso'     => false,
                'metodo'      => 'POST',
                'http_status' => 500,
                'url'         => $url,
                'payload'     => $payload,
                'resposta'    => ['exception' => $e->getMessage()],
            ];
        }
    }
}