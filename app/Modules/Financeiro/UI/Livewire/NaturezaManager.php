<?php

namespace App\Modules\Financeiro\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use App\Helpers\BreadcrumbHelper;
use App\Modules\Financeiro\Domain\Models\Natureza;
use App\Modules\Financeiro\Domain\Models\Orcamento;

#[Layout('components.layouts.app')]
#[Title('Naturezas - Financeiro')]
class NaturezaManager extends Component
{
    use WithPagination, ComPadraoListagem;

    // Campos do formulário
    public string $codigo = '';
    public string $descricao = '';
    public bool $disponivel_orcamento = true;

    // Controle de estado
    public ?int $naturezaId = null;
    public bool $isEditMode = false;
    public bool $modalAberto = false;

    // Filtros
    public string $filtroCodigo = '';
    public string $filtroDescricao = '';
    public string $filtroDisponivel = '';

    public array $breadcrumbs = [];

    public function mount()
    {
        //abort_if(!feature('financeiro.natureza.listar'), 403, 'Acesso desativado.');
        //abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('financeiro.natureza.listar'), 403);

        $this->breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Módulo Financeiro', 'url' => '#'],
            ['label' => 'Naturezas', 'url' => '#'],
        ];

        $this->ordenacaoCampo = 'codigo';
        $this->ordenacaoDirecao = 'asc';
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtroCodigo', 'filtroDescricao', 'filtroDisponivel'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtroCodigo', 'filtroDescricao', 'filtroDisponivel']);
        $this->resetPage();
    }

    public function abrirModal()
    {
        //abort_if(!feature('financeiro.natureza.criar'), 403);
        //abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('financeiro.natureza.criar'), 403);

        $this->resetInputFields();
        $this->modalAberto = true;
    }

    public function edit(int $id)
    {
        //abort_if(!feature('financeiro.natureza.editar'), 403);
        //abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('financeiro.natureza.editar'), 403);

        $natureza = Natureza::findOrFail($id);
        
        $this->naturezaId = $natureza->id;
        $this->codigo = $natureza->codigo;
        $this->descricao = $natureza->descricao;
        $this->disponivel_orcamento = $natureza->disponivel_orcamento;
        
        $this->isEditMode = true;
        $this->modalAberto = true;
    }

    public function salvar()
    {
        $regras = [
            'codigo' => 'required|string|max:50|unique:naturezas,codigo' . ($this->naturezaId ? ',' . $this->naturezaId : ''),
            'descricao' => 'required|string|max:255',
            'disponivel_orcamento' => 'boolean',
        ];

        $this->validate($regras);

        $dados = [
            'codigo' => trim($this->codigo),
            'descricao' => trim($this->descricao),
            'disponivel_orcamento' => $this->disponivel_orcamento,
        ];

        if ($this->isEditMode) {
            Natureza::findOrFail($this->naturezaId)->update($dados);
            $msg = 'Natureza atualizada com sucesso!';
        } else {
            Natureza::create($dados);
            $msg = 'Natureza criada com sucesso!';
        }

        $this->fecharModal();
        $this->dispatch('sucesso', msg: $msg);
    }

    public function toggleDisponibilidade(int $id)
    {
        //abort_if(!feature('financeiro.natureza.editar'), 403);
        //abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('financeiro.natureza.editar'), 403);

        $natureza = Natureza::findOrFail($id);
        $natureza->disponivel_orcamento = !$natureza->disponivel_orcamento;
        $natureza->save();

        $status = $natureza->disponivel_orcamento ? 'disponibilizada' : 'ocultada';
        $this->dispatch('sucesso', msg: "Natureza {$status} para os orçamentos!");
    }

    public function excluir(int $id)
    {
        //abort_if(!feature('financeiro.natureza.excluir'), 403);
        //abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('financeiro.natureza.excluir'), 403);

        $natureza = Natureza::findOrFail($id);

        // Bloqueio de exclusão: Verifica se já existem orçamentos vinculados a esta Natureza
        $emUso = Orcamento::where('natureza', $natureza->codigo)->exists();
        if ($emUso) {
            $this->dispatch('erro', msg: 'Ação Bloqueada: Esta Natureza possui orçamentos vinculados e não pode ser excluída.');
            return;
        }

        $natureza->delete();
        $this->dispatch('sucesso', msg: 'Natureza excluída com sucesso!');
    }

    public function fecharModal()
    {
        $this->modalAberto = false;
        $this->resetInputFields();
    }

    private function resetInputFields()
    {
        $this->naturezaId = null;
        $this->codigo = '';
        $this->descricao = '';
        $this->disponivel_orcamento = true;
        $this->isEditMode = false;
        $this->resetErrorBag();
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'codigo', 'label' => 'Código (Protheus)', 'sortable' => true],
            ['key' => 'descricao', 'label' => 'Descrição', 'sortable' => true],
            ['key' => 'disponivel_orcamento', 'label' => 'Disponível no Orçamento?', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function render()
    {
        $query = Natureza::query();

        if (!empty($this->filtroCodigo)) {
            $query->where('codigo', 'ilike', '%' . $this->filtroCodigo . '%');
        }
        if (!empty($this->filtroDescricao)) {
            $query->where('descricao', 'ilike', '%' . $this->filtroDescricao . '%');
        }
        if ($this->filtroDisponivel !== '') {
            $query->where('disponivel_orcamento', $this->filtroDisponivel);
        }

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('codigo', 'asc');
        }

        return view('livewire.financeiro.natureza-manager', [
            'registros' => $query->paginate($this->porPagina)
        ]);
    }
}