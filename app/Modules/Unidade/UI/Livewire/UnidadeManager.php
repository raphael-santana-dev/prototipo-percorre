<?php

namespace App\Modules\Unidade\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\Unidade\Application\Services\UnidadeService;
use App\Modules\Unidade\Domain\Models\Unidade;
use App\Models\Curso;
use App\Modules\Turno\Domain\Models\Turno;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

    public ?int $unidadeId = null;
    public string $nome = '';
    public string $status = 'Ativa';
    public ?string $data_inauguracao = null;
    public $cep, $logradouro, $numero, $complemento, $bairro, $cidade, $estado;
    public string $endereco = '';
    public string $email = '';
    public string $telefone = '';

    // Filtro de busca na listagem e dentro do modal
    public string $filtroBusca = '';
    public string $buscaCursoModal = '';

    // Matriz Contextual da Unidade:
    // $cursosSelecionados = ['1', '2']
    // $turnosSelecionados = ['1-3', '1-4', '2-1'] -> formato "{cursoId}-{turnoId}"
    public array $cursosSelecionados = [];
    public array $turnosSelecionados = [];
    public $activeCursoId = null;

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

    public function setActiveCurso(int $id): void
    {
        $this->activeCursoId = $id;
    }

    /**
     * Quando o usuário desmarca um Curso na Coluna 1, remove apenas os turnos daquele Curso.
     */
    public function updatedCursosSelecionados(): void
    {
        $this->cursosSelecionados = array_values(array_unique(array_map('strval', $this->cursosSelecionados)));

        $this->turnosSelecionados = array_values(array_filter($this->turnosSelecionados, function ($combo) {
            [$cId] = explode('-', (string) $combo, 2);
            return in_array((string) $cId, $this->cursosSelecionados, true);
        }));

        if (!empty($this->cursosSelecionados) && !$this->activeCursoId) {
            $this->activeCursoId = (int) $this->cursosSelecionados[0];
        }
    }

    /**
     * Quando o usuário marca um Turno ("{cursoId}-{turnoId}") na Coluna 2,
     * garante automaticamente que o Curso correspondente esteja marcado na Coluna 1.
     */
    public function updatedTurnosSelecionados(): void
    {
        $this->turnosSelecionados = array_values(array_unique(array_map('strval', $this->turnosSelecionados)));

        foreach ($this->turnosSelecionados as $combo) {
            if (str_contains($combo, '-')) {
                [$cId] = explode('-', $combo, 2);
                if (!in_array((string) $cId, $this->cursosSelecionados, true)) {
                    $this->cursosSelecionados[] = (string) $cId;
                }
            }
        }

        $this->cursosSelecionados = array_values(array_unique($this->cursosSelecionados));
    }

    /**
     * Atalho UX: Marca ou desmarca todos os turnos para o Curso ativo no momento.
     */
    public function toggleTodosTurnosDoCursoAtivo(): void
    {
        if (!$this->activeCursoId) return;

        $cId = (string) $this->activeCursoId;
        $turnosIds = Turno::where('status', true)->pluck('id')->map(fn($tId) => "{$cId}-{$tId}")->toArray();

        $atuaisDoCurso = array_values(array_filter($this->turnosSelecionados, fn($v) => str_starts_with((string) $v, "{$cId}-")));

        if (count($atuaisDoCurso) === count($turnosIds)) {
            // Desmarca todos os turnos deste curso
            $this->turnosSelecionados = array_values(array_diff($this->turnosSelecionados, $turnosIds));
        } else {
            // Marca todos os turnos deste curso e garante o curso ativo marcado
            $this->turnosSelecionados = array_values(array_unique(array_merge($this->turnosSelecionados, $turnosIds)));
            if (!in_array($cId, $this->cursosSelecionados, true)) {
                $this->cursosSelecionados[] = $cId;
            }
        }
    }

    public function contarTurnosDoCurso(int $cursoId): int
    {
        $prefixo = "{$cursoId}-";
        return count(array_filter($this->turnosSelecionados, fn($v) => str_starts_with((string) $v, $prefixo)));
    }

    public function openModal(): void
    {
        $this->resetInputFields();
        $primeiroCurso = Curso::whereIn('status', ['Ativo', 'ativo', '1', 1, true])->orderBy('nome')->first();
        if ($primeiroCurso) {
            $this->activeCursoId = $primeiroCurso->id;
        }
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetInputFields();
    }

    public function save(UnidadeService $service)
    {
        $this->validate([
            'nome'             => 'required|string|max:255',
            'cep'              => 'required|string|max:10',
            'estado'           => 'required|string|size:2',
            'cidade'           => 'required|string|max:255',
            'status'           => 'required|in:Ativa,Inativa',
            'email'            => 'nullable|email',
            'telefone'         => 'nullable|string|max:20',
            'data_inauguracao' => 'nullable|date',
        ]);

        $enderecoCompleto = trim("{$this->logradouro}, {$this->numero}" . ($this->complemento ? " - {$this->complemento}" : "") . " - {$this->bairro}, {$this->cidade}/{$this->estado}", " ,-/");

        $dados = [
            'nome'             => $this->nome,
            'slug'             => Str::slug($this->nome),
            'status'           => $this->status,
            'email'            => $this->email,
            'telefone'         => $this->telefone,
            'data_inauguracao' => $this->data_inauguracao,
            'cep'              => $this->cep,
            'logradouro'       => $this->logradouro,
            'numero'           => $this->numero,
            'complemento'      => $this->complemento,
            'bairro'           => $this->bairro,
            'cidade'           => $this->cidade,
            'estado'           => $this->estado,
            'endereco'         => $enderecoCompleto,
        ];

        DB::transaction(function () use ($service, $dados) {
            if ($this->isEditMode) {
                $service->atualizarUnidade($this->unidadeId, $dados);
                $unidadeId = $this->unidadeId;
            } else {
                $unidadeCriada = $service->criarUnidade($dados);
                $unidadeId = $unidadeCriada->id;
            }

            // Filtra cursos que possuem pelo menos 1 turno selecionado (ou foram marcados)
            $cursosIdsLimpos = array_values(array_unique(array_map('intval', $this->cursosSelecionados)));
            $service->sincronizarCursos($unidadeId, $cursosIdsLimpos);

            // Salva na tabela ternária unidade_curso_turno
            $turnosUnicosDaUnidade = [];
            if (Schema::hasTable('unidade_curso_turno')) {
                DB::table('unidade_curso_turno')->where('unidade_id', $unidadeId)->delete();

                $agora = now();
                $inserts = [];
                foreach ($this->turnosSelecionados as $combo) {
                    $partes = explode('-', (string) $combo);
                    if (count($partes) === 2) {
                        $cId = (int) $partes[0];
                        $tId = (int) $partes[1];
                        if (in_array($cId, $cursosIdsLimpos, true)) {
                            $inserts[] = [
                                'unidade_id' => $unidadeId,
                                'curso_id'   => $cId,
                                'turno_id'   => $tId,
                                'created_at' => $agora,
                                'updated_at' => $agora,
                            ];
                            $turnosUnicosDaUnidade[] = $tId;

                            // Garante que o turno também conste em curso_turno para retrocompatibilidade
                            if (Schema::hasTable('curso_turno')) {
                                DB::table('curso_turno')->insertOrIgnore([
                                    'curso_id' => $cId,
                                    'turno_id' => $tId,
                                ]);
                            }
                        }
                    }
                }

                if (!empty($inserts)) {
                    DB::table('unidade_curso_turno')->insertOrIgnore($inserts);
                }
            }

            $unidadeObj = Unidade::find($unidadeId);
            if ($unidadeObj && method_exists($unidadeObj, 'turnos')) {
                $unidadeObj->turnos()->sync(array_values(array_unique($turnosUnicosDaUnidade)));
            }
        });

        $this->showModal = false;
        $this->resetInputFields();
        $this->dispatch('sucesso', msg: $this->isEditMode ? 'Unidade atualizada com sucesso!' : 'Unidade cadastrada com sucesso!');
    }

    public function edit(UnidadeService $service, int $id)
    {
        $this->resetInputFields();
        $unidade = $service->buscarPorId($id);
        $unidade->load('cursos');

        $this->unidadeId        = $unidade->id;
        $this->nome             = $unidade->nome;
        $this->status           = $unidade->status;
        $this->data_inauguracao = $unidade->data_inauguracao ? \Carbon\Carbon::parse($unidade->data_inauguracao)->format('Y-m-d') : null;
        $this->email            = $unidade->email ?? '';
        $this->telefone         = $unidade->telefone ?? '';

        $this->cep         = $unidade->cep;
        $this->logradouro  = $unidade->logradouro;
        $this->numero      = $unidade->numero;
        $this->complemento = $unidade->complemento;
        $this->bairro      = $unidade->bairro;
        $this->cidade      = $unidade->cidade;
        $this->estado      = $unidade->estado;

        $this->cursosSelecionados = $unidade->cursos->pluck('id')->map(fn($v) => (string) $v)->toArray();

        $combosTurnos = [];
        if (Schema::hasTable('unidade_curso_turno')) {
            $registrosTernarios = DB::table('unidade_curso_turno')
                ->where('unidade_id', $unidade->id)
                ->get();

            foreach ($registrosTernarios as $reg) {
                $combosTurnos[] = "{$reg->curso_id}-{$reg->turno_id}";
            }
        }

        $this->turnosSelecionados = array_values(array_unique($combosTurnos));
        $this->activeCursoId = !empty($this->cursosSelecionados)
            ? (int) $this->cursosSelecionados[0]
            : Curso::whereIn('status', ['Ativo', 'ativo', '1', 1, true])->orderBy('nome')->value('id');

        $this->isEditMode = true;
        $this->showModal = true;
    }

    public function delete(UnidadeService $service, int $id)
    {
        if (Schema::hasTable('unidade_curso_turno')) {
            DB::table('unidade_curso_turno')->where('unidade_id', $id)->delete();
        }
        $service->deletarUnidade($id);
        $this->dispatch('sucesso', msg: 'Unidade removida com sucesso.');
    }

    private function resetInputFields()
    {
        $this->reset([
            'unidadeId', 'nome', 'data_inauguracao', 'email', 'telefone',
            'cursosSelecionados', 'turnosSelecionados', 'activeCursoId',
            'isEditMode', 'cep', 'logradouro', 'numero', 'complemento',
            'bairro', 'cidade', 'estado', 'buscaCursoModal',
        ]);
        $this->status = 'Ativa';
        $this->resetErrorBag();
    }

    public function showQuickView(UnidadeService $service, int $id)
    {
        $unidade = $service->buscarPorId($id);
        $unidade->load('cursos');

        $turnosPorCurso = Schema::hasTable('unidade_curso_turno')
            ? DB::table('unidade_curso_turno')
                ->join('turnos', 'turnos.id', '=', 'unidade_curso_turno.turno_id')
                ->where('unidade_curso_turno.unidade_id', $unidade->id)
                ->select('unidade_curso_turno.curso_id', 'turnos.nome')
                ->get()
                ->groupBy('curso_id')
            : collect();

        $htmlMatriz = '<div class="space-y-2">';
        foreach ($unidade->cursos as $c) {
            $nomesTurnos = isset($turnosPorCurso[$c->id])
                ? $turnosPorCurso[$c->id]->pluck('nome')->implode(', ')
                : 'Sem turnos definidos';
            $htmlMatriz .= '<div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-between gap-2 text-xs">'
                . '<span class="font-bold text-gray-800 dark:text-gray-200">' . e($c->nome) . '</span>'
                . '<span class="px-2 py-0.5 rounded bg-purpura-50 text-purpura-700 font-semibold text-[11px]">' . e($nomesTurnos) . '</span>'
                . '</div>';
        }
        if ($unidade->cursos->isEmpty()) {
            $htmlMatriz .= '<span class="text-xs text-gray-400 italic">Nenhum curso vinculado.</span>';
        }
        $htmlMatriz .= '</div>';

        $this->dispatch('load-quick-view', [
            'title'    => $unidade->nome,
            'subtitle' => $unidade->cidade ? "{$unidade->cidade}/{$unidade->estado} • {$unidade->status}" : "Status: {$unidade->status}",
            'icon'     => 'ph-buildings',
            'data'     => [
                'Endereço'                  => $unidade->endereco ?: 'Não informado',
                'Contato'                   => ($unidade->email ?: 'Sem e-mail') . ' • ' . ($unidade->telefone ?: 'Sem telefone'),
                'Cursos e Turnos na Unidade'=> $htmlMatriz,
            ],
        ]);
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true, 'class' => 'w-16'],
            ['key' => 'nome', 'label' => 'Unidade & Localização', 'sortable' => true],
            ['key' => 'oferta', 'label' => 'Oferta Acadêmica', 'sortable' => false],
            ['key' => 'telefone', 'label' => 'Contato', 'sortable' => false],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true, 'class' => 'w-28'],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right w-28'],
        ];
    }

    public function toggleStatus($id)
    {
        $unidade = Unidade::findOrFail($id);
        $unidade->update([
            'status' => $unidade->status === 'Ativa' ? 'Inativa' : 'Ativa',
        ]);
        $this->dispatch('sucesso', msg: 'Status da unidade atualizado!');
    }

    public function render()
    {
        $query = Unidade::withCount('cursos');

        $user = auth()->user();
        if ($user && method_exists($user, 'temVisaoGlobal') && !$user->temVisaoGlobal('unidades')) {
            $unidadesIds = $user->unidades->pluck('id')->toArray();
            if (count($unidadesIds) > 0) {
                $query->whereIn('id', $unidadesIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if (!empty($this->filtroBusca)) {
            $query->where(function ($q) {
                $q->where('nome', 'ilike', '%' . $this->filtroBusca . '%')
                  ->orWhere('cidade', 'ilike', '%' . $this->filtroBusca . '%');
            });
        }

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('nome', 'asc');
        }

        $cursosQuery = Curso::whereIn('status', ['Ativo', 'ativo', '1', 1, true])->orderBy('nome');
        if (!empty($this->buscaCursoModal)) {
            $cursosQuery->where('nome', 'ilike', '%' . $this->buscaCursoModal . '%');
        }

        return view('livewire.unidade.unidade-manager', [
            'registros'         => $query->paginate($this->porPagina),
            'cursosDisponiveis' => $cursosQuery->get(),
            'turnosDisponiveis' => Turno::where('status', true)->orderBy('horario_inicio')->orderBy('nome')->get(),
        ]);
    }
}