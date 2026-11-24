<?php

namespace App\Modules\Escola\UI\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\Escola\Domain\Models\Escola;
use App\Helpers\BreadcrumbHelper;
use App\Traits\ComPadraoListagem;
use App\Traits\WithToggleStatus;
use App\Traits\ValidaAcesso;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Escolas - Administrativo')]
class EscolaManager extends Component
{
    use WithPagination, ComPadraoListagem, WithToggleStatus, ValidaAcesso {
        WithToggleStatus::toggleStatus as traitToggleStatus;
    }

    public string $modelClass = Escola::class;
    public array $breadcrumbs = [];

    // Filtros
    public string $filtro_busca = '';
    public string $filtro_tipo = '';
    public string $filtro_regiao = '';
    public string $filtro_status = '';

    // Modal CRUD
    public bool $modalAberto = false;
    public ?int $escolaId = null;
    public string $nome = '';
    public string $tipo = 'estadual';
    public string $regiao = '';
    public bool $status = true;

    public function mount(): void
    {
        $this->autorizarTela('escola.listar', 'gerenciamento de escolas');
        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->permiteGrid = true;
    }

    public function updating($nomePropriedade): void
    {
        if (in_array($nomePropriedade, ['filtro_busca', 'filtro_tipo', 'filtro_regiao', 'filtro_status'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros(): void
    {
        $this->reset(['filtro_busca', 'filtro_tipo', 'filtro_regiao', 'filtro_status']);
        $this->resetPage();
    }

    public function abrirModal(?int $id = null): void
    {
        $permissao = $id ? 'escola.editar' : 'escola.criar';
        if (!$this->autorizarAcao($permissao, $id ? 'editar escola' : 'cadastrar escola')) {
            return;
        }

        $this->resetValidation();
        $this->reset(['escolaId', 'nome', 'regiao']);
        $this->tipo = 'estadual';
        $this->status = true;

        if ($id) {
            $escola = Escola::findOrFail($id);
            $this->escolaId = $escola->id;
            $this->nome     = $escola->nome;
            $this->tipo     = $escola->tipo;
            $this->regiao   = $escola->regiao;
            $this->status   = (bool) $escola->status;
        }

        $this->modalAberto = true;
    }

    public function fecharModal(): void
    {
        $this->modalAberto = false;
        $this->resetValidation();
    }

    public function salvar(): void
    {
        $permissao = $this->escolaId ? 'escola.editar' : 'escola.criar';
        if (!$this->autorizarAcao($permissao, 'salvar escola')) {
            return;
        }

        $this->validate([
            'nome'   => 'required|string|min:2|max:255',
            'tipo'   => 'required|in:estadual,municipal',
            'regiao' => 'required|string|min:2|max:120',
            'status' => 'boolean',
        ], [
            'nome.required'   => 'O nome da escola é obrigatório.',
            'tipo.required'   => 'Selecione o tipo da escola.',
            'tipo.in'         => 'O tipo deve ser Estadual ou Municipal.',
            'regiao.required' => 'Informe a região da escola.',
        ]);

        $dados = [
            'nome'   => trim($this->nome),
            'tipo'   => $this->tipo,
            'regiao' => trim($this->regiao),
            'status' => $this->status,
        ];

        if ($this->escolaId) {
            Escola::findOrFail($this->escolaId)->update($dados);
            $msg = 'Escola atualizada com sucesso!';
        } else {
            Escola::create($dados);
            $msg = 'Escola cadastrada com sucesso!';
        }

        $this->fecharModal();
        $this->dispatch('sucesso', msg: $msg);
    }

    public function toggleStatus($id): void
    {
        if (!$this->autorizarAcao('escola.editar', 'alterar status da escola')) {
            return;
        }

        $this->traitToggleStatus($id);
    }

    public function excluir(int $id): void
    {
        if (!$this->autorizarAcao('escola.excluir', 'excluir escola')) {
            return;
        }

        Escola::findOrFail($id)->delete();
        $this->dispatch('sucesso', msg: 'Escola excluída com sucesso!');
    }

    public function getHeadersProperty(): array
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true, 'class' => 'w-16'],
            ['key' => 'nome', 'label' => 'Nome da Escola', 'sortable' => true],
            ['key' => 'tipo', 'label' => 'Tipo', 'sortable' => true, 'class' => 'w-32 text-center'],
            ['key' => 'regiao', 'label' => 'Região', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true, 'class' => 'w-28 text-center'],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'w-28 text-right'],
        ];
    }

    public function render()
    {
        $query = Escola::query()
            ->when($this->filtro_busca !== '', function ($q) {
                $q->where(function ($sub) {
                    $sub->where('nome', 'ilike', '%' . $this->filtro_busca . '%')
                        ->orWhere('regiao', 'ilike', '%' . $this->filtro_busca . '%');
                });
            })
            ->when($this->filtro_tipo !== '', fn($q) => $q->where('tipo', $this->filtro_tipo))
            ->when($this->filtro_regiao !== '', fn($q) => $q->where('regiao', $this->filtro_regiao))
            ->when($this->filtro_status !== '', fn($q) => $q->where('status', (bool) $this->filtro_status));

        $query->orderBy($this->ordenacaoCampo ?: 'nome', $this->ordenacaoDirecao ?: 'asc');

        return view('livewire.escola.escola-manager', [
            'registros'         => $query->paginate($this->porPagina),
            'regioesDisponiveis'=> Escola::select('regiao')->distinct()->orderBy('regiao')->pluck('regiao'),
        ]);
    }
}