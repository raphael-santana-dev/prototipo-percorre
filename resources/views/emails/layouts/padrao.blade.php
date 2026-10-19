<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $assunto ?? config('app.name') }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1f2937; -webkit-font-smoothing: antialiased;">
    
    <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #f3f4f6; padding: 32px 16px;">
        <tr>
            <td align="center">
                
                <!-- CARD PRINCIPAL DO E-MAIL (600px) -->
                <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06); border: 1px solid #e5e7eb;">
                    
                    <!-- 1. TOPO / BANNER COM LOGO -->
                    <tr>
                        <td align="center" style="background: linear-gradient(135deg, #461a63 0%, #6b21a8 100%); background-color: #461a63; padding: 28px 24px; border-bottom: 4px solid #f59e0b;">
                            @if(!empty($headerImageUrl))
                                <img src="{{ $headerImageUrl }}" alt="Instituto Percorre" style="max-height: 52px; width: auto; display: block; border: 0; margin: 0 auto;">
                            @else
                                <span style="color: #ffffff; font-size: 22px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase;">
                                    Instituto Percorre
                                </span>
                            @endif
                        </td>
                    </tr>

                    <!-- 2. CORPO DINÂMICO DO TEMPLATE -->
                    <tr>
                        <td style="padding: 36px 32px 24px 32px; font-size: 15px; line-height: 1.7; color: #374151;">
                            {!! $corpoHtml !!}
                        </td>
                    </tr>

                    <!-- 3. ASSINATURA PADRÃO -->
                    <tr>
                        <td style="padding: 8px 32px 32px 32px;">
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-top: 1px solid #e5e7eb; padding-top: 20px;">
                                <tr>
                                    <td width="48" valign="top" style="padding-right: 14px;">
                                        <div style="width: 44px; height: 44px; border-radius: 10px; background-color: #f3e8ff; color: #6b21a8; font-weight: 800; font-size: 18px; line-height: 44px; text-align: center;">
                                            IP
                                        </div>
                                    </td>
                                    <td valign="middle" style="font-size: 13px; color: #6b7280; line-height: 1.5;">
                                        <strong style="color: #111827; font-size: 14px; display: block;">
                                            {{ $assinaturaNome ?? 'Equipe Instituto Percorre' }}
                                        </strong>
                                        <span>{{ $assinaturaCargo ?? 'Relacionamento Acadêmico & Processo Seletivo' }}</span><br>
                                        <a href="{{ config('app.url') }}" style="color: #7c3aed; text-decoration: none; font-weight: 600;">
                                            {{ parse_url(config('app.url'), PHP_URL_HOST) ?: config('app.url') }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- 4. RODAPÉ INSTITUCIONAL -->
                    <tr>
                        <td align="center" style="background-color: #f9fafb; padding: 22px 32px; border-top: 1px solid #e5e7eb; font-size: 11px; color: #9ca3af; line-height: 1.6;">
                            <p style="margin: 0 0 6px 0; color: #6b7280; font-weight: 600;">
                                © {{ date('Y') }} Instituto Percorre — Todos os direitos reservados.
                            </p>
                            <p style="margin: 0;">
                                Você recebeu esta mensagem porque seu endereço de e-mail está vinculado a um cadastro ou inscrição em nossa plataforma.
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