<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $assunto ?? config('app.name') }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1f2937; -webkit-font-smoothing: antialiased;">
    
    <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f3f4f6; padding: 28px 16px;">
        <tr>
            <td align="center">
                
                <!-- CARD PRINCIPAL DO E-MAIL (600px) -->
                <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">
                    
                    <!-- 1. TOPO SÓLIDO (SEM GRADIENTE) -->
                    <tr>
                        <td align="left" style="background-color: #461a63; padding: 20px 28px;">
                            @if(!empty($headerImageUrl))
                                <img src="{{ $headerImageUrl }}" alt="Instituto Percorre" style="max-height: 40px; width: auto; display: block; border: 0;">
                            @else
                                <span style="color: #ffffff; font-size: 16px; font-weight: 700; letter-spacing: 0.5px;">
                                    Instituto Percorre
                                </span>
                            @endif
                        </td>
                    </tr>

                    <!-- 2. CORPO DINÂMICO DO TEMPLATE -->
                    <tr>
                        <td style="padding: 32px 28px 24px 28px; font-size: 14px; line-height: 1.65; color: #374151;">
                            {!! $corpoHtml !!}
                        </td>
                    </tr>

                    <!-- 3. ASSINATURA MINIMALISTA -->
                    <tr>
                        <td style="padding: 0 28px 28px 28px;">
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-top: 1px solid #e5e7eb; padding-top: 16px;">
                                <tr>
                                    <td style="font-size: 13px; color: #6b7280; line-height: 1.5;">
                                        <strong style="color: #111827; font-size: 13px; display: block;">
                                            {{ $assinaturaNome ?? 'Equipe Instituto Percorre' }}
                                        </strong>
                                        <span>{{ $assinaturaCargo ?? 'Relacionamento Acadêmico & Processo Seletivo' }}</span><br>
                                        <a href="{{ config('app.url') }}" style="color: #461a63; text-decoration: none; font-weight: 600;">
                                            {{ parse_url(config('app.url'), PHP_URL_HOST) ?: config('app.url') }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- 4. RODAPÉ INSTITUCIONAL -->
                    <tr>
                        <td align="left" style="background-color: #f9fafb; padding: 18px 28px; border-top: 1px solid #e5e7eb; font-size: 11px; color: #6b7280; line-height: 1.5;">
                            <p style="margin: 0 0 4px 0; color: #4b5563; font-weight: 600;">
                                © {{ date('Y') }} Instituto Percorre — Todos os direitos reservados.
                            </p>
                            <p style="margin: 0; color: #9ca3af;">
                                Você recebeu esta mensagem porque seu e-mail está vinculado a um cadastro ou inscrição em nossa plataforma.
                            </p>
                        </td>
                    </tr>

                </table>

                <!-- 5. PIXEL DE RASTREAMENTO DE ABERTURA (1x1 INVISÍVEL) -->
                @if(!empty($pixelUrl))
                    <img src="{{ $pixelUrl }}" width="1" height="1" alt="" style="display:block; width:1px; height:1px; border:0; background:transparent;" />
                @endif

            </td>
        </tr>
    </table>

</body>
</html>