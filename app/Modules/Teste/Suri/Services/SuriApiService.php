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
        $this->baseUrl = env('SURI_API_URL');
        $this->token = env('SURI_API_TOKEN');
        $this->channelId = env('SURI_CHANNEL_ID');
    }

    public function syncTemplate(EmailTemplate $template): ?string
    {
        $cleanBody = strip_tags(html_entity_decode($template->corpo));
        
        // Identifica se existem variáveis no formato {{nome_variavel}}
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
                'title' => Str::limit(Str::slug($template->nome, '_'), 50, ''),
                'body' => trim($cleanBody),
                'subject' => Str::limit($template->assunto, 50, ''),
                'channelId' => $this->channelId,
                'category' => 'MARKETING'
            ],
            'templateExample' => trim($cleanBody),
            'bodyExamples' => []
        ];

        return $this->enviarRequisicao($payload);
    }

    private function cadastrarTemplateComVariaveis(EmailTemplate $template, string $cleanBody, array $variaveisNomeadas): ?string
    {
        $suriBody = $cleanBody;
        $templateExample = $cleanBody;
        $bodyExamples = [];

        foreach ($variaveisNomeadas as $index => $varNome) {
            $numeroSuri = $index + 1;
            $exemploTexto = strtoupper($varNome);

            // Substitui {{nome}} por {{1}} para o payload principal
            $suriBody = str_replace('{{' . $varNome . '}}', '{{' . $numeroSuri . '}}', $suriBody);
            
            // Substitui {{nome}} por [NOME] para o templateExample
            $templateExample = str_replace('{{' . $varNome . '}}', '[' . $exemploTexto . ']', $templateExample);
            
            // Adiciona o valor de exemplo no array esperado pela Suri
            $bodyExamples[] = $exemploTexto; 
        }

        $payload = [
            'template' => [
                'title' => Str::limit(Str::slug($template->nome, '_'), 50, ''),
                'body' => trim($suriBody),
                'subject' => Str::limit($template->assunto, 50, ''),
                'channelId' => $this->channelId,
                'category' => 'MARKETING'
            ],
            'templateExample' => trim($templateExample),
            'bodyExamples' => $bodyExamples
        ];

        return $this->enviarRequisicao($payload);
    }

    private function enviarRequisicao(array $payload): ?string
    {
        try {
            $response = Http::withToken($this->token)->post("{$this->baseUrl}/api/templates", $payload);

            if ($response->successful() && $response->json('success')) {
                return $response->json('data.id');
            }

            Log::error('Erro ao registrar template na Suri', [
                'payload' => $payload,
                'response' => $response->json()
            ]);
        } catch (\Exception $e) {
            Log::error('Exceção ao comunicar com a Suri: ' . $e->getMessage());
        }

        return null;
    }
}