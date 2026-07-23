<?php
namespace App\Modules\Unidade\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\Unidade\Application\Services\UnidadeService;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Unidades - Percorre')]
class UnidadeManager extends Component
{
    public bool $showModal = false;
    public bool $isEditMode = false;
    
    public ?int $unidadeId = null;
    public string $nome = '';
    public string $endereco = '';
    public string $email = '';
    public string $contatos = '';
    public bool $status = true;

    public function mount() { abort_if(!auth()->user()->can('unidade.listar'), 403); }

    public function openModal() {
        $this->resetInputFields();
        $this->showModal = true;
    }

    public function save(UnidadeService $service) {
        $this->validate([
            'nome' => 'required|string|max:255',
            'email' => 'nullable|email',
        ]);

        $dados = ['nome' => $this->nome, 'endereco' => $this->endereco, 'email' => $this->email, 'contatos' => $this->contatos, 'status' => $this->status];

        if ($this->isEditMode) {
            $service->atualizarUnidade($this->unidadeId, $dados);
        } else {
            $service->criarUnidade($dados);
        }

        $this->showModal = false;
        session()->flash('success', 'Unidade salva!');
    }

    public function edit(UnidadeService $service, int $id) {
        $unidade = $service->buscarPorId($id);
        $this->unidadeId = $unidade->id;
        $this->nome = $unidade->nome;
        $this->endereco = $unidade->endereco;
        $this->email = $unidade->email;
        $this->contatos = $unidade->contatos;
        $this->status = $unidade->status;
        
        $this->isEditMode = true;
        $this->showModal = true;
    }

    private function resetInputFields() {
        $this->reset(['unidadeId', 'nome', 'endereco', 'email', 'contatos', 'isEditMode']);
        $this->status = true;
    }

    public function render(UnidadeService $service) {
        return view('livewire.unidade.unidade-manager', [
            'unidades' => $service->listarTodos()
        ]);
    }
}