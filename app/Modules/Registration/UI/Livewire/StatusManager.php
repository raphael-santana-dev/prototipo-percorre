<?php

namespace App\Modules\Registration\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\StatusInscricao;
use App\Models\Inscricao;

use Livewire\WithPagination;
use App\Helpers\BreadcrumbHelper;
use App\Traits\ComPadraoListagem;
use App\Traits\WithToggleStatus;

use Illuminate\Support\Str;

#[Layout('components.layouts.app')]
#[Title('Status de Inscrição - Administrativo')]
class StatusManager extends Component
{
    use WithPagination;
    use ComPadraoListagem;
    use WithToggleStatus;

    public bool $showModal = false;
    public bool $isEditMode = false;
    public ?int $statusId = null;
    public bool $isInUse = false; // <-- Nova propriedade

    public string $nome = '';
    public string $descricao = '';

    public string $cor = '#9CA3AF';

    public $modelClass = StatusInscricao::class;

    public array $breadcrumbs = [];

    public function mount()
    {
        abort_if(!feature('status.listar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('status.listar'), 403);

        $this->breadcrumbs = BreadcrumbHelper::generate();
    }

    public function openModal()
    {
        abort_if(!feature('status.criar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('status.criar'), 403);

        $this->resetInputFields();
        $this->showModal = true;
    }

    public function edit(int $id)
    {
        abort_if(!feature('status.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('status.editar'), 403);

        $this->resetInputFields();
        $status = StatusInscricao::findOrFail($id);
        
        $this->statusId = $status->id;
        $this->nome = $status->nome;
        $this->descricao = $status->descricao ?? '';
        $this->cor = $status->cor ?? '#9CA3AF';
        $this->isEditMode = true;
        
        // Verifica se há alguma inscrição usando este status
        $this->isInUse = Inscricao::where('status_inscricao_id', $id)->exists();
        
        $this->showModal = true;
    }

    public function save()
    {
        if ($this->isEditMode) {
            abort_if(!feature('status.editar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('status.editar'), 403);
            
            // Trava de segurança no Back-end: Se estiver em uso, ignora alteração no nome
            if ($this->isInUse) {
                $statusAntigo = StatusInscricao::findOrFail($this->statusId);
                $this->nome = $statusAntigo->nome; 
            }
        } else {
            abort_if(!feature('status.criar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('status.criar'), 403);
        }

        $this->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'cor' => 'required|string',
        ]);

        $data = [
            'nome' => $this->nome,
            'slug' => Str::slug($this->nome),
            'descricao' => $this->descricao,
            'cor' => $this->cor,
        ];

        if ($this->isEditMode) {
            StatusInscricao::findOrFail($this->statusId)->update($data);
        } else {
            StatusInscricao::create($data);
        }

        $this->showModal = false;
        $this->resetInputFields();
        $this->dispatch('sucesso', msg: $this->isEditMode ? 'Status atualizado com sucesso!' : 'Status cadastrado com sucesso!');
    }

    public function delete(int $id)
    {
        abort_if(!feature('status.excluir'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('status.excluir'), 403);

        $emUso = Inscricao::where('status_inscricao_id', $id)->exists();
        if ($emUso) {
            $this->dispatch('erro', msg: 'Ação Bloqueada: Existem inscrições vinculadas a este status. Você não pode excluí-lo.');
            return;
        }

        StatusInscricao::findOrFail($id)->delete();
        $this->dispatch('sucesso', msg: 'Status excluído com sucesso!');
    }

    private function resetInputFields()
    {
        $this->statusId = null;
        $this->nome = '';
        $this->descricao = '';
        $this->isEditMode = false;
        $this->isInUse = false;
        $this->cor = '#9CA3AF';
        $this->resetErrorBag();
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'nome', 'label' => 'Nome do Status', 'sortable' => true],
            ['key' => 'descricao', 'label' => 'Descrição', 'sortable' => true],
            ['key' => 'cor', 'label' => 'Cor', 'sortable' => false],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function render()
    {
        $query = StatusInscricao::query();

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('id', 'desc');
        }
        
        $statusInscricao = $query->paginate($this->porPagina);

        return view('livewire.registration.status-manager', [
            'registros' => $statusInscricao
        ]);
    }
}