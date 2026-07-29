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

    public $filtroCiclo = ''; 
    public $filtroUnidade = '';
    public $filtroTurno = '';
    public $filtroCurso = '';

    public $inscricaoSelecionada = null;

    public $selecionadas = []; 
    public $modalLoteAberto = false;
    public $novoStatusId = '';
    
    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev'), 403, 'Acesso restrito a Desenvolvedores.');
    }

    // Isola a Query para que a tabela e a seleção em lote usem EXATAMENTE os mesmos filtros
    protected function obterQueryFiltrada()
    {
        $query = \App\Models\Inscricao::with(['curso', 'unidade', 'turno', 'ciclo', 'statusInscricao']);
        $usuario = Auth::user();

        $isAdministrador = $usuario->hasRole('dev|dev');
        // $temPermissaoEspecial = $usuario->hasPermissionTo('visualizar todas inscricoes');
        // $podeAvaliarGlobal = $usuario->hasPermissionTo('avaliar turmas globais');

        if (!$isAdministrador && !$temPermissaoEspecial && !$podeAvaliarGlobal) {
            $extras = $usuario->acessos_extras ?? [];
            $unidadesPermitidas = array_unique(array_filter(array_merge([$usuario->unidade_id], $extras['unidades'] ?? [])));
            $cursosPermitidos = array_unique(array_filter(array_merge([$usuario->curso_id], $extras['cursos'] ?? [])));
            $turnosPermitidos = array_unique(array_filter(array_merge([$usuario->turno_id], $extras['turnos'] ?? [])));

            $query->whereIn('unidade_id', $unidadesPermitidas)
                  ->whereIn('curso_id', $cursosPermitidos)
                  ->whereIn('turno_id', $turnosPermitidos);
        } else {
            if (!empty($this->filtroUnidade)) $query->where('unidade_id', $this->filtroUnidade);
            if (!empty($this->filtroTurno)) $query->where('turno_id', $this->filtroTurno);
            if (!empty($this->filtroCurso)) $query->where('curso_id', $this->filtroCurso);
            if (!empty($this->filtroCiclo)) $query->where('ciclo_id', $this->filtroCiclo);
        }

        return $query; // Devolvemos a query "limpa" para podermos calcular os cards e ordenar depois
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'nome', 'label' => 'Candidato', 'sortable' => true],
            ['key' => 'curso_id', 'label' => 'Curso', 'sortable' => false],
            ['key' => 'etapa_atual', 'label' => 'Etapa', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => false],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function updating($nomePropriedade)
    {
        // Se a propriedade alterada for algum dos nossos filtros, voltamos para a página 1 e limpamos seleções
        if (in_array($nomePropriedade, ['filtroUnidade', 'filtroTurno', 'filtroCurso', 'filtroCiclo'])) {
            $this->resetPage();
            $this->selecionadas = []; // Limpa checkboxes de ações em lote
        }
    }

    public function render()
    {
        $queryBase = $this->obterQueryFiltrada();
        $inscricoes = $queryBase->paginate($this->porPagina);

        // Aplica a mágica da ordenação da Trait
        if ($this->ordenacaoCampo) {
            $queryBase->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            // Ordem padrão: pelo número da etapa
            $queryBase->orderBy('id', 'asc');
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
            'ciclosDb' => \App\Models\Ciclo::orderBy('id', 'desc')->get(),
            'unidadesDb' => \App\Modules\Unidade\Domain\Models\Unidade::whereIn('status', ['Ativa', '1', true])->get(),
            'turnosDb' => \App\Modules\Turno\Domain\Models\Turno::where('status', true)->get(),
            'cursosDb' => \App\Models\Curso::whereIn('status', ['Ativo', '1', true])->get(),
        ]);
    }
}