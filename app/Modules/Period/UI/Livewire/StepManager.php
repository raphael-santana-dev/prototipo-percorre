<?php

namespace App\Modules\Period\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Ciclo;
use App\Models\Etapa;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use Illuminate\Support\Str;
use App\Helpers\BreadcrumbHelper;

class StepManager extends Component 
{
    use WithPagination; // Habilita a paginação sem recarregar a página
    use ComPadraoListagem; // Traz a ordenação e os registos por página

    public bool $showModal = false;
    public bool $isEditMode = false;
    public ?int $stepId = null;

    // Campos migrados do sistema antigo
    public string $nome = '';
    public ?int $numero = null;
    public string $descricao = '';

    public function mount() 
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403);
    }

    public function openModal()
    {
        $this->resetInputFields();
        
        // Busca o maior número de etapa já cadastrado
        $maiorOrdem = Etapa::max('numero');
        
        // Se já existir alguma etapa, soma 1. Se não existir (null), começa do 2.
        $this->numero = $maiorOrdem ? $maiorOrdem + 1 : 2;
        
        $this->showModal = true;
    }

    public function getHeadersProperty()
    {
        //ID, Ordem, Nome, Status, ações
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'numero', 'label' => 'Ordem', 'sortable' => true],
            ['key' => 'nome', 'label' => 'Nome', 'sortable' => true],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'], // Coluna para ações
        ];
    }

    public function save()
    {
        $this->validate([
            'nome' => 'required|string|max:255',
            'numero' => 'required|integer|min:1',
            'descricao' => 'nullable|string',
        ], [
            'nome.required' => 'O nome da etapa é obrigatório.',
            'numero.required' => 'A ordem de execução é obrigatória.',
        ]);

        $data = [
            'nome' => $this->nome,
            'numero' => $this->numero,
            'descricao' => $this->descricao,
        ];

        if ($this->isEditMode) {
            Etapa::findOrFail($this->stepId)->update($data);
        } else {
            Etapa::create($data);
        }

        $this->showModal = false;
        $this->resetInputFields();
        session()->flash('success', $this->isEditMode ? 'Etapa atualizada com sucesso!' : 'Etapa cadastrada com sucesso!');
    }

    public function delete(int $id)
    {
        $step = Etapa::findOrFail($id);

        // Bloqueio de segurança
        if ($step->numero === 1 && !auth()->user()->hasRole('dev')) {
            session()->flash('error', 'A Etapa 1 é obrigatória para o funcionamento das inscrições e não pode ser excluída.');
            return;
        }

        $step->delete();
        session()->flash('success', 'Etapa excluída com sucesso!');
    }

    private function resetInputFields()
    {
        $this->stepId = null;
        $this->nome = '';
        $this->numero = null;
        $this->descricao = '';
        $this->isEditMode = false;
        $this->resetErrorBag();
    }

    public function edit(int $id)
    {
        $this->resetInputFields();
        $step = Etapa::findOrFail($id);

        // Bloqueio de segurança
        if ($step->numero === 1 && !auth()->user()->hasRole('dev')) {
            session()->flash('error', 'A Etapa 1 é padrão do sistema e só pode ser editada por desenvolvedores.');
            return;
        }
        
        $this->stepId = $step->id;
        $this->nome = $step->nome;
        $this->numero = $step->numero;
        $this->descricao = $step->descricao ?? '';
        
        $this->isEditMode = true;
        $this->showModal = true;
    }

    public function render()
    {
        $query = Etapa::query();

        // Aplica a mágica da ordenação da Trait
        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            // Ordem padrão: pelo número da etapa
            $query->orderBy('numero', 'asc');
        }

        // Usa o $this->porPagina da Trait
        $etapas = $query->paginate($this->porPagina);

        return view('livewire.period.step-manager', [
            'registros' => $etapas,
            'breadcrumbs' => BreadcrumbHelper::generate()
        ])->layout('components.layouts.app', ['title' => 'Gestão de Etapas']);
    }
}