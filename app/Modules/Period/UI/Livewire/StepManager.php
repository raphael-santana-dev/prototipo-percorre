<?php

namespace App\Modules\Period\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Ciclo;
use App\Models\Etapa;
use App\Models\CampoFormulario;
use App\Models\Inscricao;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use App\Helpers\BreadcrumbHelper;

class StepManager extends Component 
{
    use WithPagination;
    use ComPadraoListagem;

    public bool $showModal = false;
    public bool $isEditMode = false;
    public ?int $stepId = null;
    public bool $isInUse = false; // <-- Nova propriedade

    // Campos
    public string $nome = '';
    public ?int $numero = null;
    public string $descricao = '';

    public array $breadcrumbs = [];
    public $filtro_busca = '';

    public function mount() 
    {
        abort_if(!feature('etapa.listar'), 403, 'Módulo de etapas desativado.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('etapa.listar'), 403, 'Acesso restrito.');

        $this->breadcrumbs = BreadcrumbHelper::generate();
    }

    public function updating($nomePropriedade) { if ($nomePropriedade === 'filtro_busca') $this->resetPage(); }
    
    public function limparFiltros() {
        $this->reset(['filtro_busca']);
        $this->resetPage();
    }

    public function openModal()
    {
        abort_if(!feature('etapa.criar'), 403, 'Criação de etapas desativada.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('etapa.criar'), 403, 'Acesso restrito.');

        $this->resetInputFields();
        $maiorOrdem = Etapa::max('numero');
        $this->numero = $maiorOrdem ? $maiorOrdem + 1 : 2;
        $this->showModal = true;
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'numero', 'label' => 'Ordem', 'sortable' => true],
            ['key' => 'nome', 'label' => 'Nome', 'sortable' => true],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function save()
    {
        if ($this->isEditMode) {
            abort_if(!feature('etapa.editar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('etapa.editar'), 403);
            
            if ($this->isInUse) {
                $etapaAntiga = Etapa::findOrFail($this->stepId);
                $this->nome = $etapaAntiga->nome; 
                $this->numero = $etapaAntiga->numero; 
            }
        } else {
            abort_if(!feature('etapa.criar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('etapa.criar'), 403);
        }

        $this->validate([
            'nome' => 'required|string|max:255',
            'numero' => 'required|integer|min:1',
            'descricao' => 'nullable|string',
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
        $this->dispatch('sucesso', msg: $this->isEditMode ? 'Etapa atualizada com sucesso!' : 'Etapa cadastrada com sucesso!');
    }

    public function delete(int $id)
    {
        abort_if(!feature('etapa.excluir'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('etapa.excluir'), 403);

        $step = Etapa::findOrFail($id);

        if ($step->numero === 1) {
            $this->dispatch('erro', msg:  'A Etapa 1 é obrigatória para o funcionamento e não pode ser excluída.');
            return;
        }

        $emUso = Inscricao::where('etapa_atual', $step->numero)->exists() || CampoFormulario::where('etapa', $step->numero)->exists();
        
        if ($emUso) {
            $this->dispatch('erro', msg: 'Ação Bloqueada: Esta etapa possui campos de formulário construídos nela ou inscrições vinculadas. Não é possível excluí-la.');
            return;
        }

        $step->delete();
        $this->dispatch('sucesso', msg: 'Etapa excluída com sucesso!');
    }

    private function resetInputFields()
    {
        $this->stepId = null;
        $this->nome = '';
        $this->numero = null;
        $this->descricao = '';
        $this->isEditMode = false;
        $this->isInUse = false;
        $this->resetErrorBag();
    }

    public function edit(int $id)
    {
        abort_if(!feature('etapa.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('etapa.editar'), 403);
        
        $this->resetInputFields();
        $step = Etapa::findOrFail($id);

        if ($step->numero === 1 && !auth()->user()->hasRole('dev')) {
            $this->dispatch('erro', msg:  'A Etapa 1 é padrão do sistema e só pode ser editada por desenvolvedores.');
            return;
        }
        
        $this->stepId = $step->id;
        $this->nome = $step->nome;
        $this->numero = $step->numero;
        $this->descricao = $step->descricao ?? '';
        $this->isEditMode = true;
        
        // Verifica o uso real em banco
        $this->isInUse = Inscricao::where('etapa_atual', $step->numero)->exists() || CampoFormulario::where('etapa', $step->numero)->exists();
        
        $this->showModal = true;
    }

    public function render()
    {
        $query = Etapa::query();

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('numero', 'asc');
        }

        $etapas = $query->paginate($this->porPagina);

        return view('livewire.period.step-manager', [
            'registros' => $etapas,
        ])->layout('components.layouts.app', ['title' => 'Gestão de Etapas']);
    }
}