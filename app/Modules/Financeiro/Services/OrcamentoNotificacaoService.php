<?php

namespace App\Modules\Financeiro\Services;

use App\Models\User;
use App\Modules\Financeiro\Domain\Models\Orcamento;
use App\Modules\Financeiro\Domain\Models\OrcamentoItem;
use App\Modules\Financeiro\Domain\Models\OrcamentoAvaliacao;
use App\Notifications\OrcamentoWorkflowNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class OrcamentoNotificacaoService
{
    /**
     * Obtém todos os utilizadores ativos vinculados a este Centro de Custo (respeitando expires_at)
     */
    public static function obterUsuariosVinculadosCentroCusto(Orcamento $orcamento): Collection
    {
        $orcamento->loadMissing('centroCusto');

        if (!$orcamento->centroCusto) {
            return collect();
        }

        return $orcamento->centroCusto->users()
            ->where(function ($q) {
                $q->whereNull('centro_custo_user.expires_at')
                  ->orWhereDate('centro_custo_user.expires_at', '>=', now()->toDateString());
            })
            ->get();
    }

    /**
     * Obtém os Aprovadores vinculados ao Centro de Custo (ou aprovadores globais como fallback)
     */
    public static function obterAprovadores(Orcamento $orcamento): Collection
    {
        $vinculados = self::obterUsuariosVinculadosCentroCusto($orcamento);

        $aprovadoresCC = $vinculados->filter(function (User $user) {
            return $user->can('financeiro.orcamentos.avaliacao') || $user->hasAnyRole(['dev', 'admin']);
        });

        if ($aprovadoresCC->isNotEmpty()) {
            return $aprovadoresCC->unique('id')->values();
        }

        try {
            return User::permission('financeiro.orcamentos.avaliacao')->get()->unique('id')->values();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /**
     * Obtém os Responsáveis pelo Orçamento do Centro de Custo
     */
    public static function obterResponsaveis(Orcamento $orcamento): Collection
    {
        $vinculados = self::obterUsuariosVinculadosCentroCusto($orcamento);

        // Utilizadores vinculados ao CC que elaboram/revisam o orçamento
        $responsaveis = $vinculados->filter(function (User $user) {
            return $user->can('financeiro.orcamentos.editar')
                || $user->can('financeiro.orcamentos.listar')
                || !$user->can('financeiro.orcamentos.avaliacao');
        });

        // Inclui também quem submeteu/liberou itens neste orçamento no histórico
        $idsHistorico = OrcamentoAvaliacao::where('orcamento_id', $orcamento->id)
            ->where('tipo_evento', 'liberacao_item')
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->toArray();

        if (!empty($idsHistorico)) {
            $usuariosHistorico = User::whereIn('id', $idsHistorico)->get();
            $responsaveis = $responsaveis->concat($usuariosHistorico);
        }

        return $responsaveis->unique('id')->values();
    }

    /**
     * Envia a notificação no banco (sininho da plataforma) e por e-mail
     */
    private static function dispararParaUsuarios(Collection $usuarios, array $payload): void
    {
        $usuariosValidos = $usuarios->filter(fn($u) => $u instanceof User && !empty($u->id))->unique('id');

        foreach ($usuariosValidos as $usuario) {
            // 1. Notificação Interna na Plataforma (Banco de Dados)
            try {
                $usuario->notify(new OrcamentoWorkflowNotification($payload));
            } catch (\Throwable $e) {
                Log::warning("Falha ao gravar notificação interna do orçamento para user #{$usuario->id}: " . $e->getMessage());
            }

            // 2. Disparo de E-mail
            if (!empty($usuario->email)) {
                try {
                    Notification::route('mail', $usuario->email)
                        ->notify(new OrcamentoWorkflowNotification($payload));
                } catch (\Throwable $e) {
                    Log::warning("Falha ao enviar e-mail de notificação do orçamento para {$usuario->email}: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * 1. SLIDES 44 e 46: Quando o Responsável submete o orçamento para aprovação
     */
    public static function notificarSubmissaoParaAprovacao(Orcamento $orcamento, ?User $responsavel = null): void
    {
        $orcamento->loadMissing('centroCusto');
        $ccNome = $orcamento->centroCusto ? $orcamento->centroCusto->nome : $orcamento->ccusto;
        $nomeAutor = $responsavel ? $responsavel->name : (auth()->user()->name ?? 'Responsável');

        $aprovadores = self::obterAprovadores($orcamento);

        $payload = [
            'titulo'       => "Orçamento Aguardando Aprovação — CC {$orcamento->ccusto}",
            'mensagem'     => "O responsável {$nomeAutor} finalizou a revisão do orçamento de {$orcamento->ano} do Centro de Custo {$orcamento->ccusto} ({$ccNome}) e submeteu-o para aprovação.",
            'detalhes'     => 'Valor Total Submetido: R$ ' . number_format((float) $orcamento->valor_total, 2, ',', '.'),
            'url'          => route('financeiro.aprovacoes.editor', $orcamento->id),
            'texto_botao'  => 'Analisar e Aprovar Orçamento',
            'icone'        => 'ph-clock-countdown',
            'cor'          => 'amber',
            'orcamento_id' => $orcamento->id,
            'ccusto'       => $orcamento->ccusto,
            'ano'          => $orcamento->ano,
            'status'       => $orcamento->status,
        ];

        self::dispararParaUsuarios($aprovadores, $payload);
    }

    /**
     * 2. SLIDE 47: Quando o Aprovador preenche o campo "Motivo da Revisão" numa despesa
     */
    public static function notificarMotivoRevisaoItem(Orcamento $orcamento, OrcamentoItem $item, string $motivo, ?User $aprovador = null): void
    {
        $orcamento->loadMissing('centroCusto');
        $ccNome = $orcamento->centroCusto ? $orcamento->centroCusto->nome : $orcamento->ccusto;
        $nomeAprovador = $aprovador ? $aprovador->name : (auth()->user()->name ?? 'Aprovador');
        $idDesp = $item->id_despesa ?: str_pad((string) $item->id, 3, '0', STR_PAD_LEFT);

        $responsaveis = self::obterResponsaveis($orcamento);

        $payload = [
            'titulo'       => "Revisão Solicitada na Despesa #{$idDesp} — CC {$orcamento->ccusto}",
            'mensagem'     => "O aprovador {$nomeAprovador} marcou a despesa #{$idDesp} ({$item->descricao}) do orçamento {$orcamento->ano} ({$ccNome}) com o status 'Aguardando Revisão pelo Responsável'.",
            'detalhes'     => "Motivo da Revisão: \"{$motivo}\"",
            'url'          => route('financeiro.orcamentos.editor', $orcamento->id),
            'texto_botao'  => 'Revisar Despesa no Orçamento',
            'icone'        => 'ph-warning-circle',
            'cor'          => 'orange',
            'orcamento_id' => $orcamento->id,
            'ccusto'       => $orcamento->ccusto,
            'ano'          => $orcamento->ano,
            'status'       => 'Aguardando Revisão pelo Responsável',
        ];

        self::dispararParaUsuarios($responsaveis, $payload);
    }

    /**
     * 3. SLIDE 43: Toda movimentação de aprovação gera notificação para os demais aprovadores (se existirem) e para o responsável
     */
    public static function notificarMovimentacaoAprovacao(Orcamento $orcamento, ?User $aprovadorAtivo = null): void
    {
        $orcamento->loadMissing('centroCusto');
        $ccNome = $orcamento->centroCusto ? $orcamento->centroCusto->nome : $orcamento->ccusto;
        $nomeAprovador = $aprovadorAtivo ? $aprovadorAtivo->name : (auth()->user()->name ?? 'Aprovador');
        $aprovadorId = $aprovadorAtivo ? $aprovadorAtivo->id : auth()->id();

        // Notifica os demais aprovadores (excluindo quem acabou de realizar a ação)
        $outrosAprovadores = self::obterAprovadores($orcamento)
            ->reject(fn(User $u) => (int) $u->id === (int) $aprovadorId);

        if ($outrosAprovadores->isNotEmpty()) {
            $payloadAprovadores = [
                'titulo'       => "Movimentação de Aprovação — CC {$orcamento->ccusto}",
                'mensagem'     => "O aprovador {$nomeAprovador} registou a sua análise no orçamento {$orcamento->ano} do Centro de Custo {$orcamento->ccusto} ({$ccNome}).",
                'detalhes'     => "Status atual do orçamento: {$orcamento->status}",
                'url'          => route('financeiro.aprovacoes.editor', $orcamento->id),
                'texto_botao'  => 'Aceder à Tela de Aprovação',
                'icone'        => 'ph-check-square-offset',
                'cor'          => 'blue',
                'orcamento_id' => $orcamento->id,
                'ccusto'       => $orcamento->ccusto,
                'ano'          => $orcamento->ano,
                'status'       => $orcamento->status,
            ];

            self::dispararParaUsuarios($outrosAprovadores, $payloadAprovadores);
        }

        // Notifica o(s) responsável(eis) pelo orçamento sobre a conclusão/atualização da análise
        $responsaveis = self::obterResponsaveis($orcamento);

        $payloadResponsavel = [
            'titulo'       => "Atualização na Aprovação do Orçamento — CC {$orcamento->ccusto}",
            'mensagem'     => "O aprovador {$nomeAprovador} registou uma avaliação no orçamento {$orcamento->ano} do Centro de Custo {$orcamento->ccusto} ({$ccNome}).",
            'detalhes'     => "Novo status global do orçamento: {$orcamento->status}",
            'url'          => route('financeiro.orcamentos.editor', $orcamento->id),
            'texto_botao'  => 'Visualizar Orçamento',
            'icone'        => $orcamento->status === 'Finalizado – Aprovado' ? 'ph-check-circle' : 'ph-info',
            'cor'          => $orcamento->status === 'Finalizado – Aprovado' ? 'emerald' : 'purpura',
            'orcamento_id' => $orcamento->id,
            'ccusto'       => $orcamento->ccusto,
            'ano'          => $orcamento->ano,
            'status'       => $orcamento->status,
        ];

        self::dispararParaUsuarios($responsaveis, $payloadResponsavel);
    }

    /**
     * 4. SLIDE 49: Quando o Responsável finaliza definitivamente e integra ao Protheus
     */
    public static function notificarIntegracaoProtheus(Orcamento $orcamento, ?User $autor = null): void
    {
        $orcamento->loadMissing('centroCusto');
        $ccNome = $orcamento->centroCusto ? $orcamento->centroCusto->nome : $orcamento->ccusto;
        $nomeAutor = $autor ? $autor->name : (auth()->user()->name ?? 'Responsável');

        $destinatarios = self::obterAprovadores($orcamento)
            ->concat(self::obterResponsaveis($orcamento));

        if ($autor) {
            $destinatarios->push($autor);
        }

        $payload = [
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
        ];

        self::dispararParaUsuarios($destinatarios, $payload);
    }
}