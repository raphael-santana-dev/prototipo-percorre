<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo_documento }} - Instituto Percorre</title>
    <style>
        @page {
            margin: 35px 38px 45px 38px;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #1f2937;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }

        .sandbox-banner {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            color: #92400e;
            text-align: center;
            padding: 6px 10px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        /* Cabeçalho Limpo e Formal (Sem Card/Fundo Escuro) */
        .header-clean {
            width: 100%;
            border-collapse: collapse;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }
        .brand-title {
            font-size: 16px;
            font-weight: bold;
            color: #111827;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .doc-title {
            font-size: 13px;
            font-weight: bold;
            color: #374151;
            margin: 4px 0 2px 0;
        }
        .doc-subtitle {
            font-size: 10.5px;
            color: #6b7280;
            margin: 0;
        }
        .meta-box-clean {
            text-align: right;
            vertical-align: top;
        }
        .meta-label {
            font-size: 8.5px;
            text-transform: uppercase;
            color: #6b7280;
            font-weight: bold;
            letter-spacing: 0.5px;
            display: block;
        }
        .meta-value {
            font-size: 13px;
            font-weight: bold;
            color: #111827;
            font-family: monospace;
            display: block;
            margin: 2px 0;
        }
        .meta-date {
            font-size: 9.5px;
            color: #6b7280;
        }

        /* Seções e Títulos (Sem borda/detalhe roxo na lateral) */
        .section-block {
            margin-bottom: 16px;
        }
        .section-header {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 6px 10px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #374151;
            margin-bottom: 6px;
        }

        /* Grade de Dados */
        .data-grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .data-grid td {
            border: 1px solid #e5e7eb;
            padding: 6.5px 10px;
            vertical-align: top;
            background-color: #ffffff;
            word-wrap: break-word;
        }
        .field-label {
            display: block;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #6b7280;
            letter-spacing: 0.4px;
            margin-bottom: 2.5px;
        }
        .field-value {
            font-size: 11px;
            font-weight: bold;
            color: #111827;
        }

        /* Sub-tabelas */
        .sub-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .sub-table th {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 4px 8px;
            font-size: 9px;
            text-align: left;
            color: #4b5563;
            text-transform: uppercase;
        }
        .sub-table td {
            border: 1px solid #e5e7eb;
            padding: 5px 8px;
            font-size: 10px;
            color: #111827;
        }

        /* Declaração e Assinatura */
        .signature-section {
            margin-top: 26px;
            padding-top: 14px;
            border-top: 1px dashed #d1d5db;
            page-break-inside: avoid;
        }
        .declaration-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 9px 12px;
            font-size: 9.5px;
            color: #4b5563;
            text-align: justify;
            margin-bottom: 35px;
        }
        .signature-area {
            width: 100%;
            text-align: center;
            margin-top: 15px;
        }
        .signature-image-wrapper {
            height: 55px;
            margin-bottom: -10px;
            text-align: center;
        }
        .signature-image {
            max-height: 50px;
            max-width: 220px;
        }
        .signature-spacer {
            height: 40px;
        }
        .signature-line {
            width: 320px;
            margin: 0 auto;
            border-bottom: 1.5px solid #111827;
        }
        .signer-name {
            margin-top: 6px;
            font-size: 11.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #111827;
            letter-spacing: 0.4px;
        }
        .signer-meta {
            margin-top: 2px;
            font-size: 9.5px;
            color: #6b7280;
        }

        .footer {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            height: 24px;
            border-top: 1px solid #e5e7eb;
            padding-top: 5px;
            font-size: 8.5px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="footer">
        Instituto Percorre &bull; Documento emitido eletronicamente em {{ $data_emissao }} &bull; Protocolo {{ $protocolo }}
    </div>

    @if($is_simulacao)
        <div class="sandbox-banner">
            Modo de Simulação (Sandbox) &mdash; Documento de teste sem validade oficial.
        </div>
    @endif

    <!-- CABEÇALHO LIMPO E FORMAL -->
    <table class="header-clean">
        <tr>
            <td style="width: 65%; vertical-align: top;">
                <div class="brand-title">INSTITUTO PERCORRE</div>
                <div class="doc-title">{{ $titulo_documento }}</div>
                <div class="doc-subtitle">{{ $subtitulo_documento }}</div>
            </td>
            <td class="meta-box-clean" style="width: 35%;">
                <span class="meta-label">Protocolo</span>
                <span class="meta-value">{{ $protocolo }}</span>
                <span class="meta-date">Emitido em: {{ $data_emissao }}</span>
            </td>
        </tr>
    </table>

    <!-- SEÇÕES FIXAS -->
    @foreach($secoes_fixas as $secao)
        <div class="section-block">
            <div class="section-header">{{ $secao['titulo'] }}</div>
            <table class="data-grid">
                @php
                    $itensSecao = $secao['itens'] ?? [];
                    $colAtiva = 0;
                @endphp
                @foreach($itensSecao as $item)
                    @php $isFull = ($item['largura'] ?? 'half') === 'full'; @endphp

                    @if($isFull)
                        @if($colAtiva === 1)
                            <td style="width: 50%; background-color: #f9fafb;"></td></tr>
                            @php $colAtiva = 0; @endphp
                        @endif
                        <tr>
                            <td colspan="2" style="width: 100%;">
                                <span class="field-label">{{ $item['label'] }}</span>
                                <span class="field-value">{{ $item['valor'] }}</span>
                            </td>
                        </tr>
                    @else
                        @if($colAtiva === 0)
                            <tr>
                                <td style="width: 50%;">
                                    <span class="field-label">{{ $item['label'] }}</span>
                                    <span class="field-value">{{ $item['valor'] }}</span>
                                </td>
                                @php $colAtiva = 1; @endphp
                        @else
                                <td style="width: 50%;">
                                    <span class="field-label">{{ $item['label'] }}</span>
                                    <span class="field-value">{{ $item['valor'] }}</span>
                                </td>
                            </tr>
                            @php $colAtiva = 0; @endphp
                        @endif
                    @endif
                @endforeach

                @if($colAtiva === 1)
                    <td style="width: 50%; background-color: #f9fafb;"></td></tr>
                @endif
            </table>
        </div>
    @endforeach

    <!-- RESPOSTAS DINÂMICAS -->
    @if(count($itens_dinamicos) > 0)
        <div class="section-block">
            <div class="section-header">
                {{ count($secoes_fixas) > 0 ? (count($secoes_fixas) + 1) . '. Respostas do Questionário Complementar' : 'Respostas Registradas no Formulário' }}
            </div>

            <table class="data-grid">
                @foreach($itens_dinamicos as $itemDin)
                    <tr style="page-break-inside: avoid;">
                        <td colspan="2" style="width: 100%;">
                            <span class="field-label">{{ $itemDin['label'] }}</span>

                            @if($itemDin['tipo'] === 'matriz')
                                @if(!empty($itemDin['valor']))
                                    <table class="sub-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 65%;">Item Avaliado</th>
                                                <th style="width: 35%;">Resposta</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($itemDin['valor'] as $linhaTexto => $respLinha)
                                                <tr>
                                                    <td>{{ $linhaTexto }}</td>
                                                    <td><strong>{{ $respLinha }}</strong></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <span class="field-value">-</span>
                                @endif

                            @elseif($itemDin['tipo'] === 'lista_chave_valor')
                                @if(!empty($itemDin['valor']))
                                    <table class="sub-table">
                                        <tbody>
                                            @foreach($itemDin['valor'] as $chave => $valRede)
                                                <tr>
                                                    <td style="width: 30%; background-color: #f9fafb; font-weight: bold;">{{ $chave }}</td>
                                                    <td style="width: 70%;">{{ $valRede }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <span class="field-value">-</span>
                                @endif

                            @elseif($itemDin['tipo'] === 'assinatura')
                                <div style="padding: 6px 0;">
                                    <img src="{{ $itemDin['valor'] }}" style="max-height: 55px; max-width: 220px;" alt="Assinatura">
                                </div>

                            @else
                                <span class="field-value">{!! nl2br(e($itemDin['valor'])) !!}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    <!-- BLOCO DE ASSINATURA -->
    <div class="signature-section">
        <div class="declaration-box">
            <strong>Declaração de Veracidade e Ciência:</strong> Declaro que todas as informações prestadas nesta ficha são verdadeiras e assumo inteira responsabilidade pela sua exatidão.
            @if($autorizacao_lgpd)
                Autorizo o tratamento e utilização dos dados informados pelo Instituto Percorre para fins acadêmicos, educacionais e de empregabilidade, em conformidade com a Lei Geral de Proteção de Dados (LGPD - Lei nº 13.709/2018).
            @endif
        </div>

        <div class="signature-area">
            @if(!empty($assinatura_base64))
                <div class="signature-image-wrapper">
                    <img src="{{ $assinatura_base64 }}" class="signature-image" alt="Assinatura Digital">
                </div>
            @else
                <div class="signature-spacer"></div>
            @endif

            <div class="signature-line"></div>
            <div class="signer-name">{{ $nome_assinante }}</div>
            <div class="signer-meta">
                @if(!empty($cpf_assinante))
                    CPF: {{ $cpf_assinante }} &bull;
                @endif
                Assinatura do(a) Titular / Responsável pelo Preenchimento
            </div>
        </div>
    </div>

</body>
</html>