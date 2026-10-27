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
    public int $anoSimulacao = 2026;

    // Índice do item selecionado na tabela da esquerda
    public int $itemSelecionadoIndex = 0;

    // Decisões que o aprovador atual marcou na tela antes de clicar em "Salvar Análise"
    // Formato: [item_id => ['status' => 'Aprovado'|'Não Aprovado', 'comentario' => '...']]
    public array $decisoesAprovador = [];

    // Modal opcional para comentar ao clicar em "Não Aprovado" ou "Aprovar"
    public bool $modalAvaliacaoAberto = false;
    public ?int $linhaParaAvaliar = null;
    public string $statusAvaliacao = '';
    public string $comentarioAvaliacao = '';

    // Comentários divididos em 'geral' e 'item'
    public bool $drawerComentariosAberto = false;
    public string $abaComentario = 'geral';
    public ?int $itemComentarioId = null;
    public string $novoComentarioGeral = '';
    public string $novoComentarioItem = '';

    public bool $modalAprovarReaberturaAberto = false;
    public $prazoReabertura = null;

    public function mount($id)
    {
        $this->orcamento = Orcamento::with([
            'itens' => fn($q) => $q->orderBy('id', 'asc'),
            'itens.natureza',
            'itens.avaliacoes.usuario',
            'centroCusto.users',
            'logsGerais.usuario'
        ])->findOrFail($id);

        $this->anoSimulacao = session('ano_simulacao_orcamento', date('Y'));

        $primeiroItem = $this->orcamento->itens->first();
        if ($primeiroItem) {
            $this->itemComentarioId = $primeiroItem->id;
        }

        $this->carregarDecisoesExistentesDoAprovador();
    }

    /**
     * Calcula quantos aprovadores estão configurados para este Centro de Custo / Orçamento
     */
    public function obterTotalAprovadores(): int
    {
        if ($this->orcamento->centroCusto) {
            $aprovadoresCentroCusto = $this->orcamento->centroCusto->users()
                ->get()
                ->filter(fn($u) => $u->can('financeiro.orcamentos.avaliacao') || $u->hasRole('dev|admin'))
                ->count();

            if ($aprovadoresCentroCusto > 0) {
                return $aprovadoresCentroCusto;
            }
        }

        try {
            $totalPermissao = User::permission('financeiro.orcamentos.avaliacao')->count();
            return max(1, $totalPermissao);
        } catch (\Throwable $e) {
            return 1;
        }
    }

    /**
     * Carrega os votos que o aprovador logado já deu no ciclo atual de cada item
     */
    private function carregarDecisoesExistentesDoAprovador(): void
    {
        $userId = auth()->id();

        foreach ($this->orcamento->itens as $item) {
            if ($item->status === 'Aprovado') {
                $this->decisoesAprovador[$item->id] = [
                    'status'     => 'Aprovado',
                    'comentario' => '',
                ];
                continue;
            }

            $ultimaLiberacao = OrcamentoAvaliacao::where('orcamento_item_id', $item->id)
                ->where('tipo_evento', 'liberacao_item')
                ->latest('id')
                ->first();

            $votoQuery = OrcamentoAvaliacao::where('orcamento_item_id', $item->id)
                ->where('user_id', $userId)
                ->where('tipo_evento', 'avaliacao_item');

            if ($ultimaLiberacao) {
                $votoQuery->where('id', '>', $ultimaLiberacao->id);
            }

            $meuVoto = $votoQuery->latest('id')->first();
            if ($meuVoto) {
                $this->decisoesAprovador[$item->id] = [
                    'status'     => $meuVoto->status_aplicado,
                    'comentario' => $meuVoto->comentario ?? '',
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
        }
    }

    /**
     * Marca diretamente ou abre modal de justificativa para o item ("Aprovado" ou "Não Aprovado")
     */
    public function definirStatusItem(int $itemId, string $statusDesejado, bool $pedirComentario = false): void
    {
        $item = OrcamentoItem::findOrFail($itemId);

        if ($item->status === 'Aprovado' && $this->orcamento->status === 'Finalizado – Aprovado') {
            $this->dispatch('erro', msg: 'Este orçamento já está finalizado e aprovado.');
            return;
        }

        if ($pedirComentario) {
            $this->linhaParaAvaliar = $itemId;
            $this->statusAvaliacao = $statusDesejado;
            $this->comentarioAvaliacao = $this->decisoesAprovador[$itemId]['comentario'] ?? '';
            $this->modalAvaliacaoAberto = true;
            return;
        }

        $this->decisoesAprovador[$itemId] = [
            'status'     => $statusDesejado,
            'comentario' => $this->decisoesAprovador[$itemId]['comentario'] ?? '',
        ];
    }

    public function confirmarAvaliacaoModal(): void
    {
        if (!$this->linhaParaAvaliar) return;

        $this->decisoesAprovador[$this->linhaParaAvaliar] = [
            'status'     => $this->statusAvaliacao,
            'comentario' => trim($this->comentarioAvaliacao),
        ];

        $this->modalAvaliacaoAberto = false;
        $this->linhaParaAvaliar = null;
    }

    /**
     * Salva a análise do aprovador em todos os itens e atualiza os status (parciais ou finais)
     */
    public function salvarAnaliseAprovador()
    {
        $itens = $this->orcamento->itens()->orderBy('id', 'asc')->get();

        // Verifica se o aprovador analisou todos os itens
        foreach ($itens as $item) {
            if ($item->status === 'Aprovado') {
                continue;
            }
            if (empty($this->decisoesAprovador[$item->id]['status'])) {
                $this->dispatch('erro', msg: "Analise todos os itens (aprovando ou não aprovando) antes de clicar em Salvar. Item pendente: #" . str_pad((string) $item->id, 3, '0', STR_PAD_LEFT));
                return;
            }
        }

        $totalAprovadores = $this->obterTotalAprovadores();
        $userId = auth()->id();

        foreach ($itens as $item) {
            if ($item->status === 'Aprovado') {
                continue;
            }

            $decisao = $this->decisoesAprovador[$item->id];
            $votoStatus = $decisao['status']; // 'Aprovado' ou 'Não Aprovado'
            $comentario = trim($decisao['comentario'] ?? '');

            // Registra o voto do aprovador atual neste ciclo
            OrcamentoAvaliacao::create([
                'orcamento_id'      => $this->orcamento->id,
                'orcamento_item_id' => $item->id,
                'user_id'           => $userId,
                'user_nome'         => auth()->user()->name,
                'status_aplicado'   => $votoStatus,
                'comentario'        => $comentario ?: null,
                'tipo_evento'       => 'avaliacao_item',
            ]);

            // Busca todos os votos de aprovadores desde a última liberação deste item
            $ultimaLiberacao = OrcamentoAvaliacao::where('orcamento_item_id', $item->id)
                ->where('tipo_evento', 'liberacao_item')
                ->latest('id')
                ->first();

            $votosCicloQuery = OrcamentoAvaliacao::where('orcamento_item_id', $item->id)
                ->where('tipo_evento', 'avaliacao_item');

            if ($ultimaLiberacao) {
                $votosCicloQuery->where('id', '>', $ultimaLiberacao->id);
            }

            // Pega o último voto de cada aprovador distinto
            $votosPorAprovador = $votosCicloQuery->get()
                ->groupBy('user_id')
                ->map(fn($grupo) => $grupo->last());

            $qtdAprovadoresQueVotaram = $votosPorAprovador->count();
            $algumReprovou = $votosPorAprovador->contains(fn($v) => in_array($v->status_aplicado, ['Não Aprovado', 'Reprovado'], true));

            if ($totalAprovadores > 1 && $qtdAprovadoresQueVotaram < $totalAprovadores) {
                // Mais de 1 aprovador e ainda faltam outros aprovadores analisarem
                $novoStatusItem = $algumReprovou ? 'Reprovado parcialmente' : 'Aprovada Parcialmente';
            } else {
                // Todos os aprovadores analisaram (ou há apenas 1 aprovador)
                $novoStatusItem = $algumReprovou ? 'Não Aprovado' : 'Aprovado';
            }

            $item->update(['status' => $novoStatusItem]);
        }

        // Verifica o status global do Orçamento após salvar
        $itensAtualizados = $this->orcamento->itens()->orderBy('id', 'asc')->get();

        $aindaEmAnaliseParcial = $itensAtualizados->contains(fn($i) => in_array($i->status, ['Aprovação Pendente', 'Aprovada Parcialmente', 'Reprovado parcialmente'], true));
        $temItemNaoAprovado = $itensAtualizados->contains(fn($i) => in_array($i->status, ['Não Aprovado', 'Reprovado'], true));
        $todosAprovados = $itensAtualizados->every(fn($i) => $i->status === 'Aprovado');

        if ($todosAprovados) {
            $this->orcamento->update(['status' => 'Finalizado – Aprovado']);
            $this->dispatch('sucesso', msg: 'Todos os itens foram aprovados! O orçamento passou para "Finalizado – Aprovado".');
        } elseif (!$aindaEmAnaliseParcial && $temItemNaoAprovado) {
            $this->orcamento->update(['status' => 'Aprovação – Aguardando Revisão pelo Responsável']);
            $this->dispatch('sucesso', msg: 'Análise salva! Como há item(ns) não aprovado(s), o orçamento retornou para revisão do responsável.');
        } else {
            $this->orcamento->update(['status' => 'Finalizado – Aguardando Aprovação']);
            $this->dispatch('sucesso', msg: 'Análise parcial registrada! Aguardando a avaliação do(s) demais aprovador(es).');
        }

        $this->orcamento->refresh();
    }

    // =========================================================================
    // HISTÓRICO E COMENTÁRIOS (GERAIS vs POR ITEM)
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

    public function adicionarComentarioGlobal(): void
    {
        $this->validate(
            ['novoComentarioGeral' => 'required|string|min:2'],
            ['novoComentarioGeral.required' => 'Digite uma mensagem antes de enviar.']
        );

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
        $this->dispatch('comentario-adicionado'); 
    }

    public function adicionarComentarioPorItem(): void
    {
        $this->validate([
            'itemComentarioId'   => 'required|integer',
            'novoComentarioItem' => 'required|string|min:2',
        ]);

        OrcamentoAvaliacao::create([
            'orcamento_id'      => $this->orcamento->id,
            'orcamento_item_id' => $this->itemComentarioId,
            'user_id'           => auth()->id(),
            'user_nome'         => auth()->user()->name,
            'status_aplicado'   => 'Comentário da Aprovação',
            'comentario'        => trim($this->novoComentarioItem),
            'tipo_evento'       => 'comentario_item'
        ]);

        $this->novoComentarioItem = '';
        $this->orcamento->refresh();
        $this->dispatch('comentario-adicionado');
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
        if ($this->itemComentarioId) {
            $comentariosDoItemSelecionado = OrcamentoAvaliacao::where('orcamento_id', $this->orcamento->id)
                ->where('orcamento_item_id', $this->itemComentarioId)
                ->orderBy('created_at', 'asc')
                ->get();
        }

        return view('livewire.financeiro.orcamento-aprovacao-editor', [
            'itensOrdenados'               => $itensOrdenados,
            'comentariosGerais'            => $comentariosGerais,
            'comentariosDoItemSelecionado' => $comentariosDoItemSelecionado,
            'totalAprovadores'             => $this->obterTotalAprovadores(),
        ]);
    }
}