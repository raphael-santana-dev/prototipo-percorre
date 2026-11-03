<?php

namespace App\Traits;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Ciclo;
use App\Models\Curso;
use App\Models\Inscricao;
use App\Models\Formulario;
use App\Models\RespostaFormulario;
use App\Models\CampoFormulario;
use App\Modules\Unidade\Domain\Models\Unidade;
use App\Modules\Turno\Domain\Models\Turno;

trait WithFichaPdfDownload
{
    /**
     * Uso no encerramento do preenchimento público (ou no detalhe quando $this->resposta / $this->inscricao estiver presente)
     */
    public function baixarFichaPdf()
    {
        if (property_exists($this, 'resposta') && $this->resposta instanceof RespostaFormulario) {
            return $this->baixarFichaRespostaPdf($this->resposta->id);
        }

        if (property_exists($this, 'inscricao') && $this->inscricao instanceof Inscricao) {
            return $this->baixarFichaInscricaoPdf($this->inscricao->id);
        }

        $dadosPdf = $this->montarDadosFichaPdf();
        return $this->gerarStreamPdf($dadosPdf);
    }

    /**
     * Uso no Backoffice: Exporta a Ficha de Respostas (PDF) de uma RespostaFormulario específica
     */
    public function baixarFichaRespostaPdf(?int $respostaId = null)
    {
        $idAlvo = $respostaId ?? ($this->resposta->id ?? null);
        $respostaModel = RespostaFormulario::with('formulario')->findOrFail($idAlvo);
        $formulario = $respostaModel->formulario ?? Formulario::findOrFail($respostaModel->formulario_id);

        $respostasArray = is_string($respostaModel->respostas)
            ? (json_decode($respostaModel->respostas, true) ?? [])
            : ($respostaModel->respostas ?? []);

        $campos = CampoFormulario::where('formulario_id', $formulario->id)
            ->whereNotIn('tipo', ['config', 'html', 'divider', 'media', 'spacer', 'link'])
            ->orderBy('etapa', 'asc')
            ->orderBy('ordem', 'asc')
            ->get();

        [$itensDinamicos, $assinaturaBase64] = $this->formatarCamposDinamicosParaPdf($campos, $respostasArray);

        $nomeAssinante = $this->extrairNomeDeRespostas($campos, $respostasArray) ?: 'Participante';
        $cpfAssinante  = $this->extrairCpfDeRespostas($campos, $respostasArray);

        $dadosPdf = [
            'titulo_documento'    => $formulario->titulo ?? 'Ficha de Respostas',
            'subtitulo_documento' => $formulario->descricao ?: 'Comprovante Oficial de Preenchimento',
            'protocolo'           => '#' . str_pad((string) $respostaModel->id, 6, '0', STR_PAD_LEFT),
            'data_emissao'        => $respostaModel->created_at
                ? $respostaModel->created_at->format('d/m/Y \à\s H:i')
                : now()->format('d/m/Y \à\s H:i'),
            'is_simulacao'        => false,
            'secoes_fixas'        => [],
            'itens_dinamicos'     => $itensDinamicos,
            'nome_assinante'      => $nomeAssinante,
            'cpf_assinante'       => $cpfAssinante,
            'assinatura_base64'   => $assinaturaBase64,
            'logo_base64'         => $this->obterLogoBase64Pdf(),
            'autorizacao_lgpd'    => true,
        ];

        return $this->gerarStreamPdf($dadosPdf);
    }

    /**
     * Uso no Backoffice: Exporta a Ficha Oficial de Inscrição (PDF) de uma Inscricao específica
     */
    public function baixarFichaInscricaoPdf(?int $inscricaoId = null)
    {
        $idAlvo = $inscricaoId ?? ($this->inscricao->id ?? null);
        $insc = Inscricao::with(['unidade', 'curso', 'turno', 'ciclo'])->findOrFail($idAlvo);

        $tituloDoc = ($insc->etapa_atual == 100 || $insc->deseja_informar)
            ? 'Comprovante de Lista de Espera'
            : 'Ficha Oficial de Inscrição';

        $subtituloDoc = $insc->ciclo
            ? "{$insc->ciclo->nome}" . ($insc->ciclo->ano ? " ({$insc->ciclo->ano}.{$insc->ciclo->semestre})" : '')
            : 'Processo Seletivo';

        $dadosPessoais = [
            ['label' => 'Nome Completo', 'valor' => $insc->nome ?: '-', 'largura' => 'full'],
        ];

        if (!empty($insc->nome_social)) {
            $dadosPessoais[] = ['label' => 'Nome Social', 'valor' => $insc->nome_social, 'largura' => 'full'];
        }

        $dadosPessoais[] = ['label' => 'CPF', 'valor' => $insc->cpf ?: '-', 'largura' => 'half'];
        $dadosPessoais[] = [
            'label'   => 'Data de Nascimento',
            'valor'   => $insc->data_nascimento ? Carbon::parse($insc->data_nascimento)->format('d/m/Y') : '-',
            'largura' => 'half'
        ];
        $dadosPessoais[] = ['label' => 'E-mail', 'valor' => $insc->email ?: '-', 'largura' => 'half'];
        $dadosPessoais[] = ['label' => 'Celular / Telefone', 'valor' => $insc->celular ?: '-', 'largura' => 'half'];
        $dadosPessoais[] = [
            'label'   => 'Pessoa com Deficiência (PcD)',
            'valor'   => (strtolower((string) $insc->possui_deficiencia) === 'sim')
                ? 'Sim (' . ucfirst($insc->natureza_deficiencia ?: 'Não especificada') . ')'
                : 'Não',
            'largura' => 'full'
        ];

        $secoesFixas = [
            [
                'titulo' => '1. Dados Pessoais do Candidato',
                'itens'  => $dadosPessoais,
            ],
            [
                'titulo' => '2. Endereço Residencial',
                'itens'  => [
                    ['label' => 'CEP', 'valor' => $insc->cep ?: '-', 'largura' => 'half'],
                    ['label' => 'Região', 'valor' => $insc->regiao ? ucfirst($insc->regiao) : '-', 'largura' => 'half'],
                    ['label' => 'Logradouro', 'valor' => ($insc->logradouro ?: '-') . ($insc->numero ? ", Nº {$insc->numero}" : ''), 'largura' => 'full'],
                    ['label' => 'Bairro', 'valor' => $insc->bairro ?: '-', 'largura' => 'half'],
                    ['label' => 'Complemento', 'valor' => $insc->complemento ?: '-', 'largura' => 'half'],
                    ['label' => 'Cidade / UF', 'valor' => ($insc->cidade ?: '-') . ($insc->estado ? " / {$insc->estado}" : ''), 'largura' => 'full'],
                ],
            ],
        ];

        if ($insc->deseja_informar) {
            $uniNome = Unidade::find($insc->unidade_interesse_id)?->nome ?? 'Qualquer Unidade';
            $curNome = Curso::find($insc->curso_interesse_id)?->nome ?? '-';
            $turNome = Turno::find($insc->turno_interesse_id)?->nome ?? '-';

            $secoesFixas[] = [
                'titulo' => '3. Interesse Registrado (Lista de Espera)',
                'itens'  => [
                    ['label' => 'Unidade de Interesse', 'valor' => $uniNome, 'largura' => 'full'],
                    ['label' => 'Curso de Interesse', 'valor' => $curNome, 'largura' => 'half'],
                    ['label' => 'Turno de Interesse', 'valor' => $turNome, 'largura' => 'half'],
                ],
            ];
        } else {
            $secoesFixas[] = [
                'titulo' => '3. Opção de Curso e Turma Selecionada',
                'itens'  => [
                    ['label' => 'Unidade', 'valor' => $insc->unidade->nome ?? '-', 'largura' => 'full'],
                    ['label' => 'Curso Escolhido', 'valor' => $insc->curso->nome ?? '-', 'largura' => 'half'],
                    ['label' => 'Turno', 'valor' => $insc->turno->nome ?? '-', 'largura' => 'half'],
                ],
            ];
        }

        $respostasArray = is_string($insc->dados_dinamicos)
            ? (json_decode($insc->dados_dinamicos, true) ?? [])
            : ($insc->dados_dinamicos ?? []);

        $camposCiclo = $insc->ciclo_id
            ? CampoFormulario::where('ciclo_id', $insc->ciclo_id)
                ->whereNotIn('tipo', ['config', 'html', 'divider', 'media', 'spacer', 'link'])
                ->orderBy('etapa', 'asc')
                ->orderBy('ordem', 'asc')
                ->get()
            : collect();

        [$itensDinamicos, $assinaturaBase64] = $this->formatarCamposDinamicosParaPdf($camposCiclo, $respostasArray, true);

        $dadosPdf = [
            'titulo_documento'    => $tituloDoc,
            'subtitulo_documento' => $subtituloDoc,
            'protocolo'           => '#' . str_pad((string) $insc->id, 6, '0', STR_PAD_LEFT),
            'data_emissao'        => $insc->created_at
                ? $insc->created_at->format('d/m/Y \à\s H:i')
                : now()->format('d/m/Y \à\s H:i'),
            'is_simulacao'        => false,
            'secoes_fixas'        => $secoesFixas,
            'itens_dinamicos'     => $itensDinamicos,
            'nome_assinante'      => $insc->nome ?: 'Candidato(a)',
            'cpf_assinante'       => $insc->cpf ?: null,
            'assinatura_base64'   => $assinaturaBase64,
            'logo_base64'         => $this->obterLogoBase64Pdf(),
            'autorizacao_lgpd'    => (bool) ($insc->autorizacao_uso_infos ?? true),
        ];

        return $this->gerarStreamPdf($dadosPdf);
    }

    protected function gerarStreamPdf(array $dadosPdf)
    {
        $pdf = Pdf::loadView('pdf.ficha-respostas', $dadosPdf)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
                'defaultFont'          => 'sans-serif',
            ]);

        $slugArquivo = Str::slug(($dadosPdf['titulo_documento'] ?? 'ficha') . '-' . ($dadosPdf['nome_assinante'] ?: 'comprovante'));

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, "{$slugArquivo}.pdf");
    }

    /**
     * Formata campos dinâmicos tanto para Formulários quanto para Inscrições
     */
    protected function formatarCamposDinamicosParaPdf($campos, array $respostasArray, bool $incluirChavesExtras = false): array
    {
        $itensDinamicos = [];
        $assinaturaDigitalBase64 = null;
        $chavesProcessadas = ['_email_coletado'];

        if (!empty($respostasArray['_email_coletado'])) {
            $itensDinamicos[] = [
                'label' => 'E-mail Informado',
                'tipo'  => 'texto',
                'valor' => $respostasArray['_email_coletado'],
            ];
        }

        foreach ($campos as $campo) {
            $chavesProcessadas[] = $campo->name;
            $valorBruto = $respostasArray[$campo->name] ?? null;
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

        // Caso haja campos importados ou legados em dados_dinamicos que não estão em CampoFormulario
        if ($incluirChavesExtras) {
            foreach ($respostasArray as $chave => $valor) {
                if (in_array($chave, $chavesProcessadas, true)) continue;
                if (str_contains(strtolower($chave), 'form_config') || str_starts_with(strtolower($chave), 'ui_')) continue;
                if (in_array(strtolower(trim($chave)), ['submission started', 'last updated'], true)) continue;

                $labelChave = ucfirst(str_replace('_', ' ', $chave));

                if (is_string($valor) && str_starts_with($valor, 'data:image/')) {
                    $assinaturaDigitalBase64 = $valor;
                    $itensDinamicos[] = [
                        'label' => $labelChave,
                        'tipo'  => 'assinatura',
                        'valor' => $valor,
                    ];
                } else {
                    $itensDinamicos[] = [
                        'label' => $labelChave,
                        'tipo'  => 'texto',
                        'valor' => is_array($valor) ? implode(', ', array_filter($valor)) : ((string) ($valor ?: '-')),
                    ];
                }
            }
        }

        return [$itensDinamicos, $assinaturaDigitalBase64];
    }

    protected function extrairNomeDeRespostas($campos, array $respostas): ?string
    {
        foreach ($campos as $campo) {
            $nameLower  = mb_strtolower((string) $campo->name, 'UTF-8');
            $labelLower = mb_strtolower(strip_tags((string) $campo->label), 'UTF-8');

            if (
                in_array($nameLower, ['nome', 'nome_completo', 'candidato', 'aluno', 'estudante', 'responsavel', 'participante']) ||
                str_contains($nameLower, 'nome') ||
                str_contains($labelLower, 'nome')
            ) {
                $val = $respostas[$campo->name] ?? null;
                if (!empty($val) && is_string($val) && !str_starts_with($val, 'data:image/')) {
                    return trim($val);
                }
            }
        }

        return $respostas['_email_coletado'] ?? null;
    }

    protected function extrairCpfDeRespostas($campos, array $respostas): ?string
    {
        foreach ($campos as $campo) {
            if ($campo->subtipo === 'cpf' || str_contains(strtolower((string) $campo->name), 'cpf') || str_contains(strtolower((string) $campo->label), 'cpf')) {
                $val = $respostas[$campo->name] ?? null;
                if (!empty($val) && is_string($val)) {
                    return trim($val);
                }
            }
        }
        return null;
    }

    protected function obterLogoBase64Pdf(): ?string
    {
        $possiveisLogos = [
            public_path('images/logo.png'),
            resource_path('images/logo.png'),
            resource_path('images/logo-nav-white.svg'),
        ];
        foreach ($possiveisLogos as $caminhoLogo) {
            if (file_exists($caminhoLogo)) {
                $ext = pathinfo($caminhoLogo, PATHINFO_EXTENSION);
                $mime = $ext === 'svg' ? 'image/svg+xml' : "image/{$ext}";
                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($caminhoLogo));
            }
        }
        return null;
    }

    protected function montarDadosFichaPdf(): array
    {
        $isInscricaoCiclo = property_exists($this, 'cicloAtivoId');

        if ($isInscricaoCiclo) {
            $ciclo = Ciclo::find($this->cicloAtivoId);
            $tituloDoc = $this->etapaAtual === 100
                ? 'Comprovante de Lista de Espera'
                : 'Ficha Oficial de Inscrição';
            $subtituloDoc = $ciclo ? "{$ciclo->nome} ({$ciclo->ano}.{$ciclo->semestre})" : 'Processo Seletivo';
            $protocoloId = $this->inscricaoId
                ? '#' . str_pad((string) $this->inscricaoId, 6, '0', STR_PAD_LEFT)
                : 'SIMULAÇÃO-' . strtoupper(substr(md5(uniqid()), 0, 6));
        } else {
            $tituloDoc = $this->formulario->titulo ?? 'Ficha de Respostas';
            $subtituloDoc = $this->formulario->descricao ?: 'Comprovante Oficial de Preenchimento';
            $protocoloId = $this->respostaId
                ? '#' . str_pad((string) $this->respostaId, 6, '0', STR_PAD_LEFT)
                : 'SIMULAÇÃO-' . strtoupper(substr(md5(uniqid()), 0, 6));
        }

        $nomeAssinante = $this->resolverNomePreenchedorPdf();
        $cpfAssinante  = $this->resolverCpfPreenchedorPdf();

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
                        ['label' => 'Cidade / UF', 'valor' => ($this->cidade ?: '-') . ($insc->estado ?? $this->estado ? " / {$this->estado}" : ''), 'largura' => 'full'],
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
        }

        $campos = collect($this->camposDinamicos ?? [])
            ->whereNotIn('tipo', ['config', 'html', 'divider', 'media', 'spacer', 'link'])
            ->filter(fn($c) => $this->campoVisivelNoPdf($c));

        [$itensDinamicos, $assinaturaDigitalBase64] = $this->formatarCamposDinamicosParaPdf($campos, $this->respostas ?? []);

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
            'logo_base64'         => $this->obterLogoBase64Pdf(),
            'autorizacao_lgpd'    => property_exists($this, 'autorizacao_uso_infos') ? (bool) $this->autorizacao_uso_infos : true,
        ];
    }

    protected function resolverNomePreenchedorPdf(): string
    {
        if (property_exists($this, 'nome') && !empty(trim((string) $this->nome))) {
            return trim((string) $this->nome);
        }

        if (!empty($this->camposDinamicos)) {
            $nomeExtraido = $this->extrairNomeDeRespostas($this->camposDinamicos, $this->respostas ?? []);
            if ($nomeExtraido) return $nomeExtraido;
        }

        if (auth('student')->check() && !empty(auth('student')->user()->name)) {
            return auth('student')->user()->name;
        }
        if (auth('web')->check() && !empty(auth('web')->user()->name)) {
            return auth('web')->user()->name;
        }

        return 'Participante / Candidato(a)';
    }

    protected function resolverCpfPreenchedorPdf(): ?string
    {
        if (property_exists($this, 'cpf') && !empty(trim((string) $this->cpf))) {
            return trim((string) $this->cpf);
        }

        if (!empty($this->camposDinamicos)) {
            return $this->extrairCpfDeRespostas($this->camposDinamicos, $this->respostas ?? []);
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