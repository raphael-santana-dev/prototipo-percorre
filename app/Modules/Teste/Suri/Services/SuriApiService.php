<?php

namespace App\Modules\Teste\Suri\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;
use App\Modules\Teste\Suri\Models\SuriTestLog;

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

    public function getChannelId(): string
    {
        return $this->channelId;
    }

    public function normalizarTelefoneComDdi(?string $telefone): ?string
    {
        if (empty($telefone) || str_contains($telefone, '*')) {
            return null;
        }

        $digitos = preg_replace('/\D/', '', $telefone);
        if (strlen($digitos) < 10) {
            return null;
        }

        if (!str_starts_with($digitos, '55') && in_array(strlen($digitos), [10, 11], true)) {
            $digitos = '55' . $digitos;
        }

        return $digitos;
    }

    public function resolverUserIdParaDisparo($inscricao = null, array $dadosExtras = []): ?string
    {
        $testUserId = trim((string) config('services.suri.test_user_id', env('SURI_TEST_USER_ID', '')));
        if ($testUserId !== '') {
            return $testUserId;
        }

        $inscricaoId = $inscricao->id ?? ($dadosExtras['inscricao_id'] ?? null);
        $studentId   = $inscricao->student_id ?? ($dadosExtras['student_id'] ?? null);
        $foneBruto   = $inscricao->celular ?? $dadosExtras['celular'] ?? $dadosExtras['telefone'] ?? null;
        $foneCom55   = $this->normalizarTelefoneComDdi($foneBruto);

        $contato = \App\Models\SuriContato::query()
            ->when($inscricaoId, fn($q) => $q->orWhere('inscricao_id', $inscricaoId))
            ->when($studentId, fn($q) => $q->orWhere('student_id', $studentId))
            ->when($foneCom55, fn($q) => $q->orWhere('telefone', $foneCom55))
            ->latest('id')
            ->first();

        if ($contato && !empty($contato->suri_user_id)) {
            return $contato->suri_user_id;
        }

        if ($foneCom55) {
            $nome = trim((string) ($inscricao->nome ?? $dadosExtras['nome'] ?? 'Candidato'));
            $res  = $this->importarContato($nome, $foneCom55);

            $suriUserId = data_get($res, 'resposta.data.id')
                ?? data_get($res, 'resposta.id')
                ?? ($this->channelId ? "{$this->channelId}:{$foneCom55}" : $foneCom55);

            \App\Models\SuriContato::updateOrCreate(
                ['suri_user_id' => (string) $suriUserId],
                [
                    'inscricao_id'        => $inscricaoId,
                    'student_id'          => $studentId,
                    'nome'                => $nome,
                    'telefone'            => $foneCom55,
                    'email'               => $inscricao->email ?? ($dadosExtras['email'] ?? null),
                    'channel_id'          => $this->channelId ?: null,
                    'channel_type'        => 1,
                    'ultima_interacao_em' => now(),
                ]
            );

            return (string) $suriUserId;
        }

        return null;
    }

    /**
     * Resolve name, phone, email e userId do destinatário para montar o nó "user" exigido pela Suri,
     * respeitando SURI_TEST_USER_ID e WHATSAPP_TEST_NUMBER se estiverem definidos no .env.
     */
    public function resolverDestinatarioParaDisparo($inscricao = null, array $dadosExtras = []): array
    {
        $testUserId = trim((string) config('services.suri.test_user_id', env('SURI_TEST_USER_ID', '')));
        $testPhone  = trim((string) env('WHATSAPP_TEST_NUMBER', ''));

        $nomeReal  = trim((string) ($inscricao->nome ?? $dadosExtras['nome'] ?? 'Candidato'));
        $emailReal = trim((string) ($inscricao->email ?? $dadosExtras['email'] ?? ''));
        $foneReal  = $this->normalizarTelefoneComDdi($inscricao->celular ?? $dadosExtras['celular'] ?? $dadosExtras['telefone'] ?? null);

        $foneExtraidoDoTestId = null;
        if ($testUserId !== '' && str_contains($testUserId, ':')) {
            $partes = explode(':', $testUserId, 2);
            $foneExtraidoDoTestId = $this->normalizarTelefoneComDdi($partes[1] ?? '');
        }

        $telefoneFinal = $this->normalizarTelefoneComDdi($testPhone)
            ?: $foneExtraidoDoTestId
            ?: $foneReal;

        $userIdFinal = $this->resolverUserIdParaDisparo($inscricao, $dadosExtras);

        return [
            'user_id' => $userIdFinal,
            'name'    => $nomeReal ?: 'Candidato',
            'phone'   => $telefoneFinal,
            'email'   => $emailReal !== '' ? $emailReal : null,
        ];
    }

    // =========================================================================
    // MONTAGEM DO PAYLOAD E SINCRONIZAÇÃO DE TEMPLATES
    // =========================================================================

    public function montarPayloadTemplate(EmailTemplate $template): array
    {
        $cfg = is_string($template->configuracoes_suri)
            ? (json_decode($template->configuracoes_suri, true) ?? [])
            : ($template->configuracoes_suri ?? []);

        $botoesRaw = is_string($template->botoes)
            ? (json_decode($template->botoes, true) ?? [])
            : ($template->botoes ?? []);

        $htmlCorpo = (string) $template->corpo;
        $htmlCorpo = preg_replace('/<\/(p|div|h1|h2|h3|li)>/i', "\n", $htmlCorpo);
        $htmlCorpo = preg_replace('/<br\s*\/?>/i', "\n", $htmlCorpo);
        $cleanBody = trim(strip_tags(html_entity_decode($htmlCorpo, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $cleanBody = preg_replace("/\n{3,}/", "\n\n", $cleanBody);

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
            'language'  => 'pt_BR',
        ];

        if (!empty(trim((string) ($cfg['footer'] ?? '')))) {
            $templateNode['footer'] = trim((string) $cfg['footer']);
        }

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

        $botoesAcao = [];
        $botoesUrl = [];
        $botoesQuickReply = [];

        foreach ($botoesRaw as $btn) {
            $textoBtn = trim((string) ($btn['texto'] ?? $btn['text'] ?? ''));
            if ($textoBtn === '') continue;

            $tipoBtn = isset($btn['suri_type']) ? (int) $btn['suri_type'] : (empty($btn['url']) ? 2 : 0);

            if ($tipoBtn === 1) {
                $fone = trim((string) ($btn['phone'] ?? ''));
                if ($fone !== '' && !str_starts_with($fone, '+')) $fone = '+' . preg_replace('/\D/', '', $fone);
                $botoesAcao[] = ['text' => $textoBtn, 'type' => 1, 'phone' => $fone];
            } elseif ($tipoBtn === 3) {
                $botoesAcao[] = ['text' => $textoBtn, 'type' => 3, 'example' => trim((string) ($btn['example'] ?? 'CODIGO10')) ?: 'CODIGO10'];
            } elseif ($tipoBtn === 0) {
                $urlBtn = trim((string) ($btn['url'] ?? ''));
                $itemUrl = ['text' => $textoBtn, 'type' => 0, 'url' => $urlBtn];
                if (!empty(trim((string) ($btn['example'] ?? ''))) || str_ends_with($urlBtn, '{{1}}')) {
                    $itemUrl['example'] = trim((string) ($btn['example'] ?? 'detalhe')) ?: 'detalhe';
                }
                $botoesUrl[] = $itemUrl;
            } else {
                $botoesQuickReply[] = ['text' => $textoBtn, 'type' => 2];
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

        if ($sampleFile !== null) $payload['sampleFile'] = $sampleFile;

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

    public function syncTemplateComDetalhes(EmailTemplate $template): array
    {
        $payload = $this->montarPayloadTemplate($template);
        $res = $this->postEndpoint('/api/templates', $payload, 'Sincronizar Template Local');

        $suriId = null;
        if ($res['sucesso']) {
            $suriId = data_get($res['resposta'], 'data.id') ?? data_get($res['resposta'], 'id');
        }

        $res['suri_template_id'] = $suriId;
        return $res;
    }

    public function syncTemplate(EmailTemplate $template): ?string
    {
        $res = $this->syncTemplateComDetalhes($template);
        return $res['suri_template_id'] ?? null;
    }

    public function listarTemplates(array $queryParams = []): array
    {
        return $this->getEndpoint('/api/templates', $queryParams, 'Listar Templates Suri');
    }

    public function obterTemplate(string $templateId): array
    {
        $idEncoded = urlencode(trim($templateId));
        return $this->getEndpoint("/api/templates/{$idEncoded}", [], "Inspecionar Template ({$templateId})");
    }

    public function criarTemplateDireto(array $payload): array
    {
        return $this->postEndpoint('/api/templates', $payload, 'Criar Template Direto');
    }

    // =========================================================================
    // ENDPOINTS DE MENSAGENS E CONTATOS
    // =========================================================================

    public function listarContatos(?string $continuationToken = null, string|int $limit = '20'): array
    {
        $queryParams = ['limit' => (string) $limit];
        if ($continuationToken !== null && trim($continuationToken) !== '') {
            $queryParams['continuationToken'] = trim($continuationToken);
        }
        return $this->getEndpoint('/api/contacts', $queryParams, 'Listar Contatos');
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
        return $this->postEndpoint('/api/contacts', $payload, 'Importar Contato');
    }

    public function enviarTemplateSimples(
        string $userId,
        string $templateId,
        ?string $flowId = null,
        bool $ignorePortfolio = false,
        int $responseActionType = 1,
        array $bodyParameters = [],
        array $buttonsParameters = []
    ): array {
        $messageNode = ['templateId' => trim($templateId)];

        if (!empty($bodyParameters)) {
            $messageNode['bodyParameters'] = array_values(array_map('strval', $bodyParameters));
        }

        if (!empty($buttonsParameters)) {
            $messageNode['buttonsParameters'] = array_values(array_map('strval', $buttonsParameters));
        }

        $payload = [
            'userId'  => trim($userId),
            'message' => $messageNode,
        ];

        if (!empty(trim((string) $flowId))) {
            $payload['responseAction'] = [
                'type'            => $responseActionType,
                'sendTo'          => trim($flowId),
                'ignorePortfolio' => $ignorePortfolio,
            ];
        }

        return $this->postEndpoint('/api/messages/send', $payload, 'Disparo: Template Simples');
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
            'message' => ['templateId' => trim($templateId)],
        ];

        return $this->postEndpoint('/api/messages/send', $payload, 'Disparo: Template c/ Botões');
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

        return $this->postEndpoint('/api/messages/send', $payload, 'Disparo: Template c/ Variáveis');
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

        return $this->postEndpoint('/api/messages/send', $payload, 'Disparo: Texto Livre');
    }

    public function reexecutarLog(string $metodo, string $urlCompleta, array $payload = [], string $acao = 'Replay'): array
    {
        $metodoUpper = strtoupper($metodo);
        $path = parse_url($urlCompleta, PHP_URL_PATH) ?: '/api/messages/send';

        if ($metodoUpper === 'GET') {
            return $this->getEndpoint($path, $payload, $acao);
        }

        return $this->postEndpoint($path, $payload, $acao);
    }

    public function getEndpoint(string $endpoint, array $queryParams = [], string $acao = 'Requisição API'): array
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
                'log_id'      => null
            ];
        }

        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->timeout(30)
                ->get($urlBase, $queryParams);

            $json = $response->json();
            $sucesso = $response->successful() && ($json['success'] ?? true) !== false;

            $logModel = SuriTestLog::create([
                'acao'        => $acao,
                'metodo'      => 'GET',
                'url'         => $urlCompleta,
                'http_status' => $response->status(),
                'sucesso'     => $sucesso,
                'payload'     => $queryParams,
                'resposta'    => $json ?? ['raw_body' => $response->body()],
                'user_id'     => auth()->id(),
                'user_nome'   => auth()->user()?->name ?? 'Sistema (Worker)',
                'ip'          => request()->ip() ?? '127.0.0.1',
            ]);

            return [
                'sucesso'     => $sucesso,
                'metodo'      => 'GET',
                'http_status' => $response->status(),
                'url'         => $urlCompleta,
                'payload'     => $queryParams,
                'resposta'    => $json ?? ['raw_body' => $response->body()],
                'log_id'      => $logModel->id,
            ];
        } catch (\Throwable $e) {
            Log::error("Exceção GET Suri [{$endpoint}]: " . $e->getMessage());

            $logModel = SuriTestLog::create([
                'acao'        => $acao,
                'metodo'      => 'GET',
                'url'         => $urlCompleta,
                'http_status' => 500,
                'sucesso'     => false,
                'payload'     => $queryParams,
                'resposta'    => ['exception' => $e->getMessage()],
                'user_id'     => auth()->id(),
                'user_nome'   => auth()->user()?->name ?? 'Sistema (Worker)',
                'ip'          => request()->ip() ?? '127.0.0.1',
            ]);

            return [
                'sucesso'     => false,
                'metodo'      => 'GET',
                'http_status' => 500,
                'url'         => $urlCompleta,
                'payload'     => $queryParams,
                'resposta'    => ['exception' => $e->getMessage()],
                'log_id'      => $logModel->id,
            ];
        }
    }

    public function postEndpoint(string $endpoint, array $payload, string $acao = 'Requisição API'): array
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
                'log_id'      => null
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

            $logModel = SuriTestLog::create([
                'acao'        => $acao,
                'metodo'      => 'POST',
                'url'         => $url,
                'http_status' => $response->status(),
                'sucesso'     => $sucesso,
                'payload'     => $payload,
                'resposta'    => $json ?? ['raw_body' => $response->body()],
                'user_id'     => auth()->id(),
                'user_nome'   => auth()->user()?->name ?? 'Sistema (Worker)',
                'ip'          => request()->ip() ?? '127.0.0.1',
            ]);

            return [
                'sucesso'     => $sucesso,
                'metodo'      => 'POST',
                'http_status' => $response->status(),
                'url'         => $url,
                'payload'     => $payload,
                'resposta'    => $json ?? ['raw_body' => $response->body()],
                'log_id'      => $logModel->id,
            ];
        } catch (\Throwable $e) {
            Log::error("Exceção ao comunicar com a Suri [{$endpoint}]: " . $e->getMessage());

            $logModel = SuriTestLog::create([
                'acao'        => $acao,
                'metodo'      => 'POST',
                'url'         => $url,
                'http_status' => 500,
                'sucesso'     => false,
                'payload'     => $payload,
                'resposta'    => ['exception' => $e->getMessage()],
                'user_id'     => auth()->id(),
                'user_nome'   => auth()->user()?->name ?? 'Sistema (Worker)',
                'ip'          => request()->ip() ?? '127.0.0.1',
            ]);

            return [
                'sucesso'     => false,
                'metodo'      => 'POST',
                'http_status' => 500,
                'url'         => $url,
                'payload'     => $payload,
                'resposta'    => ['exception' => $e->getMessage()],
                'log_id'      => $logModel->id,
            ];
        }
    }
}