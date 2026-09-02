<?php

namespace App\Modules\Comunicacao\Services;

use Illuminate\Support\Str;

class EmailParserService
{
    public static function getDicionarioDisponivel()
    {
        // Agora as variáveis estão agrupadas por Categoria para o menu sanfona da tela não quebrar
        return [
            'Dados do Candidato' => [
                '[nome_candidato]' => 'Nome completo do candidato',
                '[cpf_candidato]' => 'CPF do candidato',
                '[curso_aprovado]' => 'Nome do Curso',
            ],
            'Módulo de Matrícula (IA)' => [
                '[link_matricula]' => 'Link seguro e único para o Portal de Envio de Documentos (IA)',
            ]
        ];
    }

    public static function parseTexto($texto, $inscricao)
    {
        if (!$inscricao) return $texto;

        if (str_contains($texto, '[link_matricula]') && empty($inscricao->token_matricula)) {
            $inscricao->token_matricula = Str::random(60);
            $inscricao->save();
        }

        $linkSeguro = $inscricao->token_matricula ? route('matricula.portal', ['token' => $inscricao->token_matricula]) : '#';
        $botaoHtml = '<a href="'.$linkSeguro.'" style="display:inline-block;background:#8b5cf6;color:#ffffff;font-family:sans-serif;font-size:14px;font-weight:bold;line-height:120%;margin:0;text-decoration:none;text-transform:none;padding:10px 25px;border-radius:4px;">Acessar Portal de Matrícula</a>';

        $tags = [
            '[nome_candidato]' => $inscricao->nome,
            '[cpf_candidato]' => $inscricao->cpf,
            '[curso_aprovado]' => $inscricao->curso->nome ?? 'Sem Curso Vinculado',
            '[link_matricula]' => $botaoHtml
        ];

        return str_replace(array_keys($tags), array_values($tags), $texto);
    }
}