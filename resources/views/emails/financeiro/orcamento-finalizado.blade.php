<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamento Finalizado</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f3f4f6; color: #1f2937;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; border: 1px solid #e5e7eb; overflow: hidden;">
                    
                    <!-- Cabeçalho -->
                    <tr>
                        <td style="background-color: #111827; padding: 24px 32px; color: #ffffff;">
                            <h1 style="margin: 0; font-size: 18px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;">
                                Gestão de Orçamento
                            </h1>
                            <p style="margin: 4px 0 0 0; font-size: 12px; color: #9ca3af;">
                                Notificação Automática de Finalização
                            </p>
                        </td>
                    </tr>

                    <!-- Corpo -->
                    <tr>
                        <td style="padding: 32px;">
                            <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: #374151;">
                                Olá,
                            </p>
                            <p style="margin: 0 0 24px 0; font-size: 14px; line-height: 1.6; color: #374151;">
                                Informamos que o orçamento da área abaixo foi <strong>finalizado</strong> pelo coordenador responsável e já encontra-se disponível na plataforma para avaliação e sincronizado na tabela intermediária.
                            </p>

                            <!-- Card de Resumo -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 20px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding-bottom: 12px; border-bottom: 1px solid #e5e7eb;">
                                        <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #6b7280; display: block;">Centro de Custo</span>
                                        <span style="font-size: 15px; font-weight: 700; color: #111827;">
                                            {{ $orcamento->ccusto }} - {{ $orcamento->centroCusto ? $orcamento->centroCusto->nome : 'Sem Vínculo' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top: 12px;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="50%" style="padding-bottom: 8px;">
                                                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #6b7280; display: block;">Ano Base</span>
                                                    <span style="font-size: 14px; font-weight: 600; color: #1f2937;">{{ $orcamento->ano }}</span>
                                                </td>
                                                <td width="50%" style="padding-bottom: 8px;">
                                                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #6b7280; display: block;">Status</span>
                                                    <span style="font-size: 12px; font-weight: 700; color: #7e22ce; background-color: #f3e8ff; padding: 2px 8px; border-radius: 9999px; display: inline-block;">
                                                        {{ strtoupper($orcamento->status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="50%" style="padding-top: 8px;">
                                                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #6b7280; display: block;">Finalizado por</span>
                                                    <span style="font-size: 13px; font-weight: 600; color: #1f2937;">{{ $coordenadorNome }}</span>
                                                </td>
                                                <td width="50%" style="padding-top: 8px;">
                                                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #6b7280; display: block;">Total Previsto ({{ $qtdItens }} naturezas)</span>
                                                    <span style="font-size: 15px; font-weight: 800; color: #059669;">
                                                        R$ {{ number_format($valorTotal, 2, ',', '.') }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            @if(!empty($orcamento->descricao_despesa))
                                <div style="margin-bottom: 24px;">
                                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #6b7280; display: block; margin-bottom: 4px;">Justificativa / Observação Global:</span>
                                    <p style="margin: 0; font-size: 13px; color: #4b5563; background-color: #ffffff; border-left: 3px solid #9ca3af; padding: 8px 12px; font-style: italic;">
                                        "{{ $orcamento->descricao_despesa }}"
                                    </p>
                                </div>
                            @endif

                            <!-- Botão de Ação -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding-top: 8px;">
                                        <a href="{{ route('financeiro.aprovacoes') }}" style="background-color: #111827; color: #ffffff; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 700; display: inline-block;">
                                            Acessar Painel de Aprovações
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Rodapé -->
                    <tr>
                        <td style="background-color: #f9fafb; border-top: 1px solid #e5e7eb; padding: 16px 32px; text-align: center;">
                            <p style="margin: 0; font-size: 11px; color: #9ca3af;">
                                Este é um e-mail automático gerado pelo Sistema Administrativo em {{ now()->format('d/m/Y \à\s H:i') }}.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>