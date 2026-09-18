<?php

namespace App\Modules\Period\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Ciclo;
use App\Models\Curso;
use App\Models\StatusInscricao;
use App\Models\OfertaVaga;
use App\Modules\Matricula\Domain\Models\DocumentoExigido;
use Livewire\WithPagination;
use App\Helpers\BreadcrumbHelper;
use App\Traits\ComPadraoListagem;
use App\Traits\WithToggleStatus;
use Illuminate\Support\Str;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Ciclos - Administrativo')]
class PeriodManager extends Component
{
    use WithPagination, ComPadraoListagem, WithToggleStatus;

    public $modalAberto = false;
    public int $passoAtual = 1;
    public ?int $cicloIdEmEdicao = null;

    // Passo 1: Dados Básicos
    public $nome, $ano, $semestre, $data_inicio, $data_fim, $status = false;

    // Passo 2: Estrutura Académica (Gravando Combinações Únicas)
    public array $unidadesSelecionadas = []; 
    public array $cursosSelecionados = []; 
    public array $turnosSelecionados = []; 
    public $activeUnidadeId = null;
    public $activeCursoId = null;

    // Passo 3: Distribuição de Vagas (Auto-gerada)
    public array $ofertasVagas = [];

    // Passo 4: Etapas do Ciclo (Pipeline)
    public array $statusSelecionados = [];
    public $novoStatusSelecionado = ''; 

    // Passo 5: Documentos Exigidos
    public array $documentosExigidos = []; 

    // Passo 6: Conclusão
    public ?int $cicloCriadoId = null;

    public $modelClass = Ciclo::class;
    public array $breadcrumbs = [];

    public $filtro_ano = '';
    public $filtro_semestre = '';
    public $filtro_status = '';

    public function mount()
    {
        abort_if(!feature('ciclo.listar'), 403, 'O módulo de ciclos de inscrição está desativado.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('ciclo.listar'), 403);
        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->permiteGrid = true;
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtro_ano', 'filtro_semestre', 'filtro_status'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtro_ano', 'filtro_semestre', 'filtro_status']);
        $this->resetPage();
    }

    public function abrirModal()
    {
        abort_if(!feature('ciclo.criar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('ciclo.criar'), 403);

        $this->resetValidation();
        $this->reset([
            'nome', 'ano', 'semestre', 'data_inicio', 'data_fim', 'status',
            'unidadesSelecionadas', 'cursosSelecionados', 'turnosSelecionados',
            'ofertasVagas', 'statusSelecionados', 'documentosExigidos',
            'activeUnidadeId', 'activeCursoId', 'cicloCriadoId', 'cicloIdEmEdicao'
        ]);
        
        $this->ano = date('Y');
        $this->semestre = date('n') <= 6 ? 1 : 2;
        $this->passoAtual = 1;

        $statusIniciais = StatusInscricao::orderBy('id')->take(3)->pluck('id')->map(fn($v) => (string)$v)->toArray();
        $this->statusSelecionados = $statusIniciais;

        $this->modalAberto = true;
    }

    public function proximoPasso()
    {
        if ($this->passoAtual === 1) {
            $this->validate([
                'ano' => 'required|integer|min:2020',
                'semestre' => 'required|integer|in:1,2',
                'data_inicio' => 'required|date',
                'data_fim' => 'required|date|after:data_inicio',
            ]);
            $this->salvarPasso1Parcial();
        } elseif ($this->passoAtual === 2) {
            $this->salvarPasso2Parcial();
            $this->sincronizarCombinacoesVagas(); 
        } elseif ($this->passoAtual === 3) {
            $this->salvarPasso3Parcial();
        } elseif ($this->passoAtual === 4) {
            $this->salvarPasso4Parcial();
        } elseif ($this->passoAtual === 5) {
            $this->salvarPasso5Final();
        }

        if ($this->passoAtual < 6) {
            $this->passoAtual++;
        }
    }

    public function passoAnterior()
    {
        if ($this->passoAtual > 1) {
            $this->passoAtual--;
        }
    }

    public function irParaPasso(int $passo)
    {
        if ($passo < $this->passoAtual || $this->cicloIdEmEdicao) {
            $this->passoAtual = $passo;
        }
    }

    private function salvarPasso1Parcial()
    {
        if ($this->status) {
            Ciclo::query()->update(['status' => false]);
        }

        $nomeFinal = trim($this->nome);
        if (empty($nomeFinal)) {
            $nomeFinal = "{$this->ano} - {$this->semestre}º Semestre";
        }

        if ($this->cicloIdEmEdicao) {
            $ciclo = Ciclo::findOrFail($this->cicloIdEmEdicao);
            $ciclo->update([
                'nome' => $nomeFinal, 'ano' => $this->ano, 'semestre' => $this->semestre,
                'data_inicio' => $this->data_inicio, 'data_fim' => $this->data_fim, 'status' => $this->status
            ]);
        } else {
            $ciclo = Ciclo::create([
                'nome' => $nomeFinal, 'ano' => $this->ano, 'semestre' => $this->semestre,
                'data_inicio' => $this->data_inicio, 'data_fim' => $this->data_fim, 'status' => $this->status,
                'slug' => Str::slug($nomeFinal) . '-' . time()
            ]);
            $this->cicloIdEmEdicao = $ciclo->id;
            $this->cicloCriadoId = $ciclo->id;
        }
    }

    private function salvarPasso2Parcial()
    {
        if (!$this->cicloIdEmEdicao) return;
        $ciclo = Ciclo::findOrFail($this->cicloIdEmEdicao);
        
        $ciclo->unidades()->sync($this->unidadesSelecionadas);
        
        // Extrai os IDs reais dos formatos isolados "UnidadeID-CursoID"
        $cursosUnicos = collect($this->cursosSelecionados)->map(fn($v) => explode('-', $v)[1] ?? $v)->unique()->filter()->toArray();
        $ciclo->cursos()->sync($cursosUnicos);
        
        // Extrai os IDs reais dos formatos "UnidadeID-CursoID-TurnoID"
        $turnosUnicos = collect($this->turnosSelecionados)->map(fn($v) => explode('-', $v)[2] ?? $v)->unique()->filter()->toArray();
        $ciclo->turnos()->sync($turnosUnicos);
    }

    private function sincronizarCombinacoesVagas()
    {
        $cursosDb = Curso::with('turnosVinculados')->get();
        $unidadesDb = \App\Modules\Unidade\Domain\Models\Unidade::pluck('nome', 'id');
        
        $ofertasExistentes = [];
        foreach ($this->ofertasVagas as $oferta) {
            if (!empty($oferta['unidade_id']) && !empty($oferta['curso_id']) && !empty($oferta['turno_id'])) {
                $key = $oferta['unidade_id'] . '-' . $oferta['curso_id'] . '-' . $oferta['turno_id'];
                $ofertasExistentes[$key] = $oferta;
            }
        }

        $novasOfertas = [];

        foreach ($this->turnosSelecionados as $combo) {
            $parts = explode('-', $combo);
            if (count($parts) === 3) {
                $uId = $parts[0];
                $cId = $parts[1];
                $tId = $parts[2];
                
                // Validação para garantir que a Unidade e Curso ainda estão selecionados
                if (!in_array($uId, $this->unidadesSelecionadas)) continue;
                if (!in_array("{$uId}-{$cId}", $this->cursosSelecionados)) continue;

                $curso = $cursosDb->firstWhere('id', $cId);
                $turno = $curso ? $curso->turnosVinculados->firstWhere('id', $tId) : null;
                
                if (!$curso || !$turno) continue;

                $key = $combo;
                
                if (isset($ofertasExistentes[$key])) {
                    $novasOfertas[] = array_merge($ofertasExistentes[$key], [
                        'unidade_nome' => $unidadesDb[$uId] ?? 'Unidade',
                        'curso_nome' => $curso->nome,
                        'turno_nome' => $turno->nome,
                    ]);
                } else {
                    $novasOfertas[] = [
                        'unidade_id' => (string) $uId,
                        'unidade_nome' => $unidadesDb[$uId] ?? 'Unidade',
                        'curso_id' => (string) $cId,
                        'curso_nome' => $curso->nome,
                        'turno_id' => (string) $tId,
                        'turno_nome' => $turno->nome,
                        'vagas' => '',
                        'idade_min' => '',
                        'idade_max' => '',
                    ];
                }
            }
        }

        $this->ofertasVagas = $novasOfertas;
    }

    private function salvarPasso3Parcial()
    {
        if (!$this->cicloIdEmEdicao) return;
        OfertaVaga::where('ciclo_id', $this->cicloIdEmEdicao)->delete();
        foreach ($this->ofertasVagas as $oferta) {
            if (!empty($oferta['curso_id']) && !empty($oferta['unidade_id']) && !empty($oferta['turno_id'])) {
                OfertaVaga::create([
                    'ciclo_id' => $this->cicloIdEmEdicao, 'unidade_id' => $oferta['unidade_id'],
                    'curso_id' => $oferta['curso_id'], 'turno_id' => $oferta['turno_id'],
                    'vagas' => (int) ($oferta['vagas'] ?? 0),
                    'idade_min' => !empty($oferta['idade_min']) ? (int) $oferta['idade_min'] : null,
                    'idade_max' => !empty($oferta['idade_max']) ? (int) $oferta['idade_max'] : null,
                ]);
            }
        }
    }

    private function salvarPasso4Parcial()
    {
        if (!$this->cicloIdEmEdicao) return;
        $ciclo = Ciclo::findOrFail($this->cicloIdEmEdicao);
        $syncStatus = [];
        foreach ($this->statusSelecionados as $index => $statusId) {
            $syncStatus[$statusId] = ['ordem' => $index + 1];
        }
        $ciclo->statusPipeline()->sync($syncStatus);
    }

    private function salvarPasso5Final()
    {
        if (!$this->cicloIdEmEdicao) return;
        foreach ($this->documentosExigidos as $docData) {
            if (!empty(trim($docData['nome']))) {
                DocumentoExigido::updateOrCreate(
                    ['id' => $docData['id'] ?? null, 'ciclo_id' => $this->cicloIdEmEdicao],
                    ['nome' => trim($docData['nome']), 'descricao' => $docData['descricao'] ?? '', 'is_obrigatorio' => $docData['is_obrigatorio'] ?? false]
                );
            }
        }
        $this->cicloCriadoId = $this->cicloIdEmEdicao;
        $this->dispatch('sucesso', msg: 'Ciclo configurado com sucesso!');
    }

    public function setActiveUnidade($id) { $this->activeUnidadeId = $id; $this->activeCursoId = null; }
    public function setActiveCurso($id) { $this->activeCursoId = $id; }

    public function adicionarStatusPipeline()
    {
        if (!empty($this->novoStatusSelecionado) && !in_array($this->novoStatusSelecionado, $this->statusSelecionados)) {
            $this->statusSelecionados[] = $this->novoStatusSelecionado;
        }
        $this->novoStatusSelecionado = '';
    }
    public function removerStatusPipeline($id) { $this->statusSelecionados = array_values(array_diff($this->statusSelecionados, [$id])); }
    public function atualizarOrdemStatus($ordemIds) { $this->statusSelecionados = $ordemIds; }

    public function addDocumento() { $this->documentosExigidos[] = ['id' => null, 'nome' => '', 'descricao' => '', 'is_obrigatorio' => true]; }
    public function removeDocumento($index) { unset($this->documentosExigidos[$index]); $this->documentosExigidos = array_values($this->documentosExigidos); }

    public function delete(int $id)
    {
        abort_if(!feature('ciclo.excluir'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('ciclo.excluir'), 403);
        Ciclo::findOrFail($id)->delete();
        $this->dispatch('sucesso', msg: 'Ciclo eliminado com sucesso!');
    }

    public function duplicar(int $id)
    {
        abort_if(!feature('ciclo.criar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('ciclo.criar'), 403);
        
        $cicloOriginal = Ciclo::with(['cursos', 'statusPipeline', 'unidades', 'turnos'])->findOrFail($id);
        $novoCiclo = $cicloOriginal->replicate();
        $novoCiclo->nome = $cicloOriginal->nome . ' (Cópia)'; 
        $novoCiclo->slug = Str::slug($novoCiclo->nome) . '-' . time(); 
        $novoCiclo->status = false;
        $novoCiclo->save();

        $novoCiclo->cursos()->sync($cicloOriginal->cursos->pluck('id')->toArray());
        $novoCiclo->unidades()->sync($cicloOriginal->unidades->pluck('id')->toArray());
        $novoCiclo->turnos()->sync($cicloOriginal->turnos->pluck('id')->toArray());

        if ($cicloOriginal->statusPipeline) {
            $syncStatus = [];
            foreach ($cicloOriginal->statusPipeline as $status) {
                $syncStatus[$status->id] = ['ordem' => $status->pivot->ordem ?? 1];
            }
            $novoCiclo->statusPipeline()->sync($syncStatus);
        }

        foreach (\App\Models\CampoFormulario::where('ciclo_id', $id)->get() as $campo) {
            $nc = $campo->replicate(); $nc->ciclo_id = $novoCiclo->id; $nc->save();
        }
        foreach (\App\Models\OfertaVaga::where('ciclo_id', $id)->get() as $oferta) {
            $no = $oferta->replicate(); $no->ciclo_id = $novoCiclo->id; $no->save();
        }
        foreach (DocumentoExigido::where('ciclo_id', $id)->get() as $doc) {
            $nd = $doc->replicate(); $nd->ciclo_id = $novoCiclo->id; $nd->save();
        }

        $this->dispatch('sucesso', msg: 'Ciclo duplicado com sucesso!');
    }

    public function showQuickView(int $id)
    {
        $ciclo = Ciclo::withCount('inscricoes')->with(['cursos', 'unidades'])->findOrFail($id);
        $vagasOfertadas = OfertaVaga::where('ciclo_id', $id)->sum('vagas');
        $vagasPreenchidas = \App\Models\Inscricao::where('ciclo_id', $id)
            ->whereHas('statusInscricao', fn($q) => $q->whereIn('nome', ['Aprovado', 'Selecionado']))->count();

        $percentual = $vagasOfertadas > 0 ? round(($vagasPreenchidas / $vagasOfertadas) * 100, 1) : 0;
        $corBarra = $percentual >= 100 ? 'bg-red-500' : ($percentual >= 80 ? 'bg-orange-500' : 'bg-emerald-500');

        $this->dispatch('load-quick-view', [
            'title' => $ciclo->nome,
            'subtitle' => "Semestre {$ciclo->ano}.{$ciclo->semestre} • De " . $ciclo->data_inicio->format('d/m/Y') . " até " . $ciclo->data_fim->format('d/m/Y'),
            'icon' => 'ph-calendar-check',
            'data' => [
                'Status do Ciclo' => $ciclo->status ? '<span class="px-2 py-1 bg-green-50 text-green-700 text-[10px] font-bold rounded border border-green-200">ATIVO</span>' : '<span class="px-2 py-1 bg-gray-50 text-gray-500 text-[10px] font-bold rounded border">INATIVO</span>',
                'Inscrições Realizadas' => '<span class="font-black text-xl text-purpura-600">'.$ciclo->inscricoes_count.'</span>',
                'Ações Extras' => '<a href="'.route('ciclos.show', $ciclo->id).'" class="font-bold text-purpura-600 hover:underline text-sm">Acessar Detalhes</a>'
            ]
        ]);
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'nome', 'label' => 'Nome / Período', 'sortable' => true],
            ['key' => 'data_inicio', 'label' => 'Abertura', 'sortable' => true],
            ['key' => 'data_fim', 'label' => 'Encerramento', 'sortable' => true],
            ['key' => 'inscricoes_count', 'label' => 'Inscrições', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'ocupacao', 'label' => 'Ocupação', 'sortable' => false, 'class' => 'text-center'],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function render()
    {
        $query = Ciclo::query()->withCount('inscricoes');
        
        $query->addSelect([
            'total_vagas' => OfertaVaga::selectRaw('COALESCE(SUM(vagas), 0)')->whereColumn('ciclo_id', 'ciclos.id'),
            'vagas_preenchidas' => \App\Models\Inscricao::selectRaw('COUNT(*)')
                ->whereColumn('ciclo_id', 'ciclos.id')
                ->whereHas('statusInscricao', fn($q) => $q->whereIn('nome', ['Aprovado', 'Selecionado']))
        ]);
        
        $query->when($this->filtro_ano, fn($q) => $q->where('ano', $this->filtro_ano))
              ->when($this->filtro_semestre, fn($q) => $q->where('semestre', $this->filtro_semestre))
              ->when($this->filtro_status !== '', fn($q) => $q->where('status', $this->filtro_status));
        
        if ($this->ordenacaoCampo && $this->ordenacaoCampo !== 'ocupacao') {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('id', 'desc');
        }

        return view('livewire.period.period-manager', [
            'registros' => $query->paginate($this->porPagina),
            'anosDisponiveis' => Ciclo::select('ano')->distinct()->orderBy('ano', 'desc')->pluck('ano'),
            'unidadesDb' => \App\Modules\Unidade\Domain\Models\Unidade::whereIn('status', ['Ativa', '1', true])->orderBy('nome')->get(),
            'cursosDb' => Curso::with(['unidades', 'turnosVinculados'])->whereIn('status', ['Ativo', '1', true])->orderBy('nome')->get(),
            'statusDisponiveis' => StatusInscricao::orderBy('nome')->get(),
        ]);
    }
}