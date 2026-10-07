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
    public bool $isAvaliacaoLote = false;
    public ?int $linhaParaAvaliar = null;
    public string $statusAvaliacao = '';
    public string $comentarioAvaliacao = '';

    public function mount($id)
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403, 'Acesso restrito a aprovadores.');
        
        $this->orcamento = Orcamento::with(['itens.natureza', 'itens.avaliacoes.usuario', 'centroCusto'])->findOrFail($id);
        $this->anoSimulacao = session('ano_simulacao_orcamento', date('Y'));
    }

    public function voltar()
    {
        return redirect()->route('financeiro.aprovacoes'); // Ajuste o nome da rota da listagem se for diferente
    }

    public function abrirModalAvaliacaoItem($itemId, $statusDesejado)
    {
        $item = OrcamentoItem::find($itemId);
        
        if (in_array($item->status, ['Aprovado', 'Aprovado com ressalvas', 'Reprovado'])) {
            $this->dispatch('erro', msg: 'Este item já foi avaliado e não pode ser alterado até que o gestor o reenvie.');
            return;
        }

        $this->isAvaliacaoLote = false;
        $this->linhaParaAvaliar = $itemId;
        $this->statusAvaliacao = $statusDesejado;
        $this->comentarioAvaliacao = '';
        $this->modalAvaliacaoAberto = true;
    }

    public function abrirModalAvaliacaoLote($statusDesejado)
    {
        $this->isAvaliacaoLote = true;
        $this->linhaParaAvaliar = null;
        $this->statusAvaliacao = $statusDesejado;
        $this->comentarioAvaliacao = '';
        $this->modalAvaliacaoAberto = true;
    }

    public function confirmarAvaliacao()
    {
        $this->validate(
            ['comentarioAvaliacao' => 'required|string|min:5'], 
            ['comentarioAvaliacao.required' => 'A justificativa é obrigatória para comunicar a decisão ao gestor.']
        );

        if ($this->isAvaliacaoLote) {
            $itensPendentes = $this->orcamento->itens()->whereIn('status', ['Criado', 'Corrigido'])->get();
            
            if ($itensPendentes->isEmpty()) {
                $this->dispatch('erro', msg: 'Não há naturezas pendentes de avaliação neste orçamento.');
                $this->modalAvaliacaoAberto = false;
                return;
            }

            foreach ($itensPendentes as $item) {
                $item->update(['status' => $this->statusAvaliacao]);
                OrcamentoAvaliacao::create([
                    'orcamento_item_id' => $item->id,
                    'user_id' => auth()->id(),
                    'user_nome' => auth()->user()->name,
                    'status_aplicado' => $this->statusAvaliacao,
                    'comentario' => $this->comentarioAvaliacao
                ]);
            }
        } else {
            $item = OrcamentoItem::findOrFail($this->linhaParaAvaliar);
            $item->update(['status' => $this->statusAvaliacao]);
            OrcamentoAvaliacao::create([
                'orcamento_item_id' => $item->id,
                'user_id' => auth()->id(),
                'user_nome' => auth()->user()->name,
                'status_aplicado' => $this->statusAvaliacao,
                'comentario' => $this->comentarioAvaliacao
            ]);
        }

        $temReprovado = $this->orcamento->itens()->whereIn('status', ['Reprovado', 'Aprovado com ressalvas'])->exists();
        $temPendente = $this->orcamento->itens()->whereIn('status', ['Criado', 'Corrigido'])->exists();

        if ($temReprovado) {
            $this->orcamento->update(['status' => 'Em elaboração']);
        } elseif (!$temPendente) {
            $this->orcamento->update(['status' => 'Aprovado']);
        }

        $this->modalAvaliacaoAberto = false;
        $msg = $this->isAvaliacaoLote ? "Todos os itens pendentes avaliados como {$this->statusAvaliacao}." : "Linha avaliada como {$this->statusAvaliacao}.";
        $this->dispatch('sucesso', msg: $msg);
        
        $this->orcamento->refresh();
    }

    public function render()
    {
        return view('livewire.financeiro.orcamento-aprovacao-editor');
    }
}