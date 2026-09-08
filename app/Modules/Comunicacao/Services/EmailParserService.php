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
                '[nome_candidato]' => 'Nome completo do candidato',
                '[cpf_candidato]' => 'CPF do candidato',
                '[curso_aprovado]' => 'Nome do Curso',
            ],
            'Módulo de Matrícula (IA)' => [
                '[link_matricula]' => 'Botão seguro para o Portal de Matrícula',
                '[link_retomada]' => 'Botão para o candidato concluir a Inscrição',
            ],
            'Central de Solicitações (Helpdesk)' => [
                '[nome_solicitante]' => 'Nome do usuário que solicitou a ação',
                '[justificativa]' => 'Texto de justificativa inserido pelo usuário',
                '[link_painel]' => 'Botão de acesso ao painel administrativo',
            ]
        ];
    }

    public static function parseTexto($texto, $inscricao = null, $dadosExtras = [])
    {
        if (empty($texto)) return '';

        $botaoMatricula = '';
        $botaoRetomada = '';

        if ($inscricao) {
            if (str_contains($texto, '[link_matricula]') && empty($inscricao->token_matricula)) {
                $inscricao->token_matricula = Str::random(60);
                $inscricao->save();
            }
            $linkSeguro = $inscricao->token_matricula ? route('matricula.portal', ['token' => $inscricao->token_matricula]) : '#';
            $botaoMatricula = '<a href="'.$linkSeguro.'" style="display:inline-block;background:#8b5cf6;color:#ffffff;font-family:sans-serif;font-size:14px;font-weight:bold;padding:10px 25px;border-radius:4px;text-decoration:none;">Acessar Portal de Matrícula</a>';

            $linkRetomada = route('inscricao.retomar', Crypt::encrypt($inscricao->id));
            $botaoRetomada = '<a href="'.$linkRetomada.'" style="display:inline-block;background:#8b5cf6;color:#ffffff;font-family:sans-serif;font-size:14px;font-weight:bold;padding:10px 25px;border-radius:4px;text-decoration:none;">Continuar Minha Inscrição</a>';
        }

        $linkPainel = '<a href="'.url('/solicitacoes').'" style="display:inline-block;background:#374151;color:#ffffff;font-family:sans-serif;font-size:14px;font-weight:bold;padding:10px 25px;border-radius:4px;text-decoration:none;">Acessar Painel Central</a>';

        $tags = [
            '[nome_candidato]' => $inscricao ? $inscricao->nome : 'Candidato',
            '[cpf_candidato]' => $inscricao ? $inscricao->cpf : '',
            '[curso_aprovado]' => ($inscricao && $inscricao->curso) ? $inscricao->curso->nome : 'Sem Curso Vinculado',
            '[link_matricula]' => $botaoMatricula,
            '[link_retomada]' => $botaoRetomada,
            '[nome_solicitante]' => $dadosExtras['nome_solicitante'] ?? 'Sistema',
            '[justificativa]' => $dadosExtras['justificativa'] ?? 'Não informada.',
            '[link_painel]' => $linkPainel
        ];

        return str_replace(array_keys($tags), array_values($tags), $texto);
    }
}