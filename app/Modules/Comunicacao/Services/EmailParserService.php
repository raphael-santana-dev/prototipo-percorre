<?php

namespace App\Modules\Comunicacao\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;

class EmailParserService
{
    public static function getDicionarioDisponivel()
    {
        return [
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
                '{{link_matricula}}'   => 'Botão seguro para o Portal de Matrícula',
                '{{link_retomada}}'    => 'Botão para o candidato concluir a Inscrição',
                '{{link_assinatura}}'  => 'Link direto para a assinatura do contrato',
                '{{link_login}}'       => 'Link para a tela de login do Portal do Aluno',
                '{{senha_provisoria}}' => 'Senha de acesso gerada automaticamente',
            ],
            'Central de Solicitações (Helpdesk)' => [
                '{{nome_solicitante}}' => 'Nome do usuário que solicitou a ação',
                '{{justificativa}}'    => 'Texto de justificativa inserido pelo usuário',
                '{{link_painel}}'      => 'Botão de acesso ao painel administrativo',
            ]
        ];
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
                'senha_provisoria'   => 'Senha Provisória Gerada (Student)',
            ],
        ];

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
     * Extrai na ordem exata todas as variáveis presentes no corpo do template (ex: {{1}}, {{nome}}).
     */
    public static function extrairVariaveisDoCorpo(?string $corpo): array
    {
        if (empty($corpo)) return [];

        $clean = strip_tags(html_entity_decode($corpo, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/u', $clean, $matches);

        $variaveis = [];
        foreach ($matches[1] ?? [] as $v) {
            if (!in_array($v, $variaveis, true)) {
                $variaveis[] = $v;
            }
        }

        return $variaveis;
    }

    /**
     * Busca o valor correspondente à coluna do banco escolhida para preencher a variável.
     */
    public static function resolverValorColuna(string $coluna, $inscricao = null, array $dadosExtras = []): string
    {
        $coluna = trim($coluna, "{} \t\n\r\0\x0B");

        if (str_starts_with($coluna, 'dinamico:')) {
            $chaveDin = substr($coluna, 9);
            $dinamicos = $inscricao
                ? (is_array($inscricao->dados_dinamicos) ? $inscricao->dados_dinamicos : (json_decode($inscricao->dados_dinamicos ?? '{}', true) ?: []))
                : [];
            $val = $dinamicos[$chaveDin] ?? $dadosExtras[$chaveDin] ?? '';
            return is_array($val) ? implode(', ', $val) : (string) $val;
        }

        if ($inscricao) {
            switch ($coluna) {
                case 'ciclo':
                    return (string) ($inscricao->ciclo->nome ?? '');
                case 'status_inscricao':
                    return (string) ($inscricao->statusInscricao->nome ?? '');
                case 'data_nascimento':
                    return $inscricao->data_nascimento ? \Carbon\Carbon::parse($inscricao->data_nascimento)->format('d/m/Y') : '';
                case 'link_matricula_url':
                    if (empty($inscricao->token_matricula)) {
                        $inscricao->token_matricula = Str::random(60);
                        $inscricao->save();
                    }
                    return route('matricula.portal', ['token' => $inscricao->token_matricula]);
                case 'link_retomada_url':
                    return route('inscricao.retomar', Crypt::encrypt($inscricao->id));
            }
        }

        $parsed = self::parseTexto('{{' . $coluna . '}}', $inscricao, $dadosExtras);
        $limpo  = trim(strip_tags($parsed));

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
        $variaveis = self::extrairVariaveisDoCorpo($template->corpo ?? '');
        if (empty($variaveis)) {
            return [];
        }

        $cfgSuri = is_array($template->configuracoes_suri)
            ? $template->configuracoes_suri
            : (json_decode($template->configuracoes_suri ?? '{}', true) ?: []);

        $mapaTemplate = is_array($cfgSuri['variable_map'] ?? null) ? $cfgSuri['variable_map'] : [];
        $exemplos     = is_array($cfgSuri['body_examples'] ?? null) ? $cfgSuri['body_examples'] : [];

        $bodyParameters = [];

        foreach ($variaveis as $varKey) {
            $colunaAlvo = $mapaSobrescrito[$varKey]
                ?? $mapaTemplate[$varKey]
                ?? (is_numeric($varKey) ? 'nome' : $varKey);

            $valorResolvido = trim(self::resolverValorColuna($colunaAlvo, $inscricao, $dadosExtras));

            if ($valorResolvido === '') {
                $valorResolvido = $exemplos[$varKey] ?? 'Candidato';
            }

            $bodyParameters[] = $valorResolvido;
        }

        return $bodyParameters;
    }

    public static function parseTexto($texto, $inscricao = null, $dadosExtras = [])
    {
        if (empty($texto)) return '';

        $texto = str_replace("\xE2\x80\x8B", "", $texto);
        $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $botaoMatricula = '';
        $botaoRetomada = '';
        $linkAssinatura = '#';

        if ($inscricao) {
            if ((str_contains($texto, 'link_matricula') || str_contains($texto, 'link_assinatura')) && empty($inscricao->token_matricula)) {
                $inscricao->token_matricula = Str::random(60);
                $inscricao->save();
            }

            $linkSeguro = $inscricao->token_matricula ? route('matricula.portal', ['token' => $inscricao->token_matricula]) : '#';
            $linkAssinatura = $linkSeguro . '#assinatura'; 
            
            $botaoMatricula = '<a href="'.$linkSeguro.'" style="display:inline-block;background:#8b5cf6;color:#ffffff;font-family:sans-serif;font-size:14px;font-weight:bold;padding:10px 25px;border-radius:4px;text-decoration:none;">Acessar Portal de Matrícula</a>';

            $linkRetomada_url = route('inscricao.retomar', Crypt::encrypt($inscricao->id));
            $botaoRetomada = '<a href="'.$linkRetomada_url.'" style="display:inline-block;background:#8b5cf6;color:#ffffff;font-family:sans-serif;font-size:14px;font-weight:bold;padding:10px 25px;border-radius:4px;text-decoration:none;">Continuar Minha Inscrição</a>';
        }

        $linkPainel = '<a href="'.url('/solicitacoes').'" style="display:inline-block;background:#374151;color:#ffffff;font-family:sans-serif;font-size:14px;font-weight:bold;padding:10px 25px;border-radius:4px;text-decoration:none;">Acessar Painel Central</a>';

        $modeloEnsino = $inscricao->dados_dinamicos['modelo'] ?? 'Presencial'; 
        
        $enderecoUnidade = 'Endereço não cadastrado';
        if ($inscricao && $inscricao->unidade) {
            $rua = $inscricao->unidade->logradouro ?? $inscricao->unidade->endereco ?? '';
            $num = $inscricao->unidade->numero ?? 'S/N';
            if ($rua) $enderecoUnidade = "{$rua}, {$num}";
        }

        $nomeCompleto = $inscricao ? trim($inscricao->nome) : ($dadosExtras['nome'] ?? 'Candidato');
        $partesNome = explode(' ', $nomeCompleto);
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
            'link_assinatura'  => $linkAssinatura,
            'nome_solicitante' => $dadosExtras['nome_solicitante'] ?? 'Sistema',
            'justificativa'    => $dadosExtras['justificativa'] ?? 'Não informada.',
            'link_painel'      => $linkPainel,
            'link_login'       => $dadosExtras['link_login'] ?? url('/aluno/login'),
            'senha_provisoria' => $dadosExtras['senha_provisoria'] ?? 'Senha não gerada.',
            'nome_candidato'   => $nomeCompleto,
            'cpf_candidato'    => $inscricao ? $inscricao->cpf : '',
            'curso_aprovado'   => ($inscricao && $inscricao->curso) ? $inscricao->curso->nome : 'Sem Curso Vinculado'
        ];

        if (is_array($dadosExtras)) {
            foreach ($dadosExtras as $chave => $valor) {
                if (!is_array($valor) && !is_object($valor)) {
                    $tags[strtolower($chave)] = $valor;
                }
            }
        }

        return preg_replace_callback('/(?:\{\{|\[)\s*([a-zA-Z0-9_]+)\s*(?:\}\}|\])/u', function($matches) use ($tags) {
            $key = strtolower(trim($matches[1]));
            return $tags[$key] ?? $matches[0];
        }, $texto);
    }
}