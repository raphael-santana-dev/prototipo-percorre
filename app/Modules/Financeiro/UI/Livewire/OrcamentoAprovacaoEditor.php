<?php

namespace App\Modules\Financeiro\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\Financeiro\Domain\Models\Orcamento;
use App\Modules\Financeiro\Domain\Models\OrcamentoItem;
use App\Modules\Financeiro\Domain\Models\OrcamentoAvaliacao;

#[Layout('components.layouts.app')]
#[Title('Análise de Orçamento - Excel View')]
class OrcamentoAprovacaoEditor extends Component
{
    public Orcamento $orcamento;
    public int $anoSimulacao = 2026;

    public bool $modalAvaliacaoAberto = false;
    public string $modoAvaliacao = 'unico'; 
    public ?int $linhaParaAvaliar = null;
    public string $statusAvaliacao = '';
    public string $comentarioAvaliacao = '';

    public array $itensSelecionados = [];
    public bool $selecionarTudo = false;

    // --- VARIÁVEIS DE ANÁLISE DE REABERTURA ---
    public bool $modalAprovarReaberturaAberto = false;
    public $prazoReabertura = null;

    public function mount($id)
    {
        // abort_if(!auth()->user()->hasRole('dev|admin'), 403, 'Acesso restrito a aprovadores.');
        
        $this->orcamento = Orcamento::with(['itens.natureza', 'itens.avaliacoes.usuario', 'centroCusto', 'logsGerais.usuario'])->findOrFail($id);
        $this->anoSimulacao = session('ano_simulacao_orcamento', date('Y'));
    }

    public function voltar() { return redirect()->route('financeiro.aprovacoes'); }

    public function updatedSelecionarTudo($value)
    {
        if ($value) {
            $this->itensSelecionados = $this->orcamento->itens()->whereIn('status', ['Criado', 'Corrigido'])->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->itensSelecionados = [];
        }
    }

    public function abrirModalAvaliacaoItem($itemId, $statusDesejado)
    {
        $item = OrcamentoItem::find($itemId);
        if (in_array($item->status, ['Aprovado', 'Aprovado com ressalvas', 'Reprovado'])) {
            $this->dispatch('erro', msg: 'Este item já foi avaliado e não pode ser alterado até que o gestor o reenvie.'); return;
        }
        $this->modoAvaliacao = 'unico'; $this->linhaParaAvaliar = $itemId; $this->statusAvaliacao = $statusDesejado; $this->comentarioAvaliacao = ''; $this->modalAvaliacaoAberto = true;
    }

    public function abrirModalAvaliacaoSelecionados($statusDesejado)
    {
        if (count($this->itensSelecionados) === 0) { $this->dispatch('erro', msg: 'Selecione pelo menos um item usando as caixas de seleção.'); return; }
        $this->modoAvaliacao = 'selecionados'; $this->linhaParaAvaliar = null; $this->statusAvaliacao = $statusDesejado; $this->comentarioAvaliacao = ''; $this->modalAvaliacaoAberto = true;
    }

    public function abrirModalAvaliacaoRestantes($statusDesejado)
    {
        $pendentes = $this->orcamento->itens()->whereIn('status', ['Criado', 'Corrigido'])->whereNotIn('id', $this->itensSelecionados)->count();
        if ($pendentes === 0) { $this->dispatch('erro', msg: 'Não há itens pendentes restantes para avaliar.'); return; }
        $this->modoAvaliacao = 'restantes'; $this->linhaParaAvaliar = null; $this->statusAvaliacao = $statusDesejado; $this->comentarioAvaliacao = ''; $this->modalAvaliacaoAberto = true;
    }

    public function confirmarAvaliacao()
    {
        $this->validate(['comentarioAvaliacao' => 'nullable|string']);
        $itensParaProcessar = collect();

        if ($this->modoAvaliacao === 'unico') { $itensParaProcessar->push(OrcamentoItem::findOrFail($this->linhaParaAvaliar)); } 
        elseif ($this->modoAvaliacao === 'selecionados') { $itensParaProcessar = OrcamentoItem::whereIn('id', $this->itensSelecionados)->get(); } 
        elseif ($this->modoAvaliacao === 'restantes') { $itensParaProcessar = $this->orcamento->itens()->whereIn('status', ['Criado', 'Corrigido'])->whereNotIn('id', $this->itensSelecionados)->get(); }

        foreach ($itensParaProcessar as $item) {
            $item->update(['status' => $this->statusAvaliacao]);
            OrcamentoAvaliacao::create([
                'orcamento_id' => $this->orcamento->id, 'orcamento_item_id' => $item->id, 'user_id' => auth()->id(), 'user_nome' => auth()->user()->name,
                'status_aplicado' => $this->statusAvaliacao, 'comentario' => trim($this->comentarioAvaliacao) ?: null, 'tipo_evento' => 'avaliacao_item'
            ]);
        }

        if ($this->modoAvaliacao === 'selecionados') { $this->itensSelecionados = []; $this->selecionarTudo = false; }

        $temReprovado = $this->orcamento->itens()->whereIn('status', ['Reprovado', 'Aprovado com ressalvas'])->exists();
        $temPendente = $this->orcamento->itens()->whereIn('status', ['Criado', 'Corrigido'])->exists();

        if ($temReprovado) { $this->orcamento->update(['status' => 'Em elaboração']); } 
        elseif (!$temPendente) { $this->orcamento->update(['status' => 'Aprovado']); }

        $this->modalAvaliacaoAberto = false; $this->dispatch('sucesso', msg: "Avaliação registrada com sucesso!"); $this->orcamento->refresh();
    }

    // --- MÉTODOS DE RESPOSTA À REABERTURA ---
    public function aprovarReabertura()
    {
        $this->orcamento->update([
            'status' => 'Em elaboração',
            'reabertura_solicitada' => false,
            'prazo_edicao' => $this->prazoReabertura ?: null,
        ]);

        OrcamentoAvaliacao::create([
            'orcamento_id' => $this->orcamento->id,
            'user_id' => auth()->id(),
            'user_nome' => auth()->user()->name,
            'status_aplicado' => 'Reabertura Aprovada',
            'comentario' => 'Prazo limite: ' . ($this->prazoReabertura ? date('d/m/Y H:i', strtotime($this->prazoReabertura)) : 'Ilimitado.'),
            'tipo_evento' => 'reabertura_aprovada'
        ]);

        $this->modalAprovarReaberturaAberto = false;
        $this->prazoReabertura = null;
        $this->dispatch('sucesso', msg: 'Orçamento reaberto para edição do Gestor.');
        $this->orcamento->refresh();
    }

    public function negarReabertura()
    {
        $this->orcamento->update([
            'reabertura_solicitada' => false,
        ]);

        OrcamentoAvaliacao::create([
            'orcamento_id' => $this->orcamento->id,
            'user_id' => auth()->id(),
            'user_nome' => auth()->user()->name,
            'status_aplicado' => 'Reabertura Negada',
            'comentario' => 'A Diretoria negou o pedido. O orçamento permanecerá bloqueado.',
            'tipo_evento' => 'reabertura_negada'
        ]);

        $this->modalAprovarReaberturaAberto = false;
        $this->dispatch('sucesso', msg: 'Pedido de reabertura negado.');
        $this->orcamento->refresh();
    }

    public function render()
    {
        return view('livewire.financeiro.orcamento-aprovacao-editor');
    }
}