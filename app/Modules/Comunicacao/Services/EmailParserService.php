<?php

namespace App\Modules\Comunicacao\Services;

use App\Models\User;
use App\Models\Inscricao;

class EmailParserService
{
    /**
     * TUTORIAL DE MANUTENÇÃO (Para Futuros Desenvolvedores)
     * =========================================================================
     * Como adicionar novas variáveis disponíveis para os e-mails?
     * 
     * PASSO 1: Registre a variável visualmente no método `getDicionarioDisponivel()`.
     *          Isso fará com que ela apareça na "Legenda" da tela de criação de templates.
     *          Siga o padrão: '{{chave}}' => 'Descrição do que ela faz'.
     * 
     * PASSO 2: Adicione a lógica de substituição no método `extrairValores()`.
     *          Se você adicionou `{{estudante.cpf}}`, crie a chave `'estudante.cpf'` 
     *          e aponte para de onde a informação vem (ex: $contexto['user']->cpf).
     * 
     * NOTA: Sempre use o operador de coalescência nula (?? '') para evitar erros
     * se a variável não for passada no momento do envio do e-mail.
     * =========================================================================
     */

    // PASSO 1: O Dicionário Visual (Aparece na UI)
    public static function getDicionarioDisponivel(): array
    {
        return [
            'Estudante / Usuário' => [
                '{{estudante.nome}}' => 'Nome completo do usuário/estudante',
                '{{estudante.email}}' => 'E-mail de cadastro do usuário',
                '{{estudante.cpf}}' => 'CPF formatado do usuário',
            ],
            'Inscrição' => [
                '{{inscricao.curso}}' => 'Nome do curso selecionado',
                '{{inscricao.unidade}}' => 'Nome da unidade/sede',
                '{{inscricao.turno}}' => 'Turno escolhido',
                '{{inscricao.status}}' => 'Status atual da inscrição (ex: Aprovado, Pendente)',
                '{{inscricao.pontuacao}}' => 'Total de pontos obtidos',
                '{{inscricao.data}}' => 'Data e hora da realização da inscrição',
            ],
            'Sistema' => [
                '{{sistema.data_atual}}' => 'Data de hoje (Ex: 15/10/2026)',
                '{{sistema.nome_instituicao}}' => 'Nome da Instituição (Instituto Percorre)',
            ]
        ];
    }

    // PASSO 2: O Extrator Lógico (Valores Reais)
    private static function extrairValores(array $contexto): array
    {
        /** @var User|null $user */
        $user = $contexto['user'] ?? null;
        
        /** @var Inscricao|null $inscricao */
        $inscricao = $contexto['inscricao'] ?? null;

        // Recupera os nomes dos relacionamentos se existirem
        $nomeCurso = $inscricao && $inscricao->curso_id ? (\App\Models\Curso::find($inscricao->curso_id)->nome ?? 'Não informado') : 'Não informado';
        $nomeUnidade = $inscricao && $inscricao->unidade_id ? (\App\Modules\Unidade\Domain\Models\Unidade::find($inscricao->unidade_id)->nome ?? 'Não informado') : 'Não informado';
        $nomeTurno = $inscricao && $inscricao->turno_id ? (\App\Modules\Turno\Domain\Models\Turno::find($inscricao->turno_id)->nome ?? 'Não informado') : 'Não informado';
        $nomeStatus = $inscricao && $inscricao->status_inscricao_id ? (\App\Models\StatusInscricao::find($inscricao->status_inscricao_id)->nome ?? 'Não informado') : 'Não informado';

        return [
            // Variáveis do Estudante
            'estudante.nome' => $user->name ?? $inscricao->nome ?? '',
            'estudante.email' => $user->email ?? $inscricao->email ?? '',
            'estudante.cpf' => $user->cpf ?? $inscricao->cpf ?? '',
            
            // Variáveis da Inscrição
            'inscricao.curso' => $nomeCurso,
            'inscricao.unidade' => $nomeUnidade,
            'inscricao.turno' => $nomeTurno,
            'inscricao.status' => $nomeStatus,
            'inscricao.pontuacao' => $inscricao->pontuacao_total ?? '0',
            'inscricao.data' => $inscricao ? $inscricao->created_at->format('d/m/Y H:i') : '',

            // Variáveis de Sistema
            'sistema.data_atual' => now()->format('d/m/Y'),
            'sistema.nome_instituicao' => 'Instituto Percorre',
        ];
    }

    /**
     * Método principal chamado na hora de enviar o e-mail.
     * Pega o Assunto ou Corpo do template e injeta as variáveis reais.
     */
    public static function parse(string $textoOriginal, array $contexto = []): string
    {
        if (empty($textoOriginal)) return '';

        $valores = self::extrairValores($contexto);
        $textoProcessado = $textoOriginal;

        foreach ($valores as $chave => $valor) {
            // Substitui {{chave}} pelo valor correspondente
            $textoProcessado = str_replace('{{' . $chave . '}}', $valor, $textoProcessado);
        }

        return $textoProcessado;
    }
}