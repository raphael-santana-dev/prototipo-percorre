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
            'Módulo de Matrícula e Retomada' => [
                '[link_matricula]' => 'Link seguro e único para o Portal de Envio de Documentos (IA)',
                '[link_retomada]' => 'Botão com link de acesso seguro para o candidato continuar a inscrição',
            ]
        ];
    }

    public static function parseTexto($texto, $inscricao)
    {
        if (!$inscricao) return $texto;

        // 1. Processamento do Link de Matrícula (Já Existente)
        if (str_contains($texto, '[link_matricula]') && empty($inscricao->token_matricula)) {
            $inscricao->token_matricula = Str::random(60);
            $inscricao->save();
        }
        $linkSeguroMatricula = $inscricao->token_matricula ? route('matricula.portal', ['token' => $inscricao->token_matricula]) : '#';
        $botaoMatricula = '<a href="'.$linkSeguroMatricula.'" style="display:inline-block;background:#8b5cf6;color:#ffffff;font-family:sans-serif;font-size:14px;font-weight:bold;padding:10px 25px;border-radius:4px;text-decoration:none;">Acessar Portal de Matrícula</a>';

        // 2. Processamento do Link de Retomada (Novo)
        $linkRetomada = route('inscricao.retomar', Crypt::encrypt($inscricao->id));
        $botaoRetomada = '<a href="'.$linkRetomada.'" style="display:inline-block;background:#8b5cf6;color:#ffffff;font-family:sans-serif;font-size:14px;font-weight:bold;padding:10px 25px;border-radius:4px;text-decoration:none;">Continuar Minha Inscrição</a>';

        // 3. Substituição em Massa
        $tags = [
            '[nome_candidato]' => $inscricao->nome,
            '[cpf_candidato]' => $inscricao->cpf,
            '[curso_aprovado]' => $inscricao->curso->nome ?? 'Sem Curso Vinculado',
            '[link_matricula]' => $botaoMatricula,
            '[link_retomada]'  => $botaoRetomada
        ];

        return str_replace(array_keys($tags), array_values($tags), $texto);
    }
}