<?php

namespace App\Modules\Financeiro\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\User;
use App\Modules\Financeiro\Domain\Models\Orcamento;
use App\Modules\Financeiro\Domain\Models\OrcamentoItem;
use App\Modules\Financeiro\Domain\Models\OrcamentoAvaliacao;

#[Layout('components.layouts.app')]
#[Title('Análise e Aprovação de Orçamento')]
class OrcamentoAprovacaoEditor extends Component
{
    public Orcamento $orcamento;

    // Índice do item selecionado na tabela da esquerda
    public int $itemSelecionadoIndex = 0;

    // Seleção em grupo (checkboxes) para avaliação em lote (Slide 46)
    public array $itensSelecionados = [];
    public bool $selecionarTudo = false;

    // Decisões do aprovador atual antes de salvar:
    // [item_id => ['status' => 'Aprovada'|'Aguardando Revisão pelo Responsável'|'Não Aprovada', 'motivo_revisao' => '...']]
    public array $decisoesAprovador = [];

    // Modal para preenchimento do "Motivo da Revisão" (individual ou em lote)
    public bool $modalMotivoRevisaoAberto = false;
    public string $modoRevisao = 'unico'; // 'unico' ou 'lote'
    public ?int $linhaParaRevisar = null;
    public string $statusParaAplicarNoModal = 'Aguardando Revisão pelo Responsável';
    public string $textoMotivoRevisao = '';

    // Drawer de Histórico e Comentários dividido em 3 abas: 'geral', 'item' (Memo) e 'revisao' (Motivo da Revisão)
    public bool $drawerComentariosAberto = false;
    public string $abaComentario = 'geral'; // 'geral', 'item', 'revisao'
    public ?int $itemComentarioId = null;
    public string $novoComentarioGeral = '';
    public string $novoComentarioItem = '';
    public string $novoMotivoRevisaoDrawer = '';

    public bool $modalAprovarReaberturaAberto = false;
    public $prazoReabertura = null;

    public array $itensClicados = [];

    public array $cenariosDisponiveis = [
        '1' => '1 - Conservador',
        '2' => '2 - Moderado',
        '3' => '3 - Aspiracional',
    ];

    public function mount($id)
    {
        abort_if(
            !feature('financeiro.orcamentos.aprovar')
            && !feature('financeiro.orcamentos.avaliacao')
            && !auth()->user()->hasRole('dev'),
            403,
            'Módulo de avaliação orçamentária desativado.'
        );
        abort_if(
            !auth()->user()->hasRole('dev')
            && !auth()->user()->can('financeiro.orcamentos.aprovar')
            && !auth()->user()->can('financeiro.orcamentos.avaliacao'),
            403,
            'Sem permissão para aprovar orçamentos.'
        );

        $this->orcamento = Orcamento::with([
            'itens' => fn($q) => $q->orderBy('id', 'asc'),
            'itens.natureza',
            'itens.avaliacoes.usuario',
            'centroCusto.users',
            'logsGerais.usuario'
        ])->findOrFail($id);

        if ($this->orcamento->itens->isEmpty()) {
            \App\Modules\Financeiro\Services\ProtheusOrcamentoService::sincronizarDetalhesOrcamento($this->orcamento);
            $this->orcamento->load(['itens' => fn($q) => $q->orderBy('id', 'asc'), 'itens.natureza', 'itens.avaliacoes']);
        }

        if (!auth()->user()->hasRole('dev')) {
            if (method_exists(auth()->user(), 'centros_de_custo')) {
                $ccPermitidos = auth()->user()->centros_de_custo()
                    ->where(function ($q) {
                        $q->whereNull('centro_custo_user.expires_at')
                          ->orWhereDate('centro_custo_user.expires_at', '>=', now()->toDateString());
                    })
                    ->pluck('centros_custo.codigo')
                    ->toArray();

                abort_unless(in_array($this->orcamento->ccusto, $ccPermitidos), 403, 'Você não está vinculado como aprovador deste Centro de Custo.');
            } else {
                abort(403, 'Sem vínculo com Centros de Custo.');
            }
        }

        $primeiroItem = $this->orcamento->itens->first();
        if ($primeiroItem) {
            $this->itemComentarioId = $primeiroItem->id;
        }

        $this->carregarDecisoesExistentesDoAprovador();

        if ($this->orcamento->itens->isNotEmpty()) {
            $this->selecionarItem(0);
        }
    }

    /**
     * Conta apenas os aprovadores reais vinculados ao Centro de Custo (usuários 'dev' NÃO contam)
     */
    public function obterTotalAprovadores(): int
    {
        if ($this->orcamento->centroCusto) {
            $aprovadoresCentroCusto = $this->orcamento->centroCusto->users()
                ->where(function ($q) {
                    $q->whereNull('centro_custo_user.expires_at')
                      ->orWhereDate('centro_custo_user.expires_at', '>=', now()->toDateString());
                })
                ->get()
                ->reject(fn($u) => $u->hasRole('dev'))
                ->filter(fn($u) => $u->can('financeiro.orcamentos.aprovar') || $u->can('financeiro.orcamentos.avaliacao'))
                ->count();

            return max(1, $aprovadoresCentroCusto);
        }

        return 1;
    }

    /**
     * Verifica se o orçamento aceita comentários (bloqueado quando finalizado/aprovado)
     */
    public function podeInserirComentarios(): bool
    {
        $statusFinalizados = [
            'Finalizado – Aprovado',
            'Finalizado - Aprovado',
            'Finalizado – Integrado ao Protheus',
            'Finalizado - Integrado ao Protheus',
            'Aprovado',
            'Aprovado com ressalvas',
        ];

        return !in_array($this->orcamento->status, $statusFinalizados, true);
    }

    public function salvarAnaliseAprovador()
    {
        if (!$this->podeInserirComentarios()) {
            $this->dispatch('erro', msg: 'Este orçamento já está finalizado e não permite novas análises.');
            return;
        }

        // Regra: Aprovação do 'dev' NÃO conta para o andamento do processo de aprovação
        if (auth()->user()->hasRole('dev')) {
            $this->dispatch('erro', msg: 'Aprovação de usuário DEV não conta para o andamento oficial do processo de aprovação. Realize a aprovação com o usuário Aprovador vinculado ao Centro de Custo.');
            return;
        }

        $itens = $this->orcamento->itens()->orderBy('id', 'asc')->get();

        foreach ($itens as $seqIdx => $item) {
            if (in_array($item->status, ['Aprovada', 'Aprovado'], true)) {
                continue;
            }
            $codVisual = $item->id_despesa ?: str_pad((string) ($seqIdx + 1), 3, '0', STR_PAD_LEFT);
            if (empty($this->decisoesAprovador[$item->id]['status'])) {
                $this->dispatch('erro', msg: "Analise todos os itens antes de salvar. Item pendente: #{$codVisual}.");
                return;
            }
        }

        $totalAprovadores = $this->obterTotalAprovadores();
        $userId = auth()->id();

        foreach ($itens as $item) {
            if (in_array($item->status, ['Aprovada', 'Aprovado'], true)) {
                continue;
            }

            $decisao = $this->decisoesAprovador[$item->id];
            $votoStatus = $decisao['status'];
            $motivoRevisao = trim($decisao['motivo_revisao'] ?? '');

            $tipoEvento = ($votoStatus === 'Aguardando Revisão pelo Responsável') ? 'motivo_revisao' : 'avaliacao_item';

            OrcamentoAvaliacao::create([
                'orcamento_id'      => $this->orcamento->id,
                'orcamento_item_id' => $item->id,
                'user_id'           => $userId,
                'user_nome'         => auth()->user()->name,
                'status_aplicado'   => $votoStatus,
                'comentario'        => $motivoRevisao ?: null,
                'tipo_evento'       => $tipoEvento,
            ]);

            $ultimaLiberacao = OrcamentoAvaliacao::where('orcamento_item_id', $item->id)
                ->where('tipo_evento', 'liberacao_item')
                ->latest('id')
                ->first();

            $votosCicloQuery = OrcamentoAvaliacao::with('usuario')
                ->where('orcamento_item_id', $item->id)
                ->whereIn('tipo_evento', ['avaliacao_item', 'motivo_revisao']);

            if ($ultimaLiberacao) {
                $votosCicloQuery->where('id', '>', $ultimaLiberacao->id);
            }

            // Ignora qualquer voto que tenha sido feito por usuário 'dev'
            $votosPorAprovador = $votosCicloQuery->get()
                ->reject(fn($aval) => $aval->usuario && $aval->usuario->hasRole('dev'))
                ->groupBy('user_id')
                ->map(fn($grupo) => $grupo->last());

            $qtdAprovadoresQueVotaram = $votosPorAprovador->count();
            $algumPediuRevisao = $votosPorAprovador->contains(fn($v) => $v->status_aplicado === 'Aguardando Revisão pelo Responsável');
            $algumNaoAprovou   = $votosPorAprovador->contains(fn($v) => in_array($v->status_aplicado, ['Não Aprovada', 'Não Aprovado', 'Reprovado'], true));

            if ($algumPediuRevisao) {
                $novoStatusItem = 'Aguardando Revisão pelo Responsável';
            } elseif ($algumNaoAprovou) {
                $novoStatusItem = ($totalAprovadores > 1 && $qtdAprovadoresQueVotaram < $totalAprovadores)
                    ? 'Reprovado parcialmente'
                    : 'Não Aprovada';
            } else {
                $novoStatusItem = ($totalAprovadores > 1 && $qtdAprovadoresQueVotaram < $totalAprovadores)
                    ? 'Aprovada Parcialmente'
                    : 'Aprovada';
            }

            $item->update(['status' => $novoStatusItem]);
        }

        $itensAtualizados = $this->orcamento->itens()->orderBy('id', 'asc')->get();

        $aindaAguardandoOutrosAprovadores = $itensAtualizados->contains(fn($i) => in_array($i->status, ['Aprovação Pendente', 'Aprovada Parcialmente', 'Reprovado parcialmente'], true));
        $temPendenciaDeRevisaoOuReprovacao = $itensAtualizados->contains(fn($i) => in_array($i->status, ['Aguardando Revisão pelo Responsável', 'Não Aprovada', 'Não Aprovado', 'Reprovado'], true));
        $todosAprovados = $itensAtualizados->every(fn($i) => in_array($i->status, ['Aprovada', 'Aprovado'], true));

        if ($todosAprovados) {
            $this->orcamento->update(['status' => 'Finalizado – Aprovado']);
            $this->dispatch('sucesso', msg: 'Todos os itens foram aprovados! O orçamento está agora com status "Finalizado – Aprovado".');
        } elseif (!$aindaAguardandoOutrosAprovadores && $temPendenciaDeRevisaoOuReprovacao) {
            $this->orcamento->update(['status' => 'Aprovação – Aguardando Revisão pelo Responsável']);
            $this->dispatch('sucesso', msg: 'Análise salva! O orçamento passou para "Aprovação – Aguardando Revisão pelo Responsável".');
        } else {
            $this->orcamento->update(['status' => 'Finalizado – Aguardando Aprovação']);
            $this->dispatch('sucesso', msg: 'Análise parcial registrada com sucesso! Aguardando os demais aprovadores.');
        }

        $this->orcamento->refresh();

        \App\Modules\Financeiro\Services\OrcamentoNotificacaoService::notificarMovimentacaoAprovacao(
            $this->orcamento,
            auth()->user()
        );
    }

    private function carregarDecisoesExistentesDoAprovador(): void
    {
        $userId = auth()->id();

        foreach ($this->orcamento->itens as $item) {
            if (in_array($item->status, ['Aprovada', 'Aprovado'], true)) {
                $this->decisoesAprovador[$item->id] = [
                    'status'         => 'Aprovada',
                    'motivo_revisao' => '',
                ];
                continue;
            }

            $ultimaLiberacao = OrcamentoAvaliacao::where('orcamento_item_id', $item->id)
                ->where('tipo_evento', 'liberacao_item')
                ->latest('id')
                ->first();

            $votoQuery = OrcamentoAvaliacao::where('orcamento_item_id', $item->id)
                ->where('user_id', $userId)
                ->whereIn('tipo_evento', ['avaliacao_item', 'motivo_revisao']);

            if ($ultimaLiberacao) {
                $votoQuery->where('id', '>', $ultimaLiberacao->id);
            }

            $meuVoto = $votoQuery->latest('id')->first();
            if ($meuVoto) {
                $this->decisoesAprovador[$item->id] = [
                    'status'         => $meuVoto->status_aplicado,
                    'motivo_revisao' => $meuVoto->comentario ?? '',
                ];
            }
        }
    }

    public function selecionarItem(int $index): void
    {
        $itens = $this->orcamento->itens->values();
        if (isset($itens[$index])) {
            $this->itemSelecionadoIndex = $index;
            $this->itemComentarioId = $itens[$index]->id;
            
            // Marca o item como clicado para liberar os botões de ação na view
            if (!in_array($this->itemComentarioId, $this->itensClicados, true)) {
                $this->itensClicados[] = $this->itemComentarioId;
            }
        }
    }

    public function updatedSelecionarTudo($value): void
    {
        if ($value) {
            $this->itensSelecionados = $this->orcamento->itens
                ->reject(fn($i) => in_array($i->status, ['Aprovada', 'Aprovado'], true))
                ->pluck('id')
                ->map(fn($id) => (string) $id)
                ->values()
                ->toArray();
        } else {
            $this->itensSelecionados = [];
        }
    }

    /**
     * Define a decisão para 1 item individualmente:
     * - 'Aprovada'
     * - 'Aguardando Revisão pelo Responsável' (abre modal obrigatório de Motivo da Revisão)
     * - 'Não Aprovada'
     */
    public function definirStatusItem(int $itemId, string $statusDesejado): void
    {
        if (in_array($this->orcamento->status, ['Finalizado – Aprovado', 'Finalizado – Integrado ao Protheus'], true)) {
            $this->dispatch('erro', msg: 'Este orçamento já foi finalizado e aprovado.');
            return;
        }

        // Slide 47: Se mudar para "Aguardando Revisão pelo Responsável", exige preencher o campo "Motivo da Revisão"
        if ($statusDesejado === 'Aguardando Revisão pelo Responsável') {
            $this->modoRevisao = 'unico';
            $this->linhaParaRevisar = $itemId;
            $this->statusParaAplicarNoModal = 'Aguardando Revisão pelo Responsável';
            $this->textoMotivoRevisao = $this->decisoesAprovador[$itemId]['motivo_revisao'] ?? '';
            $this->resetErrorBag();
            $this->modalMotivoRevisaoAberto = true;
            return;
        }

        $this->decisoesAprovador[$itemId] = [
            'status'         => $statusDesejado,
            'motivo_revisao' => '',
        ];
    }

    // 2. Substitua o método aplicarDecisaoLote() por:
    public function aplicarDecisaoLote(string $statusDesejado): void
    {
        if (empty($this->itensSelecionados)) {
            $this->dispatch('erro', msg: 'Selecione pelo menos um item nas caixas de seleção.');
            return;
        }

        if ($statusDesejado === 'Aguardando Revisão pelo Responsável') {
            $this->modoRevisao = 'lote';
            $this->linhaParaRevisar = null;
            $this->statusParaAplicarNoModal = 'Aguardando Revisão pelo Responsável';
            $this->textoMotivoRevisao = '';
            $this->resetErrorBag();
            $this->modalMotivoRevisaoAberto = true;
            return;
        }

        foreach ($this->itensSelecionados as $idStr) {
            $itemId = (int) $idStr;
            $this->decisoesAprovador[$itemId] = [
                'status'         => $statusDesejado,
                'motivo_revisao' => '',
            ];

            // Libera os botões do item alterado em lote para exibir o destaque sólido imediatamente
            if (!in_array($itemId, $this->itensClicados, true)) {
                $this->itensClicados[] = $itemId;
            }
        }

        $qtd = count($this->itensSelecionados);
        $this->itensSelecionados = [];
        $this->selecionarTudo = false;
        $this->dispatch('sucesso', msg: "Decisão '{$statusDesejado}' marcada para {$qtd} item(ns). Clique em 'Salvar Análise' para confirmar.");
    }

    // 3. Substitua o método confirmarMotivoRevisaoModal() por:
    public function confirmarMotivoRevisaoModal(): void
    {
        $this->validate([
            'textoMotivoRevisao' => 'required|string|min:3',
        ], [
            'textoMotivoRevisao.required' => 'O preenchimento do Motivo da Revisão é obrigatório ao solicitar revisão do item.',
            'textoMotivoRevisao.min'      => 'Detalhe o motivo da revisão com pelo menos 3 caracteres.',
        ]);

        if ($this->modoRevisao === 'unico' && $this->linhaParaRevisar) {
            $itemId = (int) $this->linhaParaRevisar;
            $this->decisoesAprovador[$itemId] = [
                'status'         => 'Aguardando Revisão pelo Responsável',
                'motivo_revisao' => trim($this->textoMotivoRevisao),
            ];
            if (!in_array($itemId, $this->itensClicados, true)) {
                $this->itensClicados[] = $itemId;
            }
        } elseif ($this->modoRevisao === 'lote') {
            foreach ($this->itensSelecionados as $idStr) {
                $itemId = (int) $idStr;
                $this->decisoesAprovador[$itemId] = [
                    'status'         => 'Aguardando Revisão pelo Responsável',
                    'motivo_revisao' => trim($this->textoMotivoRevisao),
                ];
                if (!in_array($itemId, $this->itensClicados, true)) {
                    $this->itensClicados[] = $itemId;
                }
            }
            $this->itensSelecionados = [];
            $this->selecionarTudo = false;
        }

        $this->modalMotivoRevisaoAberto = false;
        $this->linhaParaRevisar = null;
        $this->textoMotivoRevisao = '';
    }

   

    /**
     * Slide 47: Permite registrar um novo Motivo da Revisão quando a despesa está marcada como "Aguardando Revisão pelo Responsável"
     */
    public function adicionarMotivoRevisaoNoDrawer(): void
    {
        if (!$this->podeInserirComentarios()) {
            $this->dispatch('erro', msg: 'Orçamentos finalizados não permitem novos comentários.');
            return;
        }
        
        $this->validate([
            'itemComentarioId'        => 'required|integer',
            'novoMotivoRevisaoDrawer' => 'required|string|min:3',
        ]);

        $item = OrcamentoItem::findOrFail($this->itemComentarioId);
        $statusNaTela = $this->decisoesAprovador[$item->id]['status'] ?? $item->status;

        if ($statusNaTela !== 'Aguardando Revisão pelo Responsável') {
            $this->dispatch('erro', msg: 'O campo Motivo da Revisão só pode ser preenchido quando a despesa estiver marcada como "Aguardando Revisão pelo Responsável".');
            return;
        }

        $motivoTexto = trim($this->novoMotivoRevisaoDrawer);

        OrcamentoAvaliacao::create([
            'orcamento_id'      => $this->orcamento->id,
            'orcamento_item_id' => $item->id,
            'user_id'           => auth()->id(),
            'user_nome'         => auth()->user()->name,
            'status_aplicado'   => 'Aguardando Revisão pelo Responsável',
            'comentario'        => $motivoTexto,
            'tipo_evento'       => 'motivo_revisao'
        ]);

        // ---> ATUALIZAÇÃO 3: Dispara e-mail e notificação interna ao adicionar motivo de revisão pelo painel lateral
        // \App\Modules\Financeiro\Services\OrcamentoNotificacaoService::notificarMotivoRevisaoItem(
        //     $this->orcamento,
        //     $item,
        //     $motivoTexto,
        //     auth()->user()
        // );

        $this->decisoesAprovador[$item->id]['motivo_revisao'] = $motivoTexto;
        $this->novoMotivoRevisaoDrawer = '';
        $this->orcamento->refresh();
    }

    // =========================================================================
    // HISTÓRICOS: GERAL | COMENTÁRIOS (MEMO) | MOTIVO DA REVISÃO
    // =========================================================================

    public function abrirComentariosGerais(): void
    {
        $this->abaComentario = 'geral';
        $this->drawerComentariosAberto = true;
    }

    public function abrirComentariosDoItem(int $index, int $itemId): void
    {
        $this->selecionarItem($index);
        $this->itemComentarioId = $itemId;
        $this->abaComentario = 'item';
        $this->drawerComentariosAberto = true;
    }

    public function abrirHistoricoMotivoRevisao(int $index, int $itemId): void
    {
        $this->selecionarItem($index);
        $this->itemComentarioId = $itemId;
        $this->abaComentario = 'revisao';
        $this->drawerComentariosAberto = true;
    }

    public function adicionarComentarioGlobal(): void
    {
        if (!$this->podeInserirComentarios()) {
            $this->dispatch('erro', msg: 'Orçamentos finalizados não permitem novos comentários.');
            return;
        }

        $this->validate(['novoComentarioGeral' => 'required|string|min:2']);

        OrcamentoAvaliacao::create([
            'orcamento_id'      => $this->orcamento->id,
            'orcamento_item_id' => null,
            'user_id'           => auth()->id(),
            'user_nome'         => auth()->user()->name,
            'status_aplicado'   => 'Comentário Geral',
            'comentario'        => trim($this->novoComentarioGeral),
            'tipo_evento'       => 'comentario_chat'
        ]);

        $this->novoComentarioGeral = '';
        $this->orcamento->refresh();
    }

    public function adicionarComentarioPorItem(): void
    {
        if (!$this->podeInserirComentarios()) {
            $this->dispatch('erro', msg: 'Orçamentos finalizados não permitem novos comentários.');
            return;
        }

        $this->validate([
            'itemComentarioId'   => 'required|integer',
            'novoComentarioItem' => 'required|string|min:2',
        ]);

        OrcamentoAvaliacao::create([
            'orcamento_id'      => $this->orcamento->id,
            'orcamento_item_id' => $this->itemComentarioId,
            'user_id'           => auth()->id(),
            'user_nome'         => auth()->user()->name,
            'status_aplicado'   => 'Comentário no Item',
            'comentario'        => trim($this->novoComentarioItem),
            'tipo_evento'       => 'comentario_item'
        ]);

        $this->novoComentarioItem = '';
        $this->orcamento->refresh();
    }

    

    public function aprovarReabertura(): void
    {
        $this->orcamento->update([
            'status'                => 'Em revisão/atualização',
            'reabertura_solicitada' => false,
            'prazo_edicao'          => $this->prazoReabertura ?: null,
        ]);

        OrcamentoAvaliacao::create([
            'orcamento_id'    => $this->orcamento->id,
            'user_id'         => auth()->id(),
            'user_nome'       => auth()->user()->name,
            'status_aplicado' => 'Reabertura Aprovada',
            'comentario'      => 'Prazo limite: ' . ($this->prazoReabertura ? date('d/m/Y H:i', strtotime($this->prazoReabertura)) : 'Ilimitado.'),
            'tipo_evento'     => 'reabertura_aprovada'
        ]);

        $this->modalAprovarReaberturaAberto = false;
        $this->prazoReabertura = null;
        $this->dispatch('sucesso', msg: 'Orçamento reaberto para revisão do responsável.');
        $this->orcamento->refresh();
    }

    public function negarReabertura(): void
    {
        $this->orcamento->update(['reabertura_solicitada' => false]);

        OrcamentoAvaliacao::create([
            'orcamento_id'    => $this->orcamento->id,
            'user_id'         => auth()->id(),
            'user_nome'       => auth()->user()->name,
            'status_aplicado' => 'Reabertura Negada',
            'comentario'      => 'Pedido de reabertura negado.',
            'tipo_evento'     => 'reabertura_negada'
        ]);

        $this->modalAprovarReaberturaAberto = false;
        $this->dispatch('sucesso', msg: 'Pedido de reabertura negado.');
        $this->orcamento->refresh();
    }

    public function voltar()
    {
        return redirect()->route('financeiro.aprovacoes');
    }

    public function render()
    {
        $itensOrdenados = $this->orcamento->itens()->with(['natureza', 'avaliacoes'])->orderBy('id', 'asc')->get();

        $comentariosGerais = OrcamentoAvaliacao::where('orcamento_id', $this->orcamento->id)
            ->whereNull('orcamento_item_id')
            ->orderBy('created_at', 'asc')
            ->get();

        $comentariosDoItemSelecionado = collect();
        $motivosRevisaoDoItem = collect();

        if ($this->itemComentarioId) {
            $comentariosDoItemSelecionado = OrcamentoAvaliacao::where('orcamento_id', $this->orcamento->id)
                ->where('orcamento_item_id', $this->itemComentarioId)
                ->where('tipo_evento', '!=', 'motivo_revisao')
                ->orderBy('created_at', 'asc')
                ->get();

            $motivosRevisaoDoItem = OrcamentoAvaliacao::where('orcamento_id', $this->orcamento->id)
                ->where('orcamento_item_id', $this->itemComentarioId)
                ->where('tipo_evento', 'motivo_revisao')
                ->orderBy('created_at', 'asc')
                ->get();
        }

        return view('livewire.financeiro.orcamento-aprovacao-editor', [
            'itensOrdenados'               => $itensOrdenados,
            'comentariosGerais'            => $comentariosGerais,
            'comentariosDoItemSelecionado' => $comentariosDoItemSelecionado,
            'motivosRevisaoDoItem'         => $motivosRevisaoDoItem,
            'totalAprovadores'             => $this->obterTotalAprovadores(),
        ]);
    }
}