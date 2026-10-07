<?php

namespace App\Modules\Financeiro\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use App\Helpers\BreadcrumbHelper;
use App\Modules\Financeiro\Domain\Models\CentroCusto;
use App\Modules\Financeiro\Domain\Models\Orcamento;

#[Layout('components.layouts.app')]
#[Title('Centros de Custo - Financeiro')]
class CentroCustoManager extends Component
{
    use WithPagination, ComPadraoListagem;

    // Campos do formulário
    public string $codigo = '';
    public string $nome = '';
    public bool $disponivel_orcamento = true;

    // Controle de estado
    public ?int $centroCustoId = null;
    public bool $isEditMode = false;
    public bool $modalAberto = false;

    // Filtros
    public string $filtroCodigo = '';
    public string $filtroNome = '';
    public string $filtroDisponivel = '';

    public array $breadcrumbs = [];

    public function mount()
    {
        // abort_if(!feature('financeiro.centro_custo.listar'), 403, 'Acesso desativado.');
        // abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('financeiro.centro_custo.listar'), 403);

        $this->breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Módulo Financeiro', 'url' => '#'],
            ['label' => 'Centros de Custo', 'url' => '#'],
        ];

        $this->ordenacaoCampo = 'codigo';
        $this->ordenacaoDirecao = 'asc';
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtroCodigo', 'filtroNome', 'filtroDisponivel'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtroCodigo', 'filtroNome', 'filtroDisponivel']);
        $this->resetPage();
    }

    public function abrirModal()
    {
        // abort_if(!feature('financeiro.centro_custo.criar'), 403);
        // abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('financeiro.centro_custo.criar'), 403);

        $this->resetInputFields();
        $this->modalAberto = true;
    }

    public function edit(int $id)
    {
        // abort_if(!feature('financeiro.centro_custo.editar'), 403);
        // abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('financeiro.centro_custo.editar'), 403);

        $centro = CentroCusto::findOrFail($id);
        
        $this->centroCustoId = $centro->id;
        $this->codigo = $centro->codigo;
        $this->nome = $centro->nome;
        $this->disponivel_orcamento = $centro->disponivel_orcamento;
        
        $this->isEditMode = true;
        $this->modalAberto = true;
    }

    public function salvar()
    {
        $regras = [
            'codigo' => 'required|string|max:50|unique:centros_custo,codigo' . ($this->centroCustoId ? ',' . $this->centroCustoId : ''),
            'nome' => 'required|string|max:255',
            'disponivel_orcamento' => 'boolean',
        ];

        $this->validate($regras);

        $dados = [
            'codigo' => trim($this->codigo),
            'nome' => trim($this->nome),
            'disponivel_orcamento' => $this->disponivel_orcamento,
        ];

        if ($this->isEditMode) {
            CentroCusto::findOrFail($this->centroCustoId)->update($dados);
            $msg = 'Centro de Custo atualizado com sucesso!';
        } else {
            CentroCusto::create($dados);
            $msg = 'Centro de Custo criado com sucesso!';
        }

        $this->fecharModal();
        $this->dispatch('sucesso', msg: $msg);
    }

    public function toggleDisponibilidade(int $id)
    {
        // abort_if(!feature('financeiro.centro_custo.editar'), 403);
        // abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('financeiro.centro_custo.editar'), 403);

        $centro = CentroCusto::findOrFail($id);
        $centro->disponivel_orcamento = !$centro->disponivel_orcamento;
        $centro->save();

        $status = $centro->disponivel_orcamento ? 'disponibilizado' : 'ocultado';
        $this->dispatch('sucesso', msg: "Centro de Custo {$status} para novos orçamentos!");
    }

    public function excluir(int $id)
    {
        // abort_if(!feature('financeiro.centro_custo.excluir'), 403);
        // abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('financeiro.centro_custo.excluir'), 403);

        $centro = CentroCusto::findOrFail($id);

        // Bloqueio de exclusão: Verifica se já existem orçamentos vinculados a este Centro de Custo
        $emUso = Orcamento::where('ccusto', $centro->codigo)->exists();
        if ($emUso) {
            $this->dispatch('erro', msg: 'Ação Bloqueada: Este Centro de Custo possui orçamentos vinculados e não pode ser excluído.');
            return;
        }

        $centro->delete();
        $this->dispatch('sucesso', msg: 'Centro de Custo excluído com sucesso!');
    }

    public function fecharModal()
    {
        $this->modalAberto = false;
        $this->resetInputFields();
    }

    private function resetInputFields()
    {
        $this->centroCustoId = null;
        $this->codigo = '';
        $this->nome = '';
        $this->disponivel_orcamento = true;
        $this->isEditMode = false;
        $this->resetErrorBag();
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'codigo', 'label' => 'Código (Protheus)', 'sortable' => true],
            ['key' => 'nome', 'label' => 'Nome / Descrição', 'sortable' => true],
            ['key' => 'disponivel_orcamento', 'label' => 'Visível no Orçamento?', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function render()
    {
        $query = CentroCusto::query();

        if (!empty($this->filtroCodigo)) {
            $query->where('codigo', 'ilike', '%' . $this->filtroCodigo . '%');
        }
        if (!empty($this->filtroNome)) {
            $query->where('nome', 'ilike', '%' . $this->filtroNome . '%');
        }
        if ($this->filtroDisponivel !== '') {
            $query->where('disponivel_orcamento', $this->filtroDisponivel);
        }

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('codigo', 'asc');
        }

        return view('livewire.financeiro.centro-custo-manager', [
            'registros' => $query->paginate($this->porPagina)
        ]);
    }
}