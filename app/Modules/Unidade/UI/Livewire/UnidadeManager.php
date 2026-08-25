<?php

namespace App\Modules\Unidade\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\Unidade\Application\Services\UnidadeService;
use App\Modules\Unidade\Domain\Models\Unidade;
use Illuminate\Support\Str;

use App\Traits\WithCepConsulta;
use Livewire\WithPagination;
use App\Helpers\BreadcrumbHelper;
use App\Traits\ComPadraoListagem;
use App\Traits\WithToggleStatus;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Unidades - Percorre')]
class UnidadeManager extends Component
{
    use WithPagination;
    use ComPadraoListagem;
    use WithToggleStatus;
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

     public $modelClass = Ciclo::class;

    public array $breadcrumbs = [];


    public function mount() 
    { 
        abort_if(!feature('unidade.listar'), 403, 'Módulo desativado.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('unidade.listar'), 403);

        $this->breadcrumbs = BreadcrumbHelper::generate();

        $this->permiteGrid = true;
    }

    public function openModal() 
    {
        abort_if(!feature('unidade.criar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('unidade.criar'), 403);
        $this->resetInputFields();
        $this->showModal = true;
    }

    public function save(UnidadeService $service) 
    {
        if ($this->isEditMode) {
            abort_if(!feature('unidade.editar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('unidade.editar'), 403);
        } else {
            abort_if(!feature('unidade.criar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('unidade.criar'), 403);
        }

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
        $this->dispatch('sucesso', msg: $this->isEditMode ? 'Unidade atualizada!' : 'Unidade cadastrada!');
    }

    public function edit(UnidadeService $service, int $id) 
    {
        abort_if(!feature('unidade.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('unidade.editar'), 403);

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
        abort_if(!feature('unidade.excluir'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('unidade.excluir'), 403);

        $service->deletarUnidade($id);
        $this->dispatch('sucesso', msg: 'Unidade movida para a lixeira.');
    }

    private function resetInputFields() 
    {
        $this->reset(['unidadeId', 'nome', 'data_inauguracao', 'email', 'telefone', 'cursosSelecionados', 'isEditMode', 'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'estado']);
        $this->status = 'Ativa';
        $this->resetErrorBag();
    }

    public function showQuickView(UnidadeService $service, int $id)
    {
        abort_if(!feature('unidade.visualizar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('unidade.visualizar'), 403);
        
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

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'nome', 'label' => 'Unidade', 'sortable' => true],
            ['key' => 'telefone', 'label' => 'Contato', 'sortable' => false],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function toggleStatus($id)
    {
        abort_if(!feature('unidade.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('unidade.editar'), 403);
        
        $unidade = \App\Modules\Unidade\Domain\Models\Unidade::findOrFail($id);
        
        $unidade->update([
            'status' => $unidade->status === 'Ativa' ? 'Inativa' : 'Ativa'
        ]);

        $this->dispatch('sucesso', msg: 'Status da unidade atualizado!');
    }

    public function render(UnidadeService $service) 
    {
        // 1. LEITURA NO LIVEWIRE (CQRS): Consulta direta para permitir paginação nativa
        $query = Unidade::query();

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('nome', 'asc');
        }

        $unidades = $query->paginate($this->porPagina);

        return view('livewire.unidade.unidade-manager', [
            'registros' => $unidades, // Passa os registros paginados para a view
            'cursosDisponiveis' => \App\Models\Curso::whereIn('status', ['Ativo', 'ativo', '1', 1, true])->orderBy('nome')->get() 
        ]);
    }
}