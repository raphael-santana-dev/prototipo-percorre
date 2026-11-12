<?php

namespace App\Modules\Curso\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\Curso\Application\Services\CursoService;
use Livewire\WithPagination;
use App\Helpers\BreadcrumbHelper;
use App\Traits\ComPadraoListagem;
use App\Traits\WithToggleStatus;
use App\Models\Curso;
use App\Modules\Unidade\Domain\Models\Unidade;
use App\Modules\Turno\Domain\Models\Turno;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Cursos - Administrativo')]
class CursoManager extends Component
{
    use WithPagination, ComPadraoListagem, WithToggleStatus;

    public bool $showModal = false;
    public bool $isEditMode = false;

    public ?int $cursoId = null;
    public string $nome = '';
    public string $status = 'Ativo';
    public bool $permite_estado_diferente = false;

    public string $filtroBusca = '';

    // Matriz Contextual do Curso:
    // $unidadesSelecionadas = ['1', '2']
    // $turnosSelecionados = ['1-3', '1-4', '2-1'] -> formato "{unidadeId}-{turnoId}"
    public array $unidadesSelecionadas = [];
    public array $turnosSelecionados = [];
    public $activeUnidadeId = null;

    public $modelClass = Curso::class;
    public array $breadcrumbs = [];

    public function mount()
    {
        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->permiteGrid = true;
    }

    public function updatingFiltroBusca()
    {
        $this->resetPage();
    }

    public function setActiveUnidade(int $id): void
    {
        $this->activeUnidadeId = $id;
    }

    /**
     * Ao desmarcar uma Unidade na Coluna 1, limpa apenas os turnos daquela Unidade.
     */
    public function updatedUnidadesSelecionadas(): void
    {
        $this->unidadesSelecionadas = array_values(array_unique(array_map('strval', $this->unidadesSelecionadas)));

        $this->turnosSelecionados = array_values(array_filter($this->turnosSelecionados, function ($combo) {
            [$uId] = explode('-', (string) $combo, 2);
            return in_array((string) $uId, $this->unidadesSelecionadas, true);
        }));

        if (!empty($this->unidadesSelecionadas) && !$this->activeUnidadeId) {
            $this->activeUnidadeId = (int) $this->unidadesSelecionadas[0];
        }
    }

    /**
     * Ao marcar um Turno ("{unidadeId}-{turnoId}") na Coluna 2,
     * marca automaticamente a Unidade correspondente na Coluna 1.
     */
    public function updatedTurnosSelecionados(): void
    {
        $this->turnosSelecionados = array_values(array_unique(array_map('strval', $this->turnosSelecionados)));

        foreach ($this->turnosSelecionados as $combo) {
            if (str_contains($combo, '-')) {
                [$uId] = explode('-', $combo, 2);
                if (!in_array((string) $uId, $this->unidadesSelecionadas, true)) {
                    $this->unidadesSelecionadas[] = (string) $uId;
                }
            }
        }

        $this->unidadesSelecionadas = array_values(array_unique($this->unidadesSelecionadas));
    }

    /**
     * Atalho UX: Replica os turnos da Unidade ativa atual para todas as outras Unidades marcadas.
     */
    public function replicarTurnosParaUnidadesMarcadas(): void
    {
        if (!$this->activeUnidadeId || empty($this->unidadesSelecionadas)) return;

        $uAtiva = (string) $this->activeUnidadeId;
        $turnosDaAtiva = [];

        foreach ($this->turnosSelecionados as $combo) {
            if (str_starts_with((string) $combo, "{$uAtiva}-")) {
                [, $tId] = explode('-', (string) $combo, 2);
                $turnosDaAtiva[] = $tId;
            }
        }

        if (empty($turnosDaAtiva)) {
            $this->dispatch('erro', msg: 'Marque pelo menos um turno nesta unidade antes de replicar.');
            return;
        }

        foreach ($this->unidadesSelecionadas as $uId) {
            foreach ($turnosDaAtiva as $tId) {
                $this->turnosSelecionados[] = "{$uId}-{$tId}";
            }
        }

        $this->turnosSelecionados = array_values(array_unique($this->turnosSelecionados));
        $this->dispatch('sucesso', msg: 'Turnos replicados para todas as unidades selecionadas!');
    }

    public function contarTurnosNaUnidade(int $unidadeId): int
    {
        $prefixo = "{$unidadeId}-";
        return count(array_filter($this->turnosSelecionados, fn($v) => str_starts_with((string) $v, $prefixo)));
    }

    public function openModal(): void
    {
        $this->resetInputFields();
        $primeiraUnidade = Unidade::whereIn('status', ['Ativa', '1', true])->orderBy('nome')->first();
        if ($primeiraUnidade) {
            $this->activeUnidadeId = $primeiraUnidade->id;
        }
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetInputFields();
    }

    public function save(CursoService $service)
    {
        $this->validate([
            'nome'                     => 'required|string|max:255',
            'status'                   => 'required',
            'permite_estado_diferente' => 'boolean',
        ]);

        $dados = [
            'nome'                     => $this->nome,
            'slug'                     => Str::slug($this->nome),
            'status'                   => $this->status,
            'permite_estado_diferente' => $this->permite_estado_diferente,
        ];

        DB::transaction(function () use ($service, $dados) {
            if ($this->isEditMode) {
                $service->atualizarCurso($this->cursoId, $dados);
                $cursoId = $this->cursoId;
            } else {
                $cursoCriado = $service->criarCurso($dados);
                $cursoId = $cursoCriado->id;
            }

            $unidadesIdsLimpos = array_values(array_unique(array_map('intval', $this->unidadesSelecionadas)));
            $turnosUnicosDoCurso = [];

            if (Schema::hasTable('unidade_curso_turno')) {
                DB::table('unidade_curso_turno')->where('curso_id', $cursoId)->delete();

                $agora = now();
                $inserts = [];
                foreach ($this->turnosSelecionados as $combo) {
                    $partes = explode('-', (string) $combo);
                    if (count($partes) === 2) {
                        $uId = (int) $partes[0];
                        $tId = (int) $partes[1];
                        if (in_array($uId, $unidadesIdsLimpos, true)) {
                            $inserts[] = [
                                'unidade_id' => $uId,
                                'curso_id'   => $cursoId,
                                'turno_id'   => $tId,
                                'created_at' => $agora,
                                'updated_at' => $agora,
                            ];
                            $turnosUnicosDoCurso[] = $tId;
                        }
                    }
                }

                if (!empty($inserts)) {
                    DB::table('unidade_curso_turno')->insertOrIgnore($inserts);
                }
            }

            $service->sincronizarRelacionamentos(
                $cursoId,
                $unidadesIdsLimpos,
                array_values(array_unique($turnosUnicosDoCurso))
            );
        });

        $this->showModal = false;
        $this->resetInputFields();
        $this->dispatch('sucesso', msg: $this->isEditMode ? 'Curso atualizado com sucesso!' : 'Curso cadastrado com sucesso!');
    }

    public function edit(CursoService $service, int $id)
    {
        $this->resetInputFields();
        $curso = $service->buscarPorId($id);
        $curso->load(['unidades', 'turnosVinculados']);

        $this->cursoId                  = $curso->id;
        $this->nome                     = $curso->nome;
        $this->status                   = in_array($curso->status, ['1', 1, true, 'Ativo', 'ativo'], true) ? 'Ativo' : 'Inativo';
        $this->permite_estado_diferente = (bool) $curso->permite_estado_diferente;
        $this->unidadesSelecionadas     = $curso->unidades->pluck('id')->map(fn($v) => (string) $v)->toArray();

        $combosTurnos = [];
        if (Schema::hasTable('unidade_curso_turno')) {
            $registrosTernarios = DB::table('unidade_curso_turno')
                ->where('curso_id', $curso->id)
                ->get();

            foreach ($registrosTernarios as $reg) {
                $combosTurnos[] = "{$reg->unidade_id}-{$reg->turno_id}";
            }
        }

        // Fallback caso existam dados legados ainda não migrados
        if (empty($combosTurnos) && !empty($this->unidadesSelecionadas)) {
            foreach ($this->unidadesSelecionadas as $uId) {
                foreach ($curso->turnosVinculados as $t) {
                    $combosTurnos[] = "{$uId}-{$t->id}";
                }
            }
        }

        $this->turnosSelecionados = array_values(array_unique($combosTurnos));
        $this->activeUnidadeId = !empty($this->unidadesSelecionadas)
            ? (int) $this->unidadesSelecionadas[0]
            : Unidade::whereIn('status', ['Ativa', '1', true])->orderBy('nome')->value('id');

        $this->isEditMode = true;
        $this->showModal = true;
    }

    public function delete(CursoService $service, int $id)
    {
        if (Schema::hasTable('unidade_curso_turno')) {
            DB::table('unidade_curso_turno')->where('curso_id', $id)->delete();
        }
        $service->deletarCurso($id);
        $this->dispatch('sucesso', msg: 'Curso removido com sucesso.');
    }

    private function resetInputFields()
    {
        $this->reset(['cursoId', 'nome', 'isEditMode', 'unidadesSelecionadas', 'turnosSelecionados', 'activeUnidadeId']);
        $this->status = 'Ativo';
        $this->permite_estado_diferente = false;
        $this->resetErrorBag();
    }

    public function showQuickView(CursoService $service, int $id)
    {
        $curso = $service->buscarPorId($id);
        $curso->load(['unidades', 'turnosVinculados']);

        $turnosPorUnidade = Schema::hasTable('unidade_curso_turno')
            ? DB::table('unidade_curso_turno')
                ->join('turnos', 'turnos.id', '=', 'unidade_curso_turno.turno_id')
                ->where('unidade_curso_turno.curso_id', $curso->id)
                ->select('unidade_curso_turno.unidade_id', 'turnos.nome')
                ->get()
                ->groupBy('unidade_id')
            : collect();

        $htmlOferta = '<div class="space-y-2">';
        foreach ($curso->unidades as $u) {
            $nomesTurnos = isset($turnosPorUnidade[$u->id])
                ? $turnosPorUnidade[$u->id]->pluck('nome')->implode(', ')
                : 'Sem turnos definidos';
            $htmlOferta .= '<div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-between gap-2 text-xs">'
                . '<span class="font-bold text-gray-800 dark:text-gray-200">' . e($u->nome) . '</span>'
                . '<span class="px-2 py-0.5 rounded bg-purpura-50 text-purpura-700 font-semibold text-[11px]">' . e($nomesTurnos) . '</span>'
                . '</div>';
        }
        if ($curso->unidades->isEmpty()) {
            $htmlOferta .= '<span class="text-xs text-gray-400 italic">Nenhuma unidade vinculada.</span>';
        }
        $htmlOferta .= '</div>';

        $this->dispatch('load-quick-view', [
            'title'    => $curso->nome,
            'subtitle' => 'Status: ' . $curso->status,
            'icon'     => 'ph-graduation-cap',
            'data'     => [
                'Restrição Geográfica'       => $curso->permite_estado_diferente ? 'Aceita alunos de outros Estados' : 'Apenas residentes do Estado local',
                'Unidades e Turnos do Curso' => $htmlOferta,
            ],
        ]);
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true, 'class' => 'w-16'],
            ['key' => 'nome', 'label' => 'Nome do Curso', 'sortable' => true],
            ['key' => 'oferta', 'label' => 'Disponibilidade', 'sortable' => false],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true, 'class' => 'w-28'],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right w-28'],
        ];
    }

    public function render()
    {
        $query = Curso::withCount(['unidades', 'turnosVinculados']);

        if (!empty($this->filtroBusca)) {
            $query->where('nome', 'ilike', '%' . $this->filtroBusca . '%');
        }

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('nome', 'asc');
        }

        return view('livewire.curso.curso-manager', [
            'registros'           => $query->paginate($this->porPagina),
            'unidadesDisponiveis' => Unidade::whereIn('status', ['Ativa', '1', true])->orderBy('nome')->get(),
            'turnosDisponiveis'   => Turno::where('status', true)->orderBy('horario_inicio')->orderBy('nome')->get(),
        ]);
    }
}