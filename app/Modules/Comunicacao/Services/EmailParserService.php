<?php

namespace App\Modules\Comunicacao\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;

class EmailParserService
{
    public static function getDicionarioDisponivel()
    {
        $dicionario = [
            'Dados do Candidato' => [
                '{{nome}}'          => 'Nome completo do candidato',
                '{{primeiro_nome}}' => 'Primeiro nome do candidato (Ex: João)',
                '{{cpf}}'           => 'CPF do candidato',
            ],
            'Dados Acadêmicos' => [
                '{{curso}}'    => 'Nome do Curso',
                '{{unidade}}'  => 'Nome da Unidade/Sede',
                '{{turno}}'    => 'Turno do curso',
                '{{modelo}}'   => 'Modelo de ensino (Presencial/EAD)',
                '{{endereco}}' => 'Endereço da Unidade',
            ],
            'Módulo de Matrícula e Links' => [
                '{{link_matricula}}'   => 'Botão/Link seguro para o Portal de Matrícula',
                '{{link_retomada}}'    => 'Botão/Link para o candidato concluir a Inscrição',
                '{{link_assinatura}}'  => 'Botão/Link direto para a assinatura do contrato',
                '{{link_login}}'       => 'Botão/Link para a tela de login do Portal do Aluno',
                '{{senha_provisoria}}' => 'Senha de acesso gerada automaticamente',
            ],
            'Central de Solicitações (Helpdesk)' => [
                '{{nome_solicitante}}' => 'Nome do usuário que solicitou a ação',
                '{{justificativa}}'    => 'Texto de justificativa inserido pelo usuário',
                '{{link_painel}}'      => 'Botão/Link de acesso ao painel administrativo',
            ],
        ];

        if (class_exists(\App\Models\Formulario::class)) {
            $formularios = \App\Models\Formulario::where('status', true)
                ->whereNotNull('slug')
                ->orderBy('titulo')
                ->get(['id', 'titulo', 'slug']);

            foreach ($formularios as $form) {
                $dicionario['Links de Formulários Cadastrados']['{{link_formulario_' . $form->id . '}}'] = "Link: {$form->titulo} (/f/{$form->slug})";
            }
        }

        return $dicionario;
    }

    /**
     * Lista todas as colunas do banco de dados disponíveis para substituir
     * variáveis de templates no momento do disparo (E-mail e WhatsApp Suri).
     */
    public static function getColunasBancoDisponiveis(): array
    {
        $colunas = [
            'Tabela de Inscrições (Colunas Diretas)' => [
                'nome'                  => 'inscricoes.nome (Nome Completo)',
                'primeiro_nome'         => 'inscricoes.nome (Apenas 1º Nome)',
                'nome_social'           => 'inscricoes.nome_social (Nome Social)',
                'cpf'                   => 'inscricoes.cpf (CPF)',
                'email'                 => 'inscricoes.email (E-mail)',
                'celular'               => 'inscricoes.celular (Celular / Telefone)',
                'data_nascimento'       => 'inscricoes.data_nascimento (Data de Nascimento)',
                'cep'                   => 'inscricoes.cep (CEP)',
                'logradouro'            => 'inscricoes.logradouro (Endereço / Rua)',
                'numero'                => 'inscricoes.numero (Número)',
                'complemento'           => 'inscricoes.complemento (Complemento)',
                'bairro'                => 'inscricoes.bairro (Bairro)',
                'cidade'                => 'inscricoes.cidade (Cidade)',
                'estado'                => 'inscricoes.estado (UF / Estado)',
                'regiao'                => 'inscricoes.regiao (Região)',
                'pontuacao_total'       => 'inscricoes.pontuacao_total (Pontuação Total)',
                'posicao_ranking_geral' => 'inscricoes.posicao_ranking_geral (Posição no Ranking)',
                'etapa_atual'           => 'inscricoes.etapa_atual (Etapa Atual)',
            ],
            'Relacionamentos Acadêmicos (Tabelas Vinculadas)' => [
                'curso'            => 'cursos.nome (Nome do Curso)',
                'unidade'          => 'unidades.nome (Nome da Unidade)',
                'endereco'         => 'unidades.endereco (Endereço da Unidade)',
                'turno'            => 'turnos.nome (Nome do Turno)',
                'ciclo'            => 'ciclos.nome (Nome do Ciclo / Semestre)',
                'status_inscricao' => 'status_inscricoes.nome (Status da Inscrição)',
                'modelo'           => 'dados_dinamicos.modelo (Modelo Presencial/EAD)',
            ],
            'Links e Acessos do Sistema' => [
                'link_matricula_url' => 'URL Direta do Portal de Matrícula (Texto p/ WhatsApp)',
                'link_retomada_url'  => 'URL Direta de Retomada da Inscrição (Texto p/ WhatsApp)',
                'link_assinatura'    => 'URL Direta de Assinatura de Contrato',
                'link_login'         => 'URL de Login do Portal do Aluno',
                'link_painel'        => 'URL da Central de Solicitações (/solicitacoes)',
                'senha_provisoria'   => 'Senha Provisória Gerada (Student)',
            ],
        ];

        if (class_exists(\App\Models\Formulario::class)) {
            $formularios = \App\Models\Formulario::where('status', true)
                ->whereNotNull('slug')
                ->orderBy('titulo')
                ->get(['id', 'titulo', 'slug', 'tipo']);

            foreach ($formularios as $form) {
                $tipoFmt = strtoupper((string) ($form->tipo ?: 'GERAL'));
                $colunas['Links de Formulários Cadastrados']['formulario_url:' . $form->id] = "[{$tipoFmt}] {$form->titulo} (/f/{$form->slug})";
            }
        }

        if (class_exists(\App\Models\Ciclo::class)) {
            $ciclos = \App\Models\Ciclo::where('status', true)
                ->orderByDesc('id')
                ->get(['id', 'nome', 'tipo_ciclo', 'slug_projeto', 'slug']);

            foreach ($ciclos as $ciclo) {
                $isCustom = ($ciclo->tipo_ciclo ?? 'regular') === 'customizado';
                $slugCiclo = $ciclo->slug_projeto ?: $ciclo->slug;
                $caminhoPreview = ($isCustom && !empty($slugCiclo)) ? "/inscricao/{$slugCiclo}" : '/inscricao';
                $colunas['Links de Formulários Cadastrados']['ciclo_url:' . $ciclo->id] = "[INSCRIÇÃO] {$ciclo->nome} ({$caminhoPreview})";
            }
        }

        if (class_exists(\App\Models\CampoFormulario::class)) {
            $camposDin = \App\Models\CampoFormulario::whereNotIn('tipo', ['config', 'html', 'divider', 'media', 'spacer', 'link'])
                ->select('name', 'label')
                ->distinct()
                ->orderBy('name')
                ->limit(100)
                ->get();

            if ($camposDin->isNotEmpty()) {
                foreach ($camposDin as $c) {
                    $labelLimpo = trim(strip_tags((string) $c->label)) ?: $c->name;
                    $colunas['Campos Dinâmicos do Formulário (dados_dinamicos)']['dinamico:' . $c->name] = "dados_dinamicos.{$c->name} ({$labelLimpo})";
                }
            }
        }

        return $colunas;
    }

    /**
     * Extrai na ordem exata todas as variáveis presentes no corpo do template (ex: {{1}}, {{nome}}, [nome]).
     */
    public static function extrairVariaveisDoCorpo(?string $corpo): array
    {
        if (empty($corpo)) return [];

        $textoLimpo = str_replace("\xE2\x80\x8B", "", $corpo);
        $clean = strip_tags(html_entity_decode($textoLimpo, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        preg_match_all('/(?:\{\{|\[)\s*([a-zA-Z0-9_]+)\s*(?:\}\}|\])/u', $clean, $matches);

        $variaveis = [];
        foreach ($matches[1] ?? [] as $v) {
            if (!in_array($v, $variaveis, true)) {
                $variaveis[] = $v;
            }
        }

        return $variaveis;
    }

    /**
     * Busca o valor em texto puro (ou URL pura) correspondente à coluna/variável para envio em parâmetros da Suri (WhatsApp).
     */
    public static function resolverValorColuna(string $coluna, $inscricao = null, array $dadosExtras = []): string
    {
        $coluna = trim($coluna, "{}[] \t\n\r\0\x0B");

        if (str_starts_with($coluna, 'dinamico:')) {
            $chaveDin = substr($coluna, 9);
            $dinamicos = $inscricao
                ? (is_array($inscricao->dados_dinamicos) ? $inscricao->dados_dinamicos : (json_decode($inscricao->dados_dinamicos ?? '{}', true) ?: []))
                : [];
            $val = $dinamicos[$chaveDin] ?? $dadosExtras[$chaveDin] ?? '';
            return is_array($val) ? implode(', ', $val) : (string) $val;
        }

        // Resolução de Links de Formulários Cadastrados (selecionados no mapeamento da Automação/Esteira ou via tag {{link_formulario_ID}})
        if (str_starts_with($coluna, 'formulario_url:') || preg_match('/^link_formulario_(\d+)$/i', $coluna)) {
            $formId = str_starts_with($coluna, 'formulario_url:')
                ? (int) substr($coluna, 15)
                : (int) preg_replace('/\D/', '', $coluna);

            $form = class_exists(\App\Models\Formulario::class) ? \App\Models\Formulario::find($formId) : null;
            if ($form && !empty($form->slug)) {
                return url('/f/' . $form->slug);
            }
            return url('/');
        }

        // Resolução de Links de Ciclos de Inscrição Cadastrados (ciclo_url:ID ou {{link_ciclo_ID}})
        if (str_starts_with($coluna, 'ciclo_url:') || preg_match('/^link_ciclo_(\d+)$/i', $coluna)) {
            $cicloId = str_starts_with($coluna, 'ciclo_url:')
                ? (int) substr($coluna, 10)
                : (int) preg_replace('/\D/', '', $coluna);

            $ciclo = class_exists(\App\Models\Ciclo::class) ? \App\Models\Ciclo::find($cicloId) : null;
            if ($ciclo) {
                $isCustom = ($ciclo->tipo_ciclo ?? 'regular') === 'customizado';
                $slugCiclo = $ciclo->slug_projeto ?: $ciclo->slug;
                return ($isCustom && !empty($slugCiclo))
                    ? url('/inscricao/' . $slugCiclo)
                    : url('/inscricao');
            }
            return url('/inscricao');
        }

        // Tratamento direto de variáveis de Link para retornar SEMPRE a URL pura (e não o texto do botão HTML)
        switch ($coluna) {
            case 'link_painel':
                return (string) ($dadosExtras['link_painel'] ?? url('/solicitacoes'));

            case 'link_login':
                return (string) ($dadosExtras['link_login'] ?? url('/aluno/login'));

            case 'link_matricula':
            case 'link_matricula_url':
                if ($inscricao) {
                    if (empty($inscricao->token_matricula)) {
                        $inscricao->token_matricula = Str::random(60);
                        $inscricao->save();
                    }
                    return route('matricula.portal', ['token' => $inscricao->token_matricula]);
                }
                return url('/matricula');

            case 'link_assinatura':
                if ($inscricao) {
                    if (empty($inscricao->token_matricula)) {
                        $inscricao->token_matricula = Str::random(60);
                        $inscricao->save();
                    }
                    return route('matricula.portal', ['token' => $inscricao->token_matricula]) . '#assinatura';
                }
                return url('/matricula#assinatura');

            case 'link_retomada':
            case 'link_retomada_url':
                if ($inscricao) {
                    return route('inscricao.retomar', Crypt::encrypt($inscricao->id));
                }
                return url('/inscricao');
        }

        if ($inscricao) {
            switch ($coluna) {
                case 'ciclo':
                    return (string) ($inscricao->ciclo->nome ?? '');
                case 'status_inscricao':
                    return (string) ($inscricao->statusInscricao->nome ?? '');
                case 'data_nascimento':
                    return $inscricao->data_nascimento ? \Carbon\Carbon::parse($inscricao->data_nascimento)->format('d/m/Y') : '';
            }
        }

        $parsed = self::parseTextoInterno('{{' . $coluna . '}}', $inscricao, $dadosExtras, false);

        // Se o resultado contiver uma tag <a href="...">, extrai o href para não perder o link no WhatsApp
        if (preg_match('/<a\s+[^>]*href=["\']([^"\']+)["\']/i', $parsed, $mHref)) {
            return html_entity_decode($mHref[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $limpo = trim(strip_tags($parsed));

        if ($limpo !== '' && $limpo !== '{{' . $coluna . '}}') {
            return $limpo;
        }

        if ($inscricao) {
            if (isset($inscricao->{$coluna}) && !is_array($inscricao->{$coluna}) && !is_object($inscricao->{$coluna})) {
                return (string) $inscricao->{$coluna};
            }
            $dinamicos = is_array($inscricao->dados_dinamicos)
                ? $inscricao->dados_dinamicos
                : (json_decode($inscricao->dados_dinamicos ?? '{}', true) ?: []);
            if (isset($dinamicos[$coluna])) {
                return is_array($dinamicos[$coluna]) ? implode(', ', $dinamicos[$coluna]) : (string) $dinamicos[$coluna];
            }
        }

        return (string) ($dadosExtras[$coluna] ?? '');
    }

    /**
     * Monta o array sequencial bodyParameters (ex: ["João", "Administração"]) para o envio na Suri.
     */
    public static function montarBodyParametersSuri($template, $inscricao = null, array $dadosExtras = [], array $mapaSobrescrito = []): array
    {
        $cfgSuri = is_array($template->configuracoes_suri)
            ? $template->configuracoes_suri
            : (json_decode($template->configuracoes_suri ?? '{}', true) ?: []);

        $mapaTemplate = is_array($cfgSuri['variable_map'] ?? null) ? $cfgSuri['variable_map'] : [];
        $exemplos     = is_array($cfgSuri['body_examples'] ?? null) ? $cfgSuri['body_examples'] : [];

        $variaveis = self::extrairVariaveisDoCorpo($template->corpo ?? '');

        if (empty($variaveis)) {
            if (!empty($mapaSobrescrito)) {
                $variaveis = array_keys($mapaSobrescrito);
            } elseif (!empty($mapaTemplate)) {
                $variaveis = array_keys($mapaTemplate);
            } elseif (!empty($exemplos)) {
                $variaveis = array_keys($exemplos);
            }
        }

        if (empty($variaveis)) {
            return [];
        }

        $bodyParameters = [];

        foreach ($variaveis as $varKey) {
            $colunaAlvo = $mapaSobrescrito[$varKey]
                ?? $mapaTemplate[$varKey]
                ?? (is_numeric($varKey) ? 'nome' : $varKey);

            $valorResolvido = trim(self::resolverValorColuna((string) $colunaAlvo, $inscricao, $dadosExtras));

            if ($valorResolvido === '') {
                $valorResolvido = (string) ($exemplos[$varKey] ?? ($inscricao->nome ?? 'Candidato'));
            }

            $bodyParameters[] = $valorResolvido;
        }

        return array_values($bodyParameters);
    }

    public static function parseTexto($texto, $inscricao = null, $dadosExtras = [], $template = null)
    {
        if (empty($texto)) return '';

        $mapa = is_array($dadosExtras['variaveis_mapeadas'] ?? null) ? $dadosExtras['variaveis_mapeadas'] : [];
        if ($template) {
            $cfgSuri = is_array($template->configuracoes_suri)
                ? $template->configuracoes_suri
                : (json_decode($template->configuracoes_suri ?? '{}', true) ?: []);
            $mapa = array_merge($cfgSuri['variable_map'] ?? [], $mapa);
        }

        foreach ($mapa as $varKey => $colunaBanco) {
            $dadosExtras[strtolower((string) $varKey)] = self::resolverValorColuna((string) $colunaBanco, $inscricao, $dadosExtras);
        }

        return self::parseTextoInterno($texto, $inscricao, $dadosExtras, true);
    }

    private static function parseTextoInterno($texto, $inscricao = null, $dadosExtras = [], bool $autoLink = true)
    {
        if (empty($texto)) return '';

        $texto = str_replace("\xE2\x80\x8B", "", $texto);
        $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $botaoMatricula  = '';
        $botaoRetomada   = '';
        $urlAssinatura   = '#';
        $botaoAssinatura = '';

        if ($inscricao) {
            if ((str_contains($texto, 'link_matricula') || str_contains($texto, 'link_assinatura')) && empty($inscricao->token_matricula)) {
                $inscricao->token_matricula = Str::random(60);
                $inscricao->save();
            }

            $linkSeguro    = $inscricao->token_matricula ? route('matricula.portal', ['token' => $inscricao->token_matricula]) : '#';
            $urlAssinatura = $linkSeguro . '#assinatura';

            $botaoMatricula  = '<a href="' . $linkSeguro . '" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:#8b5cf6;color:#ffffff;font-family:sans-serif;font-size:14px;font-weight:bold;padding:10px 25px;border-radius:6px;text-decoration:none;">Acessar Portal de Matrícula</a>';
            $botaoAssinatura = '<a href="' . $urlAssinatura . '" target="_blank" rel="noopener noreferrer" style="color:#7c3aed;font-weight:bold;text-decoration:underline;word-break:break-all;">' . $urlAssinatura . '</a>';

            $linkRetomadaUrl = route('inscricao.retomar', Crypt::encrypt($inscricao->id));
            $botaoRetomada   = '<a href="' . $linkRetomadaUrl . '" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:#8b5cf6;color:#ffffff;font-family:sans-serif;font-size:14px;font-weight:bold;padding:10px 25px;border-radius:6px;text-decoration:none;">Continuar Minha Inscrição</a>';
        }

        $urlPainel  = $dadosExtras['link_painel'] ?? url('/solicitacoes');
        $linkPainel = '<a href="' . $urlPainel . '" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:#374151;color:#ffffff;font-family:sans-serif;font-size:14px;font-weight:bold;padding:10px 25px;border-radius:6px;text-decoration:none;">Acessar Painel Central</a>';

        $urlLogin  = $dadosExtras['link_login'] ?? url('/aluno/login');
        $linkLogin = '<a href="' . $urlLogin . '" target="_blank" rel="noopener noreferrer" style="color:#7c3aed;font-weight:bold;text-decoration:underline;word-break:break-all;">' . $urlLogin . '</a>';

        $modeloEnsino = $inscricao->dados_dinamicos['modelo'] ?? 'Presencial';

        $enderecoUnidade = 'Endereço não cadastrado';
        if ($inscricao && $inscricao->unidade) {
            $rua = $inscricao->unidade->logradouro ?? $inscricao->unidade->endereco ?? '';
            $num = $inscricao->unidade->numero ?? 'S/N';
            if ($rua) $enderecoUnidade = "{$rua}, {$num}";
        }

        $nomeCompleto = $inscricao ? trim($inscricao->nome) : ($dadosExtras['nome'] ?? 'Candidato');
        $partesNome   = explode(' ', $nomeCompleto);
        $primeiroNome = $partesNome[0] ?? 'Candidato';

        $tags = [
            'nome'             => $nomeCompleto,
            'primeiro_nome'    => $primeiroNome,
            'cpf'              => $inscricao ? $inscricao->cpf : '',
            'curso'            => ($inscricao && $inscricao->curso) ? $inscricao->curso->nome : 'Sem Curso Vinculado',
            'unidade'          => ($inscricao && $inscricao->unidade) ? $inscricao->unidade->nome : 'Unidade não definida',
            'turno'            => ($inscricao && $inscricao->turno) ? $inscricao->turno->nome : 'Turno não definido',
            'modelo'           => $modeloEnsino,
            'endereco'         => $enderecoUnidade,
            'link_matricula'   => $botaoMatricula,
            'link_retomada'    => $botaoRetomada,
            'link_assinatura'  => $autoLink ? $botaoAssinatura : $urlAssinatura,
            'nome_solicitante' => $dadosExtras['nome_solicitante'] ?? 'Sistema',
            'justificativa'    => $dadosExtras['justificativa'] ?? 'Não informada.',
            'link_painel'      => $autoLink ? $linkPainel : $urlPainel,
            'link_login'       => $autoLink ? $linkLogin : $urlLogin,
            'senha_provisoria' => $dadosExtras['senha_provisoria'] ?? 'Senha não gerada.',
            'nome_candidato'   => $nomeCompleto,
            'cpf_candidato'    => $inscricao ? $inscricao->cpf : '',
            'curso_aprovado'   => ($inscricao && $inscricao->curso) ? $inscricao->curso->nome : 'Sem Curso Vinculado',
        ];

        if (is_array($dadosExtras)) {
            foreach ($dadosExtras as $chave => $valor) {
                if (!is_array($valor) && !is_object($valor)) {
                    $strVal = (string) $valor;
                    // Se o valor extra já for uma URL pura e estivermos gerando HTML de e-mail, torna-o clicável
                    if ($autoLink && preg_match('/^https?:\/\/[^\s<>"\']+$/i', trim($strVal))) {
                        $urlTrim = trim($strVal);
                        $tags[strtolower((string) $chave)] = '<a href="' . $urlTrim . '" target="_blank" rel="noopener noreferrer" style="color:#7c3aed;font-weight:bold;text-decoration:underline;word-break:break-all;">' . $urlTrim . '</a>';
                    } else {
                        $tags[strtolower((string) $chave)] = $valor;
                    }
                }
            }
        }

        // Caso uma variável de link já tenha sido colocada dentro de um atributo href="{{link_...}}" no Quill,
        // substitui apenas pela URL pura para não gerar <a> dentro de <a>
        $texto = preg_replace_callback('/href=(["\'])\s*(?:\{\{|\[)\s*(link_[a-zA-Z0-9_]+)\s*(?:\}\}|\])\s*\1/i', function ($m) use ($inscricao, $dadosExtras) {
            $urlPura = self::resolverValorColuna($m[2], $inscricao, $dadosExtras);
            return 'href=' . $m[1] . $urlPura . $m[1];
        }, $texto);

        $resultado = preg_replace_callback('/(?:\{\{|\[)\s*([a-zA-Z0-9_]+)\s*(?:\}\}|\])/u', function ($matches) use ($tags, $inscricao, $dadosExtras, $autoLink) {
            $key = strtolower(trim($matches[1]));
            if (array_key_exists($key, $tags)) {
                return $tags[$key];
            }
            if (preg_match('/^link_(formulario|ciclo)_(\d+)$/i', $key)) {
                $urlForm = self::resolverValorColuna($key, $inscricao, $dadosExtras);
                return $autoLink
                    ? '<a href="' . $urlForm . '" target="_blank" rel="noopener noreferrer" style="color:#7c3aed;font-weight:bold;text-decoration:underline;word-break:break-all;">' . $urlForm . '</a>'
                    : $urlForm;
            }
            return $matches[0];
        }, $texto);

        return $autoLink ? self::autoLinkUrlsEmHtml($resultado) : $resultado;
    }

    /**
     * Converte automaticamente qualquer URL em texto puro (http:// ou https://) em tag <a href="..."> clicável,
     * preservando intactas as tags <a> e atributos HTML (src="", href="") que já existirem no corpo.
     */
    public static function autoLinkUrlsEmHtml(string $html): string
    {
        if (empty($html) || (!str_contains($html, 'http://') && !str_contains($html, 'https://'))) {
            return $html;
        }

        // Isola tags <a ...>...</a> inteiras e qualquer outra tag HTML (<...>) para não alterar URLs que já estão em atributos
        $partes = preg_split('/(<a\b[^>]*>.*?<\/a>|<[^>]+>)/is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($partes === false) {
            return $html;
        }

        foreach ($partes as $i => $trecho) {
            if ($trecho === '' || str_starts_with($trecho, '<')) {
                continue;
            }

            $partes[$i] = preg_replace_callback(
                '/\b(https?:\/\/[^\s<>"\']+)/i',
                function ($m) {
                    $url = $m[1];
                    $pontuacaoFinal = '';

                    // Evita capturar ponto final, vírgula ou parêntese de fechamento no fim de uma frase
                    while (preg_match('/[.,;:!?)\]]$/', $url, $p)) {
                        $pontuacaoFinal = $p[0] . $pontuacaoFinal;
                        $url = substr($url, 0, -1);
                    }

                    return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" style="color:#7c3aed;font-weight:bold;text-decoration:underline;word-break:break-all;">' . $url . '</a>' . $pontuacaoFinal;
                },
                $trecho
            );
        }

        return implode('', $partes);
    }
}