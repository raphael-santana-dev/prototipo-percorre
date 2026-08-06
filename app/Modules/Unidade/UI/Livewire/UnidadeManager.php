<?php

namespace App\Modules\Unidade\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\Unidade\Application\Services\UnidadeService;
use Livewire\WithPagination;
use Illuminate\Support\Str;
use App\Traits\WithCepConsulta;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Unidades - Percorre')]
class UnidadeManager extends Component
{
    use WithPagination;
    use WithCepConsulta;

    public bool $showModal = false;
    public bool $isEditMode = false;
    
    // Variáveis atualizadas com a nova base de dados
    public ?int $unidadeId = null;
    public string $nome = '';
    public string $status = 'Ativa';
    public ?string $data_inauguracao = null;
    public $cep, $logradouro, $numero, $complemento, $bairro, $cidade, $estado;
    public string $endereco = '';
    public string $email = '';
    public string $telefone = '';

    public array $cursosSelecionados = [];

    public function mount() 
    { 
        // Mantivemos a sua trava de segurança original
        // abort_if(!auth()->user()->can('unidade.listar'), 403); 
        abort_if(!auth()->user()->hasRole('dev|admin'), 403);
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
            'cep' => 'required|string|max:10',
            'estado' => 'required|string|size:2',
            'cidade' => 'required|string|max:255',
            'status' => 'required|in:Ativa,Inativa',
            'email' => 'nullable|email',
            'telefone' => 'nullable|string|max:20',
            'data_inauguracao' => 'nullable|date',
        ]);

        $enderecoCompleto = "{$this->logradouro}, {$this->numero}" . ($this->complemento ? " - {$this->complemento}" : "") . " - {$this->bairro}, {$this->cidade}/{$this->estado}";

        $dados = [
            'nome' => $this->nome,
            'slug' => Str::slug($this->nome),
            'status' => $this->status,
            'email' => $this->email,
            'telefone' => $this->telefone,
            'data_inauguracao' => $this->data_inauguracao,
            // Campos de endereço
            'cep' => $this->cep,
            'logradouro' => $this->logradouro,
            'numero' => $this->numero,
            'complemento' => $this->complemento,
            'bairro' => $this->bairro,
            'cidade' => $this->cidade,
            'estado' => $this->estado,
            'endereco' => $enderecoCompleto,
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
        $unidade->load('cursos'); 
        
        $this->unidadeId = $unidade->id;
        $this->nome = $unidade->nome;
        $this->status = $unidade->status;
        $this->data_inauguracao = $unidade->data_inauguracao ? \Carbon\Carbon::parse($unidade->data_inauguracao)->format('Y-m-d') : null;
        $this->email = $unidade->email ?? '';
        $this->telefone = $unidade->telefone ?? '';
        
        // Povoando os campos de endereço
        $this->cep = $unidade->cep;
        $this->logradouro = $unidade->logradouro;
        $this->numero = $unidade->numero;
        $this->complemento = $unidade->complemento;
        $this->bairro = $unidade->bairro;
        $this->cidade = $unidade->cidade;
        $this->estado = $unidade->estado;
        
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
        $this->reset(['unidadeId', 'nome', 'data_inauguracao', 'email', 'telefone', 'cursosSelecionados', 'isEditMode', 'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'estado']);
        $this->status = 'Ativa';
        $this->resetErrorBag();
    }

    public function showQuickView(UnidadeService $service, int $id)
    {
        $unidade = $service->buscarPorId($id);
        $unidade->load('cursos'); // Carrega a quantidade de cursos

        // Dispara o evento passando o array exato que o seu QuickViewDrawer espera
        $this->dispatch('load-quick-view', [
            'title' => $unidade->nome,
            'subtitle' => 'Status: ' . $unidade->status,
            'icon' => 'ph-buildings',
            'data' => [
                'Endereço' => $unidade->endereco,
                'E-mail' => $unidade->email ?: 'Não informado',
                'Telefone' => $unidade->telefone ?: 'Não informado',
                'Cursos Ofertados' => $unidade->cursos->count() . ' cursos vinculados',
                // Como sua view renderiza HTML ({!! $value !!}), podemos passar o link direto!
                'Mais Detalhes' => '<a href="'.route('unidades.show', $unidade->id).'" class="font-bold text-purpura-600 hover:underline">Ver Página Completa</a>'
            ]
        ]);
    }

    public function render(UnidadeService $service) 
    {
        return view('livewire.unidade.unidade-manager', [
            'unidades' => $service->listarTodos(),
            'cursosDisponiveis' => \App\Models\Curso::whereIn('status', ['Ativo', 'ativo', '1', 1, true])->orderBy('nome')->get() 
        ]);
    }
}