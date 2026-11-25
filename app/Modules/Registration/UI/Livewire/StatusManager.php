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
use App\Traits\ValidaAcesso;

use Illuminate\Support\Str;

#[Layout('components.layouts.app')]
#[Title('Status de Inscrição - Administrativo')]
class StatusManager extends Component
{
    use WithPagination;
    use ComPadraoListagem;
    use WithToggleStatus;
    use ValidaAcesso;

    public bool $showModal = false;
    public bool $isEditMode = false;
    public ?int $statusId = null;
    public bool $isInUse = false; 

    public string $nome = '';
    public string $titulo_amigavel = '';
    public bool $visivel_estudante = true;
    public string $descricao = '';

    public string $cor = '#9CA3AF';

    public $modelClass = StatusInscricao::class;

    public array $breadcrumbs = [];

    public function mount()
    {
        $this->autorizarTela('secretaria.status_inscricao.listar', 'status de inscrição');

        $this->breadcrumbs = BreadcrumbHelper::generate();
    }

    public function openModal()
    {
        if (!$this->autorizarAcao('secretaria.status_inscricao.criar', 'criar novo status de inscrição')) {
            return;
        }

        $this->resetInputFields();
        $this->showModal = true;
    }

    public function toggleVisibilidade($id)
    {
        if (!$this->autorizarAcao('secretaria.status_inscricao.status', 'alterar visibilidade do status')) {
            return;
        }

        $status = StatusInscricao::findOrFail($id);
        $status->visivel_estudante = !$status->visivel_estudante;
        $status->save();
        
        $this->dispatch('sucesso', msg: $status->visivel_estudante ? 'Status Ativado!' : 'Status Desativado!');
    }

    public function edit(int $id)
    {
        if (!$this->autorizarAcao('secretaria.status_inscricao.editar', 'editar status de inscrição')) {
            return;
        }

        $this->resetInputFields();
        $status = StatusInscricao::findOrFail($id);
        
        $this->statusId = $status->id;
        $this->nome = $status->nome;
        $this->descricao = $status->descricao ?? '';
        $this->cor = $status->cor ?? '#9CA3AF';
        $this->isEditMode = true;
        $this->titulo_amigavel = $status->titulo_amigavel ?? '';
        $this->visivel_estudante = (bool) ($status->visivel_estudante ?? true);
        
        $this->isInUse = Inscricao::where('status_inscricao_id', $id)->exists();
        
        $this->showModal = true;
    }

    public function save()
    {
        if ($this->isEditMode) {
            if (!$this->autorizarAcao('secretaria.status_inscricao.editar', 'salvar edição do status de inscrição')) {
                return;
            }
            
            if ($this->isInUse) {
                $statusAntigo = StatusInscricao::findOrFail($this->statusId);
                $this->nome = $statusAntigo->nome; 
            }
        } else {
            if (!$this->autorizarAcao('secretaria.status_inscricao.criar', 'cadastrar novo status de inscrição')) {
                return;
            }
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
            'titulo_amigavel' => $this->titulo_amigavel,
            'visivel_estudante' => $this->visivel_estudante,
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
        if (!$this->autorizarAcao('secretaria.status_inscricao.excluir', 'excluir status de inscrição')) {
            return;
        }

        // Verifica na base de dados (incluindo a lixeira com withTrashed)
        $emUso = Inscricao::withTrashed()->where('status_inscricao_id', $id)->exists();
        if ($emUso) {
            $this->dispatch('erro', msg: 'Ação Bloqueada: Existem inscrições ativas ou arquivadas vinculadas a este status. O sistema não pode excluí-lo.');
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
            ['key' => 'titulo_amigavel', 'label' => 'Titulo Amigável', 'sortable' => true],
            ['key' => 'visivel_estudante', 'label' => 'Visível para estudante', 'sortable' => true],
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