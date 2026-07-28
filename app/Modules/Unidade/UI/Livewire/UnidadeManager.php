<?php

namespace App\Modules\Unidade\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\Unidade\Application\Services\UnidadeService;
use Livewire\WithPagination;
use Illuminate\Support\Str;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Unidades - Percorre')]
class UnidadeManager extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public bool $isEditMode = false;
    
    // Variáveis atualizadas com a nova base de dados
    public ?int $unidadeId = null;
    public string $nome = '';
    public string $status = 'Ativa';
    public ?string $data_inauguracao = null;
    public string $endereco = '';
    public string $email = '';
    public string $telefone = '';

    public array $cursosSelecionados = [];

    public function mount() 
    { 
        // Mantivemos a sua trava de segurança original
        abort_if(!auth()->user()->can('unidade.listar'), 403); 
    }

    public function openModal() 
    {
        $this->resetInputFields();
        $this->showModal = true;
    }

    public function save(UnidadeService $service) 
    {
        $this->validate([
            'nome' => 'required|string|max:255',
            'endereco' => 'required|string|max:255',
            'status' => 'required|in:Ativa,Inativa',
            'email' => 'nullable|email',
            'telefone' => 'nullable|string|max:20',
            'data_inauguracao' => 'nullable|date',
        ]);

        $dados = [
            'nome' => $this->nome,
            'slug' => \Illuminate\Support\Str::slug($this->nome),
            'endereco' => $this->endereco,
            'status' => $this->status,
            'email' => $this->email,
            'telefone' => $this->telefone,
            'data_inauguracao' => $this->data_inauguracao,
        ];

        if ($this->isEditMode) {
            $service->atualizarUnidade($this->unidadeId, $dados);
            $unidadeId = $this->unidadeId;
        } else {
            $unidadeCriada = $service->criarUnidade($dados);
            $unidadeId = $unidadeCriada->id; // Pega o ID da unidade recém criada
        }

        // Delega a sincronização dos relacionamentos para o Serviço
        $service->sincronizarCursos($unidadeId, $this->cursosSelecionados);

        $this->showModal = false;
        $this->resetInputFields();
        session()->flash('success', $this->isEditMode ? 'Unidade atualizada!' : 'Unidade cadastrada!');
    }

    public function edit(UnidadeService $service, int $id) 
    {
        $this->resetInputFields();
        $unidade = $service->buscarPorId($id);
        $unidade->load('cursos'); // Carrega a relação
        
        $this->unidadeId = $unidade->id;
        $this->nome = $unidade->nome;
        $this->status = $unidade->status;
        $this->data_inauguracao = $unidade->data_inauguracao ? \Carbon\Carbon::parse($unidade->data_inauguracao)->format('Y-m-d') : null;
        $this->endereco = $unidade->endereco;
        $this->email = $unidade->email ?? '';
        $this->telefone = $unidade->telefone ?? '';
        
        // Extrai apenas os IDs dos cursos para preencher os checkboxes no HTML
        $this->cursosSelecionados = $unidade->cursos->pluck('id')->toArray();
        
        $this->isEditMode = true;
        $this->showModal = true;
    }

    public function delete(UnidadeService $service, int $id)
    {
        $service->deletarUnidade($id);
        session()->flash('success', 'Unidade movida para a lixeira.');
    }

    private function resetInputFields() 
    {
        $this->unidadeId = null;
        $this->nome = '';
        $this->status = 'Ativa';
        $this->data_inauguracao = null;
        $this->endereco = '';
        $this->email = '';
        $this->telefone = '';
        $this->cursosSelecionados = []; // <- AQUI
        $this->isEditMode = false;
        $this->resetErrorBag();
    }

    public function render(UnidadeService $service) 
    {
        return view('livewire.unidade.unidade-manager', [
            'unidades' => $service->listarTodos(),
            'cursosDisponiveis' => \App\Models\Curso::where('status', 'Ativo')->orderBy('nome')->get() // Traz os cursos para o form
        ]);
    }
}