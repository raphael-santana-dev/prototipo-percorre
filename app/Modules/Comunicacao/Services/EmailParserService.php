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
                '{{link_matricula}}'  => 'Botão seguro para o Portal de Matrícula',
                '{{link_retomada}}'   => 'Botão para o candidato concluir a Inscrição',
                '{{link_assinatura}}' => 'Link direto para a assinatura do contrato',
                '{{link_login}}'      => 'Link para a tela de login do Portal do Aluno',
                '{{senha_provisoria}}'=> 'Senha de acesso gerada automaticamente',
            ],
            'Central de Solicitações (Helpdesk)' => [
                '{{nome_solicitante}}' => 'Nome do usuário que solicitou a ação',
                '{{justificativa}}'    => 'Texto de justificativa inserido pelo usuário',
                '{{link_painel}}'      => 'Botão de acesso ao painel administrativo',
            ]
        ];
    }

    public static function parseTexto($texto, $inscricao = null, $dadosExtras = [])
    {
        if (empty($texto)) return '';

        // 1. Limpa caracteres invisíveis (zero-width spaces) que atrapalham a leitura
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

        // Geração das variáveis dinâmicas de nome
        $nomeCompleto = $inscricao ? trim($inscricao->nome) : 'Candidato';
        $partesNome = explode(' ', $nomeCompleto);
        $primeiroNome = $partesNome[0] ?? 'Candidato';

        // ARRAY SEM AS CHAVES: Facilita a busca limpa da regex
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
            
            // Retrocompatibilidade para quem ainda usa []
            'nome_candidato'   => $nomeCompleto,
            'cpf_candidato'    => $inscricao ? $inscricao->cpf : '',
            'curso_aprovado'   => ($inscricao && $inscricao->curso) ? $inscricao->curso->nome : 'Sem Curso Vinculado'
        ];

        // 2. REGEX INTELIGENTE: Encontra {{ tag }} ou [ tag ], ignora espaços extras por dentro, e busca direto no array $tags
        return preg_replace_callback('/(?:\{\{|\[)\s*([a-zA-Z0-9_]+)\s*(?:\}\}|\])/u', function($matches) use ($tags) {
            $key = strtolower(trim($matches[1]));
            return $tags[$key] ?? $matches[0]; // Se a tag não existir na lista, mantém o texto original sem quebrar
        }, $texto);
    }
}