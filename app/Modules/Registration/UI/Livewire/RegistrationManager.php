<?php

namespace App\Modules\Registration\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Inscricao;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use Illuminate\Support\Str;
use App\Helpers\BreadcrumbHelper;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Inscrições - Administrativo')]
class RegistrationManager extends Component
{
    public bool $showModal = false;

    use WithPagination;
    use ComPadraoListagem;

    // Filtros
    public $filtroNome = '';
    public $filtroStatus = '';
    public $filtroCiclo = ''; 
    public $filtroUnidade = '';
    public $filtroTurno = '';
    public $filtroCurso = '';

    public $inscricaoSelecionada = null;

    // Variáveis de Lote (Etapa 2)
    public array $selecionadas = []; 
    public bool $modalLoteAberto = false;
    public $novoStatusId = '';
    
    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403);
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtroUnidade', 'filtroTurno', 'filtroCurso', 'filtroCiclo', 'filtroNome', 'filtroStatus'])) {
            $this->resetPage();
            $this->desmarcarTodas(); // Limpa as seleções em lote se mudar de página/filtro
        }
    }

    protected function obterQueryFiltrada()
    {
        $query = Inscricao::with(['curso', 'unidade', 'turno', 'ciclo', 'statusInscricao']);
        
        // Aplicação dos Filtros Dinâmicos
        if (!empty($this->filtroNome)) {
            $query->where(function($q) {
                // Busca tanto por nome quanto por CPF
                $q->where('nome', 'ilike', '%' . $this->filtroNome . '%')
                  ->orWhere('cpf', 'like', '%' . $this->filtroNome . '%');
            });
        }
        if (!empty($this->filtroStatus)) $query->where('status_inscricao_id', $this->filtroStatus);
        if (!empty($this->filtroUnidade)) $query->where('unidade_id', $this->filtroUnidade);
        if (!empty($this->filtroTurno)) $query->where('turno_id', $this->filtroTurno);
        if (!empty($this->filtroCurso)) $query->where('curso_id', $this->filtroCurso);
        if (!empty($this->filtroCiclo)) $query->where('ciclo_id', $this->filtroCiclo);

        return $query; 
    }

    // ==========================================
    // MÉTODOS DE AÇÃO EM LOTE (ETAPA 2)
    // ==========================================
    public function selecionarQuantidade($quantidade)
    {
        $this->selecionadas = $this->obterQueryFiltrada()
            ->limit($quantidade)
            ->pluck('id')
            ->map(fn($id) => (string) $id) 
            ->toArray();
    }

    public function desmarcarTodas()
    {
        $this->selecionadas = [];
    }

    public function abrirModalLote()
    {
        if (count($this->selecionadas) === 0) return;
        $this->novoStatusId = '';
        $this->modalLoteAberto = true;
    }

    public function salvarStatusEmLote()
    {
        $this->validate([
            'novoStatusId' => 'required',
            'selecionadas' => 'required|array|min:1'
        ], [
            'novoStatusId.required' => 'Você precisa escolher o novo status.',
        ]);

        // Faz o UPDATE massivo em uma única instrução SQL (Alta Performance)
        Inscricao::whereIn('id', $this->selecionadas)->update([
            'status_inscricao_id' => $this->novoStatusId
        ]);

        $this->modalLoteAberto = false;
        $this->desmarcarTodas(); 
        
        // Dispara o alerta verde (toast) na tela
        $this->dispatch('sucesso', msg: 'Status alterado em lote com sucesso!');
    }

    public function alterarStatusLoteRapido($statusId)
    {
        if (count($this->selecionadas) === 0) return;

        Inscricao::whereIn('id', $this->selecionadas)->update([
            'status_inscricao_id' => $statusId
        ]);

        $this->desmarcarTodas();
        $this->dispatch('sucesso', msg: 'Status alterado rapidamente com sucesso!');
    }

    // ==========================================
    // CONFIGURAÇÃO DA TABELA
    // ==========================================
    public function getHeadersProperty()
    {
        return [
            ['key' => 'checkbox', 'label' => '', 'sortable' => false, 'class' => 'w-10 text-center'], // <- NOVA COLUNA PARA O LOTE
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'nome', 'label' => 'Candidato', 'sortable' => true],
            ['key' => 'curso_id', 'label' => 'Curso', 'sortable' => false],
            ['key' => 'etapa_atual', 'label' => 'Etapa', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => false],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function render()
    {
        $queryBase = $this->obterQueryFiltrada();
        $inscricoes = $queryBase->paginate($this->porPagina);

        if ($this->ordenacaoCampo) {
            $queryBase->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $queryBase->orderBy('id', 'desc');
        }

        $metricas = [
            [
                'label' => 'Total',
                'value' => (clone $queryBase)->count(),
                'color_text' => 'text-blue-600 dark:text-blue-400',
                'color_bg' => 'bg-blue-100 dark:bg-blue-900/30',
            ],
            [
                'label' => 'Aprovados',
                'value' => (clone $queryBase)
                    ->whereHas('statusInscricao', fn ($q) => $q->where('nome', 'Aprovado'))
                    ->count(),
                'color_text' => 'text-green-600 dark:text-green-400',
                'color_bg' => 'bg-green-100 dark:bg-green-900/30',
            ],
            [
                'label' => 'Reprovados',
                'value' => (clone $queryBase)
                    ->whereHas('statusInscricao', fn ($q) => $q->where('nome', 'Reprovado'))
                    ->count(),
                'color_text' => 'text-red-600 dark:text-red-400',
                'color_bg' => 'bg-red-100 dark:bg-red-900/30',
            ],
            [
                'label' => 'Pendentes',
                'value' => (clone $queryBase)
                    ->whereHas('statusInscricao', fn ($q) => $q->whereNotIn('nome', ['Aprovado', 'Reprovado']))
                    ->count(),
                'color_text' => 'text-yellow-600 dark:text-yellow-400',
                'color_bg' => 'bg-yellow-100 dark:bg-yellow-900/30',
            ],
        ];

        return view('livewire.registration.registration-manager', [
            'registros' => $inscricoes,
            'metricas' => $metricas,
            'breadcrumbs' => BreadcrumbHelper::generate(),
            
            // Dados para os Selects
            'statusInscricoesDb' => \App\Models\StatusInscricao::orderBy('nome')->get(),
            'ciclosDb' => \App\Models\Ciclo::orderBy('id', 'desc')->get(),
            'unidadesDb' => \App\Modules\Unidade\Domain\Models\Unidade::whereIn('status', ['Ativa', '1', true])->get(),
            'turnosDb' => \App\Modules\Turno\Domain\Models\Turno::orderBy('nome')->get(),
            'cursosDb' => \App\Models\Curso::whereIn('status', ['Ativo', '1', true])->get(),
        ]);
    }
}