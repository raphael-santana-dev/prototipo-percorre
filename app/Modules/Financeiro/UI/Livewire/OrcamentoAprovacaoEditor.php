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

    // --- VARIÁVEIS DA AVALIAÇÃO ---
    public bool $modalAvaliacaoAberto = false;
    public string $modoAvaliacao = 'unico'; // unico, selecionados, restantes
    public ?int $linhaParaAvaliar = null;
    public string $statusAvaliacao = '';
    public string $comentarioAvaliacao = '';

    // --- VARIÁVEIS DE SELEÇÃO MÚLTIPLA ---
    public array $itensSelecionados = [];
    public bool $selecionarTudo = false;

    public function mount($id)
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403, 'Acesso restrito a aprovadores.');
        
        $this->orcamento = Orcamento::with(['itens.natureza', 'itens.avaliacoes.usuario', 'centroCusto'])->findOrFail($id);
        $this->anoSimulacao = session('ano_simulacao_orcamento', date('Y'));
    }

    public function voltar()
    {
        return redirect()->route('financeiro.aprovacoes'); 
    }

    // Toggle para a Checkbox "Selecionar Todos os Pendentes"
    public function updatedSelecionarTudo($value)
    {
        if ($value) {
            $this->itensSelecionados = $this->orcamento->itens()
                ->whereIn('status', ['Criado', 'Corrigido'])
                ->pluck('id')
                ->map(fn($id) => (string)$id)
                ->toArray();
        } else {
            $this->itensSelecionados = [];
        }
    }

    // 1. AVALIAR UM ÚNICO ITEM (No botão da linha)
    public function abrirModalAvaliacaoItem($itemId, $statusDesejado)
    {
        $item = OrcamentoItem::find($itemId);
        
        if (in_array($item->status, ['Aprovado', 'Aprovado com ressalvas', 'Reprovado'])) {
            $this->dispatch('erro', msg: 'Este item já foi avaliado e não pode ser alterado até que o gestor o reenvie.');
            return;
        }

        $this->modoAvaliacao = 'unico';
        $this->linhaParaAvaliar = $itemId;
        $this->statusAvaliacao = $statusDesejado;
        $this->comentarioAvaliacao = '';
        $this->modalAvaliacaoAberto = true;
    }

    // 2. AVALIAR ITENS SELECIONADOS NAS CHECKBOXES
    public function abrirModalAvaliacaoSelecionados($statusDesejado)
    {
        if (count($this->itensSelecionados) === 0) {
            $this->dispatch('erro', msg: 'Selecione pelo menos um item usando as caixas de seleção.');
            return;
        }

        $this->modoAvaliacao = 'selecionados';
        $this->linhaParaAvaliar = null;
        $this->statusAvaliacao = $statusDesejado;
        $this->comentarioAvaliacao = '';
        $this->modalAvaliacaoAberto = true;
    }

    // 3. AVALIAR ITENS RESTANTES PENDENTES
    public function abrirModalAvaliacaoRestantes($statusDesejado)
    {
        $pendentes = $this->orcamento->itens()
            ->whereIn('status', ['Criado', 'Corrigido'])
            ->whereNotIn('id', $this->itensSelecionados)
            ->count();
        
        if ($pendentes === 0) {
            $this->dispatch('erro', msg: 'Não há itens pendentes restantes para avaliar.');
            return;
        }

        $this->modoAvaliacao = 'restantes';
        $this->linhaParaAvaliar = null;
        $this->statusAvaliacao = $statusDesejado;
        $this->comentarioAvaliacao = '';
        $this->modalAvaliacaoAberto = true;
    }

    public function confirmarAvaliacao()
    {
        // Validação foi alterada: comentário agora é nullable (opcional)
        $this->validate([
            'comentarioAvaliacao' => 'nullable|string'
        ]);

        $itensParaProcessar = collect();

        if ($this->modoAvaliacao === 'unico') {
            $itensParaProcessar->push(OrcamentoItem::findOrFail($this->linhaParaAvaliar));
        } elseif ($this->modoAvaliacao === 'selecionados') {
            $itensParaProcessar = OrcamentoItem::whereIn('id', $this->itensSelecionados)->get();
        } elseif ($this->modoAvaliacao === 'restantes') {
            $itensParaProcessar = $this->orcamento->itens()
                ->whereIn('status', ['Criado', 'Corrigido'])
                ->whereNotIn('id', $this->itensSelecionados)
                ->get();
        }

        foreach ($itensParaProcessar as $item) {
            $item->update(['status' => $this->statusAvaliacao]);
            
            // Regista o log Híbrido (vinculado à linha e ao cabeçalho)
            OrcamentoAvaliacao::create([
                'orcamento_id' => $this->orcamento->id,
                'orcamento_item_id' => $item->id,
                'user_id' => auth()->id(),
                'user_nome' => auth()->user()->name,
                'status_aplicado' => $this->statusAvaliacao,
                'comentario' => trim($this->comentarioAvaliacao) ?: null,
                'tipo_evento' => 'avaliacao_item'
            ]);
        }

        // Limpa a seleção se a ação foi concluída
        if ($this->modoAvaliacao === 'selecionados') {
            $this->itensSelecionados = [];
            $this->selecionarTudo = false;
        }

        // INTELIGÊNCIA GLOBAL DO ORÇAMENTO
        $temReprovado = $this->orcamento->itens()->whereIn('status', ['Reprovado', 'Aprovado com ressalvas'])->exists();
        $temPendente = $this->orcamento->itens()->whereIn('status', ['Criado', 'Corrigido'])->exists();

        if ($temReprovado) {
            $this->orcamento->update(['status' => 'Em elaboração']);
        } elseif (!$temPendente) {
            $this->orcamento->update(['status' => 'Aprovado']);
        }

        $this->modalAvaliacaoAberto = false;
        $this->dispatch('sucesso', msg: "Avaliação registrada com sucesso!");
        
        $this->orcamento->refresh();
    }

    public function render()
    {
        return view('livewire.financeiro.orcamento-aprovacao-editor');
    }
}