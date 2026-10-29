<?php

namespace App\Traits;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Ciclo;
use App\Models\Curso;
use App\Modules\Unidade\Domain\Models\Unidade;
use App\Modules\Turno\Domain\Models\Turno;

trait WithFichaPdfDownload
{
    public function baixarFichaPdf()
    {
        $dadosPdf = $this->montarDadosFichaPdf();

        $pdf = Pdf::loadView('pdf.ficha-respostas', $dadosPdf)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
                'defaultFont'          => 'sans-serif',
            ]);

        $slugArquivo = Str::slug($dadosPdf['titulo_documento'] . '-' . ($dadosPdf['nome_assinante'] ?: 'comprovante'));

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, "{$slugArquivo}.pdf");
    }

    protected function montarDadosFichaPdf(): array
    {
        $isInscricaoCiclo = property_exists($this, 'cicloAtivoId');
        $isPreInscricao   = property_exists($this, 'formulario') && ($this->formulario->tipo ?? '') === 'pre_inscricao';

        // 1. Título e Subtítulo do Documento
        if ($isInscricaoCiclo) {
            $ciclo = Ciclo::find($this->cicloAtivoId);
            $tituloDoc = $this->etapaAtual === 100
                ? 'Comprovante de Lista de Espera'
                : 'Ficha Oficial de Inscrição';
            $subtituloDoc = $ciclo ? "{$ciclo->nome} ({$ciclo->ano}.{$ciclo->semestre})" : 'Processo Seletivo';
            $protocoloId = $this->inscricaoId
                ? '#' . str_pad((string) $this->inscricaoId, 6, '0', STR_PAD_LEFT)
                : 'SIMULAÇÃO-' . strtoupper( substr(md5(uniqid()), 0, 6) );
        } else {
            $tituloDoc = $this->formulario->titulo ?? 'Ficha de Respostas';
            $subtituloDoc = $isPreInscricao
                ? 'Registro de Interesse / Pré-Inscrição'
                : ($this->formulario->descricao ?: 'Comprovante Oficial de Preenchimento');
            $protocoloId = $this->respostaId
                ? '#' . str_pad((string) $this->respostaId, 6, '0', STR_PAD_LEFT)
                : 'SIMULAÇÃO-' . strtoupper( substr(md5(uniqid()), 0, 6) );
        }

        // 2. Identificação do Nome do Preenchedor (Para a Linha de Assinatura)
        $nomeAssinante = $this->resolverNomePreenchedorPdf();
        $cpfAssinante  = $this->resolverCpfPreenchedorPdf();

        // 3. Montagem das Seções Fixas (Quando for Inscrição ou Pré-Inscrição)
        $secoesFixas = [];

        if ($isInscricaoCiclo) {
            if ($this->exibirDadosPessoaisFixos ?? true) {
                $emailExibir = (!empty($this->emailOriginal) && str_contains((string) $this->email, '*'))
                    ? $this->emailOriginal
                    : $this->email;
                $celularExibir = (!empty($this->celularOriginal) && str_contains((string) $this->celular, '*'))
                    ? $this->celularOriginal
                    : $this->celular;

                $dadosPessoais = [
                    ['label' => 'Nome Completo', 'valor' => $this->nome ?: '-', 'largura' => 'full'],
                ];

                if (($this->possui_nome_social ?? 'nao') === 'sim' && !empty($this->nome_social)) {
                    $dadosPessoais[] = ['label' => 'Nome Social', 'valor' => $this->nome_social, 'largura' => 'full'];
                }

                $dadosPessoais[] = ['label' => 'CPF', 'valor' => $this->cpf ?: '-', 'largura' => 'half'];
                $dadosPessoais[] = [
                    'label'   => 'Data de Nascimento',
                    'valor'   => $this->data_nascimento ? Carbon::parse($this->data_nascimento)->format('d/m/Y') : '-',
                    'largura' => 'half'
                ];
                $dadosPessoais[] = ['label' => 'E-mail', 'valor' => $emailExibir ?: '-', 'largura' => 'half'];
                $dadosPessoais[] = ['label' => 'Celular / Telefone', 'valor' => $celularExibir ?: '-', 'largura' => 'half'];
                $dadosPessoais[] = [
                    'label'   => 'Pessoa com Deficiência (PcD)',
                    'valor'   => ($this->possui_deficiencia === 'sim')
                        ? 'Sim (' . ucfirst($this->natureza_deficiencia ?: 'Não especificada') . ')'
                        : 'Não',
                    'largura' => 'full'
                ];

                $secoesFixas[] = [
                    'titulo' => '1. Dados Pessoais do Candidato',
                    'itens'  => $dadosPessoais,
                ];
            }

            if ($this->exibirEnderecoFixo ?? true) {
                $secoesFixas[] = [
                    'titulo' => '2. Endereço Residencial',
                    'itens'  => [
                        ['label' => 'CEP', 'valor' => $this->cep ?: '-', 'largura' => 'half'],
                        ['label' => 'Região', 'valor' => $this->regiao ? ucfirst($this->regiao) : '-', 'largura' => 'half'],
                        ['label' => 'Logradouro', 'valor' => ($this->logradouro ?: '-') . ($this->numero ? ", Nº {$this->numero}" : ''), 'largura' => 'full'],
                        ['label' => 'Bairro', 'valor' => $this->bairro ?: '-', 'largura' => 'half'],
                        ['label' => 'Complemento', 'valor' => $this->complemento ?: '-', 'largura' => 'half'],
                        ['label' => 'Cidade / UF', 'valor' => ($this->cidade ?: '-') . ($this->estado ? " / {$this->estado}" : ''), 'largura' => 'full'],
                    ],
                ];
            }

            if ($this->exibirSelecaoCursoFixo ?? true) {
                if (!empty($this->unidade) || !empty($this->curso) || !empty($this->turno)) {
                    $uniNome = $this->unidadesDisponiveis[$this->unidade] ?? Unidade::find($this->unidade)?->nome ?? '-';
                    $curNome = $this->cursosDisponiveis[$this->curso] ?? Curso::find($this->curso)?->nome ?? '-';
                    $turNome = $this->turnosDisponiveis[$this->turno] ?? Turno::find($this->turno)?->nome ?? '-';

                    $secoesFixas[] = [
                        'titulo' => '3. Opção de Curso e Turma Selecionada',
                        'itens'  => [
                            ['label' => 'Unidade', 'valor' => $uniNome, 'largura' => 'full'],
                            ['label' => 'Curso Escolhido', 'valor' => $curNome, 'largura' => 'half'],
                            ['label' => 'Turno', 'valor' => $turNome, 'largura' => 'half'],
                        ],
                    ];
                } elseif (!empty($this->deseja_informar)) {
                    $uniNome = Unidade::find($this->unidade_interesse)?->nome ?? 'Qualquer Unidade';
                    $curNome = Curso::find($this->curso_interesse)?->nome ?? '-';
                    $turNome = Turno::find($this->turno_interesse)?->nome ?? '-';

                    $secoesFixas[] = [
                        'titulo' => '3. Interesse Registrado (Lista de Espera)',
                        'itens'  => [
                            ['label' => 'Unidade de Interesse', 'valor' => $uniNome, 'largura' => 'full'],
                            ['label' => 'Curso de Interesse', 'valor' => $curNome, 'largura' => 'half'],
                            ['label' => 'Turno de Interesse', 'valor' => $turNome, 'largura' => 'half'],
                        ],
                    ];
                }
            }
        } elseif ($isPreInscricao && !empty($this->deseja_informar)) {
            $uniNome = Unidade::find($this->unidade_interesse)?->nome ?? '-';
            $curNome = Curso::find($this->curso_interesse)?->nome ?? '-';
            $turNome = Turno::find($this->turno_interesse)?->nome ?? '-';

            $secoesFixas[] = [
                'titulo' => 'Local e Curso de Interesse',
                'itens'  => [
                    ['label' => 'Unidade de Interesse', 'valor' => $uniNome, 'largura' => 'full'],
                    ['label' => 'Curso de Interesse', 'valor' => $curNome, 'largura' => 'half'],
                    ['label' => 'Turno de Interesse', 'valor' => $turNome, 'largura' => 'half'],
                ],
            ];
        }

        // 4. Montagem dos Campos Dinâmicos preenchidos
        $itensDinamicos = [];
        $assinaturaDigitalBase64 = null;

        if (!empty($this->respostas['_email_coletado'])) {
            $itensDinamicos[] = [
                'label' => 'E-mail Informado',
                'tipo'  => 'texto',
                'valor' => $this->respostas['_email_coletado'],
            ];
        }

        $campos = collect($this->camposDinamicos ?? [])
            ->whereNotIn('tipo', ['config', 'html', 'divider', 'media', 'spacer', 'link']);

        foreach ($campos as $campo) {
            if (!$this->campoVisivelNoPdf($campo)) {
                continue;
            }

            $valorBruto = $this->respostas[$campo->name] ?? null;
            $labelLimpo = trim(strip_tags((string) $campo->label));
            $cfg = is_string($campo->configuracoes) ? json_decode($campo->configuracoes, true) : ($campo->configuracoes ?? []);

            if ($campo->tipo === 'signature') {
                $labelAssinatura = $labelLimpo ?: (($cfg['tipo_assinante'] ?? $campo->subtipo) === 'responsavel' ? 'Assinatura do Responsável' : 'Assinatura Digital');
                if (is_string($valorBruto) && str_starts_with($valorBruto, 'data:image/')) {
                    $assinaturaDigitalBase64 = $valorBruto;
                    $itensDinamicos[] = [
                        'label' => $labelAssinatura,
                        'tipo'  => 'assinatura',
                        'valor' => $valorBruto,
                    ];
                } else {
                    $itensDinamicos[] = [
                        'label' => $labelAssinatura,
                        'tipo'  => 'texto',
                        'valor' => 'Não assinada digitalmente',
                    ];
                }
                continue;
            }

            if ($campo->tipo === 'matriz') {
                $linhas = $cfg['linhas'] ?? [];
                $respostasMatriz = [];
                if (is_array($valorBruto)) {
                    foreach ($linhas as $idxLinha => $textoLinha) {
                        $respostasMatriz[$textoLinha] = $valorBruto[$idxLinha] ?? '-';
                    }
                }
                $itensDinamicos[] = [
                    'label' => $labelLimpo,
                    'tipo'  => 'matriz',
                    'valor' => $respostasMatriz,
                ];
                continue;
            }

            if ($campo->tipo === 'social') {
                $redesPreenchidas = [];
                if (is_array($valorBruto)) {
                    foreach ($valorBruto as $rede => $perfil) {
                        if (!empty(trim((string) $perfil))) {
                            $redesPreenchidas[ucfirst($rede)] = $perfil;
                        }
                    }
                }
                $itensDinamicos[] = [
                    'label' => $labelLimpo,
                    'tipo'  => 'lista_chave_valor',
                    'valor' => $redesPreenchidas,
                ];
                continue;
            }

            if ($campo->tipo === 'file') {
                $arquivos = array_filter((array) $valorBruto);
                $nomesArquivos = array_map(fn($p) => basename((string) $p), $arquivos);
                $itensDinamicos[] = [
                    'label' => $labelLimpo,
                    'tipo'  => 'texto',
                    'valor' => !empty($nomesArquivos) ? implode(', ', $nomesArquivos) : 'Nenhum anexo enviado',
                ];
                continue;
            }

            if ($campo->tipo === 'system') {
                $valorFormatado = '-';
                if (!empty($valorBruto)) {
                    if ($campo->subtipo === 'unidade') {
                        $valorFormatado = Unidade::find($valorBruto)?->nome ?? $valorBruto;
                    } elseif ($campo->subtipo === 'curso') {
                        $valorFormatado = Curso::find($valorBruto)?->nome ?? $valorBruto;
                    } elseif ($campo->subtipo === 'turno') {
                        $valorFormatado = Turno::find($valorBruto)?->nome ?? $valorBruto;
                    } else {
                        $valorFormatado = (string) $valorBruto;
                    }
                }
                $itensDinamicos[] = [
                    'label' => $labelLimpo,
                    'tipo'  => 'texto',
                    'valor' => $valorFormatado,
                ];
                continue;
            }

            // Campos convencionais (texto, select, radio, check, data, moeda, rating, scale)
            if (is_array($valorBruto)) {
                $valorFormatado = !empty($valorBruto) ? implode(', ', array_filter($valorBruto)) : '-';
            } elseif ($campo->subtipo === 'date' && !empty($valorBruto)) {
                try {
                    $valorFormatado = Carbon::parse($valorBruto)->format('d/m/Y');
                } catch (\Throwable $e) {
                    $valorFormatado = (string) $valorBruto;
                }
            } elseif ($campo->subtipo === 'money' && !empty($valorBruto)) {
                $valorFormatado = 'R$ ' . $valorBruto;
            } elseif ($campo->tipo === 'rating' && $valorBruto !== '' && $valorBruto !== null) {
                $maxStars = $cfg['max_stars'] ?? 5;
                $valorFormatado = "{$valorBruto} de {$maxStars} estrelas";
            } elseif ($campo->tipo === 'scale' && $valorBruto !== '' && $valorBruto !== null) {
                $maxScale = $cfg['escala_maxima'] ?? 10;
                $valorFormatado = "Nota {$valorBruto} (Escala de 0 a {$maxScale})";
            } else {
                $valorFormatado = ($valorBruto !== '' && $valorBruto !== null) ? (string) $valorBruto : '-';
            }

            $itensDinamicos[] = [
                'label' => $labelLimpo,
                'tipo'  => 'texto',
                'valor' => $valorFormatado,
            ];
        }

        // Busca a logo em base64 para embutir sem falhas no DomPDF
        $logoBase64 = null;
        $possiveisLogos = [
            public_path('images/logo.png'),
            resource_path('images/logo.png'),
            resource_path('images/logo-nav-white.svg'),
        ];
        foreach ($possiveisLogos as $caminhoLogo) {
            if (file_exists($caminhoLogo)) {
                $ext = pathinfo($caminhoLogo, PATHINFO_EXTENSION);
                $mime = $ext === 'svg' ? 'image/svg+xml' : "image/{$ext}";
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($caminhoLogo));
                break;
            }
        }

        return [
            'titulo_documento'    => $tituloDoc,
            'subtitulo_documento' => $subtituloDoc,
            'protocolo'           => $protocoloId,
            'data_emissao'        => now()->format('d/m/Y \à\s H:i'),
            'is_simulacao'        => (bool) ($this->isTeste ?? false),
            'secoes_fixas'        => $secoesFixas,
            'itens_dinamicos'     => $itensDinamicos,
            'nome_assinante'      => $nomeAssinante,
            'cpf_assinante'       => $cpfAssinante,
            'assinatura_base64'   => $assinaturaDigitalBase64,
            'logo_base64'         => $logoBase64,
            'autorizacao_lgpd'    => property_exists($this, 'autorizacao_uso_infos') ? (bool) $this->autorizacao_uso_infos : true,
        ];
    }

    protected function resolverNomePreenchedorPdf(): string
    {
        // 1. Se for o componente Inscricao e tiver nome preenchido
        if (property_exists($this, 'nome') && !empty(trim((string) $this->nome))) {
            return trim((string) $this->nome);
        }

        // 2. Procura nas respostas dinâmicas algum campo cujo name ou label indique Nome
        if (!empty($this->camposDinamicos)) {
            foreach ($this->camposDinamicos as $campo) {
                $nameLower  = mb_strtolower((string) $campo->name, 'UTF-8');
                $labelLower = mb_strtolower(strip_tags((string) $campo->label), 'UTF-8');

                if (
                    in_array($nameLower, ['nome', 'nome_completo', 'candidato', 'aluno', 'estudante', 'responsavel']) ||
                    str_contains($nameLower, 'nome') ||
                    str_contains($labelLower, 'nome completo') ||
                    $labelLower === 'nome'
                ) {
                    $val = $this->respostas[$campo->name] ?? null;
                    if (!empty($val) && is_string($val)) {
                        return trim($val);
                    }
                }
            }
        }

        // 3. Fallback: Usuário ou Estudante autenticado na sessão
        if (auth('student')->check() && !empty(auth('student')->user()->name)) {
            return auth('student')->user()->name;
        }
        if (auth('web')->check() && !empty(auth('web')->user()->name)) {
            return auth('web')->user()->name;
        }

        // 4. Fallback: E-mail coletado
        if (!empty($this->respostas['_email_coletado'])) {
            return $this->respostas['_email_coletado'];
        }

        return 'Participante / Candidato(a)';
    }

    protected function resolverCpfPreenchedorPdf(): ?string
    {
        if (property_exists($this, 'cpf') && !empty(trim((string) $this->cpf))) {
            return trim((string) $this->cpf);
        }

        if (!empty($this->camposDinamicos)) {
            foreach ($this->camposDinamicos as $campo) {
                if ($campo->subtipo === 'cpf' || str_contains(strtolower((string) $campo->name), 'cpf')) {
                    $val = $this->respostas[$campo->name] ?? null;
                    if (!empty($val) && is_string($val)) {
                        return trim($val);
                    }
                }
            }
        }

        return null;
    }

    protected function campoVisivelNoPdf($campo): bool
    {
        if (empty($campo->depende_de) || empty($campo->depende_valor)) {
            return true;
        }

        $valorGatilho = $this->respostas[$campo->depende_de] ?? ($this->{$campo->depende_de} ?? null);
        $op  = $campo->depende_operador ?? '=';
        $tgt = strtolower(trim((string) $campo->depende_valor));

        if (is_array($valorGatilho)) {
            $arrayValores = array_map('strtolower', array_map('trim', $valorGatilho));
            $arrayAlvos   = array_map('trim', explode(',', $tgt));
            return match ($op) {
                '='     => in_array($tgt, $arrayValores),
                '!='    => !in_array($tgt, $arrayValores),
                'in'    => !empty(array_intersect($arrayValores, $arrayAlvos)),
                default => false,
            };
        }

        $val = strtolower(trim((string) $valorGatilho));
        return match ($op) {
            '='     => ($val === $tgt),
            '!='    => ($val !== $tgt),
            '>'     => (is_numeric($val) && is_numeric($tgt) && $val > $tgt),
            '<'     => (is_numeric($val) && is_numeric($tgt) && $val < $tgt),
            '>='    => (is_numeric($val) && is_numeric($tgt) && $val >= $tgt),
            '<='    => (is_numeric($val) && is_numeric($tgt) && $val <= $tgt),
            'in'    => in_array($val, array_map('trim', explode(',', $tgt))),
            default => false,
        };
    }
}