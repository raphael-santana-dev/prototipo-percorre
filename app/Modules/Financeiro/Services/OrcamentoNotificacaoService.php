<?php

namespace App\Modules\Financeiro\Services;

use App\Models\User;
use App\Modules\Financeiro\Domain\Models\Orcamento;
use App\Modules\Financeiro\Domain\Models\OrcamentoAvaliacao;
use App\Notifications\OrcamentoWorkflowNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class OrcamentoNotificacaoService
{
    /**
     * Obtém TODOS os Aprovadores elegíveis para este Orçamento:
     * 1) Usuários vinculados a este Centro de Custo para aprovação ('tipo_acesso' = 'aprovacao')
     * 2) Usuários com Permissão Global de Centros de Custo ('centros_custo.visao_global')
     * Ambos precisam ter a permissão 'financeiro.orcamentos.aprovar' (e não serem 'dev').
     */
    public static function obterAprovadores(Orcamento $orcamento): Collection
    {
        $orcamento->loadMissing('centroCusto');

        // 1. Aprovadores vinculados diretamente ao Centro de Custo
        $vinculadosAoCC = collect();
        if ($orcamento->centroCusto) {
            $vinculadosAoCC = $orcamento->centroCusto->aprovadores()
                ->where(function ($q) {
                    $q->whereNull('centro_custo_user.expires_at')
                      ->orWhereDate('centro_custo_user.expires_at', '>=', now()->toDateString());
                })
                ->get();
        }

        // 2. Usuários com Permissão Global de Centros de Custo
        $usuariosGlobais = User::all()->filter(function (User $u) {
            return $u->possuiVisaoGlobalCentrosCusto('aprovacao');
        });

        return $vinculadosAoCC
            ->concat($usuariosGlobais)
            ->unique('id')
            ->reject(fn (User $user) => $user->hasRole('dev'))
            ->filter(function (User $user) {
                return $user->can('financeiro.orcamentos.aprovar')
                    || $user->can('financeiro.orcamentos.avaliacao');
            })
            ->values();
    }

    /**
     * Obtém os Responsáveis (Editores) vinculados ao Centro de Custo deste Orçamento
     */
    public static function obterResponsaveis(Orcamento $orcamento): Collection
    {
        $orcamento->loadMissing('centroCusto');

        $vinculadosEdicao = collect();
        if ($orcamento->centroCusto) {
            $vinculadosEdicao = $orcamento->centroCusto->editores()
                ->where(function ($q) {
                    $q->whereNull('centro_custo_user.expires_at')
                      ->orWhereDate('centro_custo_user.expires_at', '>=', now()->toDateString());
                })
                ->get();
        }

        $idsHistorico = OrcamentoAvaliacao::where('orcamento_id', $orcamento->id)
            ->where('tipo_evento', 'liberacao_item')
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->toArray();

        if (!empty($idsHistorico)) {
            $usuariosHistorico = User::whereIn('id', $idsHistorico)->get();
            $vinculadosEdicao = $vinculadosEdicao->concat($usuariosHistorico);
        }

        return $vinculadosEdicao
            ->unique('id')
            ->reject(fn (User $user) => $user->hasRole('dev'))
            ->filter(function (User $user) {
                return $user->can('financeiro.orcamentos.editar')
                    || $user->can('financeiro.orcamentos.listar');
            })
            ->values();
    }

    private static function dispararParaUsuarios(Collection $usuarios, array $payload): void
    {
        $usuariosValidos = $usuarios->filter(fn($u) => $u instanceof User && !empty($u->id))->unique('id');

        foreach ($usuariosValidos as $usuario) {
            try {
                $usuario->notify(new OrcamentoWorkflowNotification($payload));
            } catch (\Throwable $e) {
                Log::warning("Falha ao gravar notificação interna para user #{$usuario->id}: " . $e->getMessage());
            }

            if (!empty($usuario->email)) {
                try {
                    Notification::route('mail', $usuario->email)
                        ->notify(new OrcamentoWorkflowNotification($payload));
                } catch (\Throwable $e) {
                    Log::warning("Falha ao enviar e-mail de orçamento para {$usuario->email}: " . $e->getMessage());
                }
            }
        }
    }

    public static function notificarSubmissaoParaAprovacao(Orcamento $orcamento, ?User $responsavel = null): void
    {
        $orcamento->loadMissing('centroCusto');
        $ccNome = $orcamento->centroCusto ? $orcamento->centroCusto->nome : $orcamento->ccusto;
        $nomeAutor = $responsavel ? $responsavel->name : (auth()->user()->name ?? 'Responsável');

        $aprovadores = self::obterAprovadores($orcamento);

        self::dispararParaUsuarios($aprovadores, [
            'titulo'       => "Orçamento Aguardando Aprovação — CC {$orcamento->ccusto}",
            'mensagem'     => "O responsável {$nomeAutor} finalizou a edição do orçamento de {$orcamento->ano} do Centro de Custo {$orcamento->ccusto} ({$ccNome}) e submeteu-o para aprovação.",
            'detalhes'     => 'Valor Total Submetido: R$ ' . number_format((float) $orcamento->valor_total, 2, ',', '.'),
            'url'          => route('financeiro.aprovacoes.editor', $orcamento->id),
            'texto_botao'  => 'Analisar e Aprovar Orçamento',
            'icone'        => 'ph-clock-countdown',
            'cor'          => 'amber',
            'orcamento_id' => $orcamento->id,
            'ccusto'       => $orcamento->ccusto,
            'ano'          => $orcamento->ano,
            'status'       => $orcamento->status,
        ]);
    }

    public static function notificarMovimentacaoAprovacao(Orcamento $orcamento, ?User $aprovadorAtivo = null): void
    {
        $orcamento->loadMissing(['centroCusto', 'itens']);
        $ccNome = $orcamento->centroCusto ? $orcamento->centroCusto->nome : $orcamento->ccusto;
        $nomeAprovador = $aprovadorAtivo ? $aprovadorAtivo->name : (auth()->user()->name ?? 'Aprovador');
        $aprovadorId = $aprovadorAtivo ? $aprovadorAtivo->id : auth()->id();

        $qtdAprovadas  = $orcamento->itens->whereIn('status', ['Aprovada', 'Aprovado', 'Aprovada Parcialmente'])->count();
        $qtdRevisao    = $orcamento->itens->where('status', 'Aguardando Revisão pelo Responsável')->count();
        $qtdReprovadas = $orcamento->itens->whereIn('status', ['Não Aprovada', 'Não Aprovado', 'Reprovado', 'Reprovado parcialmente'])->count();

        $resumo = "Status Global: {$orcamento->status} | Aprovadas: {$qtdAprovadas} | Para Revisão: {$qtdRevisao} | Não Aprovadas: {$qtdReprovadas}.";

        $outrosAprovadores = self::obterAprovadores($orcamento)
            ->reject(fn(User $u) => (int) $u->id === (int) $aprovadorId);

        if ($outrosAprovadores->isNotEmpty()) {
            self::dispararParaUsuarios($outrosAprovadores, [
                'titulo'       => "Análise de Orçamento Finalizada por {$nomeAprovador} — CC {$orcamento->ccusto}",
                'mensagem'     => "O aprovador {$nomeAprovador} registrou a análise do orçamento {$orcamento->ano} do Centro de Custo {$orcamento->ccusto} ({$ccNome}).",
                'detalhes'     => $resumo,
                'url'          => route('financeiro.aprovacoes.editor', $orcamento->id),
                'texto_botao'  => 'Acessar Orçamento',
                'icone'        => 'ph-check-square-offset',
                'cor'          => 'blue',
                'orcamento_id' => $orcamento->id,
                'ccusto'       => $orcamento->ccusto,
                'ano'          => $orcamento->ano,
                'status'       => $orcamento->status,
            ]);
        }

        $responsaveis = self::obterResponsaveis($orcamento);

        self::dispararParaUsuarios($responsaveis, [
            'titulo'       => "Resultado da Análise do Orçamento — CC {$orcamento->ccusto}",
            'mensagem'     => "A análise do orçamento {$orcamento->ano} ({$ccNome}) foi concluída por {$nomeAprovador}.",
            'detalhes'     => $resumo,
            'url'          => route('financeiro.orcamentos.editor', $orcamento->id),
            'texto_botao'  => 'Acessar Orçamento',
            'icone'        => $orcamento->status === 'Finalizado – Aprovado' ? 'ph-check-circle' : 'ph-warning-circle',
            'cor'          => $orcamento->status === 'Finalizado – Aprovado' ? 'emerald' : 'amber',
            'orcamento_id' => $orcamento->id,
            'ccusto'       => $orcamento->ccusto,
            'ano'          => $orcamento->ano,
            'status'       => $orcamento->status,
        ]);
    }

    public static function notificarIntegracaoProtheus(Orcamento $orcamento, ?User $autor = null): void
    {
        $orcamento->loadMissing('centroCusto');
        $ccNome = $orcamento->centroCusto ? $orcamento->centroCusto->nome : $orcamento->ccusto;
        $nomeAutor = $autor ? $autor->name : (auth()->user()->name ?? 'Responsável');

        $destinatarios = self::obterAprovadores($orcamento)
            ->concat(self::obterResponsaveis($orcamento));

        if ($autor && !$autor->hasRole('dev')) {
            $destinatarios->push($autor);
        }

        self::dispararParaUsuarios($destinatarios, [
            'titulo'       => "Orçamento Integrado ao Protheus — CC {$orcamento->ccusto}",
            'mensagem'     => "O orçamento de {$orcamento->ano} do Centro de Custo {$orcamento->ccusto} ({$ccNome}) foi finalizado definitivamente por {$nomeAutor} e integrado à Tabela Intermediária do Protheus.",
            'detalhes'     => 'Status alterado para: Finalizado – Integrado ao Protheus (bloqueado para novas alterações).',
            'url'          => route('financeiro.orcamentos.editor', $orcamento->id),
            'texto_botao'  => 'Visualizar Orçamento',
            'icone'        => 'ph-cloud-check',
            'cor'          => 'indigo',
            'orcamento_id' => $orcamento->id,
            'ccusto'       => $orcamento->ccusto,
            'ano'          => $orcamento->ano,
            'status'       => 'Finalizado – Integrado ao Protheus',
        ]);
    }
}