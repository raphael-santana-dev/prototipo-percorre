<?php

namespace App\Modules\Comunicacao\Services;

use App\Modules\Comunicacao\Domain\Models\ComunicacaoLog;

class EmailTrackingService
{
    /**
     * Envolve o corpo do template no Layout Padrão e injeta o rastreamento de abertura e cliques.
     */
    public static function empacotarEmail(string $corpoBruto, ComunicacaoLog $log, string $assunto = ''): string
    {
        $token = $log->tracking_token;
        $corpoProcessado = $corpoBruto;

        // 1. Reescreve todos os links <a href="..."> para passarem pelo rastreador de cliques
        if ($token) {
            $corpoProcessado = preg_replace_callback(
                '/<a\s+([^>]*?)href=["\']([^"\']+)["\']([^>]*?)>(.*?)<\/a>/is',
                function ($matches) use ($token) {
                    $antesHref = $matches[1];
                    $urlOriginal = html_entity_decode(trim($matches[2]), ENT_QUOTES, 'UTF-8');
                    $depoisHref = $matches[3];
                    $conteudoInterno = $matches[4];

                    // Ignora links vazios, âncoras puras, mailto, tel ou links já rastreados
                    if (
                        $urlOriginal === '' ||
                        $urlOriginal === '#' ||
                        str_starts_with($urlOriginal, 'mailto:') ||
                        str_starts_with($urlOriginal, 'tel:') ||
                        str_contains($urlOriginal, '/email/t/c/')
                    ) {
                        return $matches[0];
                    }

                    $textoBotao = trim(strip_tags($conteudoInterno)) ?: 'Link / Botão';

                    $urlRastreada = route('email.tracking.click', [
                        'token' => $token,
                        'url'   => base64_encode($urlOriginal),
                        'label' => base64_encode(mb_substr($textoBotao, 0, 120)),
                    ]);

                    return '<a ' . $antesHref . 'href="' . $urlRastreada . '"' . $depoisHref . '>' . $conteudoInterno . '</a>';
                },
                $corpoProcessado
            );
        }

        // 2. Gera a URL do pixel invisível de abertura
        $pixelUrl = $token ? route('email.tracking.open', ['token' => $token]) : null;

        // 3. Verifica se existe um logo público PNG/SVG para o topo do e-mail
        $headerImageUrl = file_exists(public_path('images/logo-email.png'))
            ? asset('images/logo-email.png')
            : null;

        return view('emails.layouts.padrao', [
            'assunto'        => $assunto ?: $log->assunto,
            'corpoHtml'      => $corpoProcessado,
            'pixelUrl'       => $pixelUrl,
            'headerImageUrl' => $headerImageUrl,
        ])->render();
    }

    /**
     * Extrai Dispositivo, Navegador/Cliente e Sistema Operacional gratuitamente via User-Agent
     */
    public static function analisarUserAgent(?string $userAgent): array
    {
        if (!$userAgent) {
            return [
                'dispositivo' => 'Desconhecido',
                'navegador' => 'Desconhecido',
                'sistema_operacional' => 'Desconhecido',
            ];
        }

        $ua = strtolower($userAgent);

        // Dispositivo
        if (str_contains($ua, 'googleimageproxy')) {
            $dispositivo = 'Proxy do Gmail';
        } elseif (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')) {
            $dispositivo = 'Tablet';
        } elseif (str_contains($ua, 'iphone') || str_contains($ua, 'android') || str_contains($ua, 'mobile')) {
            $dispositivo = 'Smartphone (Mobile)';
        } else {
            $dispositivo = 'Computador (Desktop)';
        }

        // Sistema Operacional
        $so = match (true) {
            str_contains($ua, 'windows') => 'Windows',
            str_contains($ua, 'iphone') || str_contains($ua, 'ipad') || str_contains($ua, 'ios') => 'iOS (Apple)',
            str_contains($ua, 'macintosh') || str_contains($ua, 'mac os') => 'macOS',
            str_contains($ua, 'android') => 'Android',
            str_contains($ua, 'linux') => 'Linux',
            default => 'Outro / Proxy Webmail',
        };

        // Navegador / Cliente de E-mail
        $navegador = match (true) {
            str_contains($ua, 'googleimageproxy') => 'Gmail App / Web',
            str_contains($ua, 'outlook') || str_contains($ua, 'microsoft office') => 'Microsoft Outlook',
            str_contains($ua, 'thunderbird') => 'Mozilla Thunderbird',
            str_contains($ua, 'edg/') || str_contains($ua, 'edge') => 'Microsoft Edge',
            str_contains($ua, 'opr/') || str_contains($ua, 'opera') => 'Opera',
            str_contains($ua, 'chrome') => 'Google Chrome',
            str_contains($ua, 'safari') && !str_contains($ua, 'chrome') => 'Apple Safari / Mail',
            str_contains($ua, 'firefox') => 'Mozilla Firefox',
            default => 'Cliente de E-mail Padrão',
        };

        return [
            'dispositivo'         => $dispositivo,
            'navegador'           => $navegador,
            'sistema_operacional' => $so,
        ];
    }
}