<?php

namespace App\Modules\Period\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Ciclo;
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
    public $nome, $ano, $semestre, $data_inicio, $data_fim, $status = false;
    
    public $modelClass = Ciclo::class;
    public array $breadcrumbs = [];

    public $filtro_ano = '';
    public $filtro_semestre = '';
    public $filtro_status = '';

    public bool $unicoAtivo = true;
    
    public function mount()
    {
        abort_if(!feature('ciclo.listar'), 403, 'O módulo de ciclos de inscrição está temporariamente desativado no sistema.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('ciclo.listar'), 403, 'Acesso restrito.');
        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->permiteGrid = true;
    }

    protected function rules()
    {
        return [
            'nome' => 'nullable|string|max:255',
            'ano' => 'required|integer|min:2020',
            'semestre' => 'required|integer|in:1,2',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after:data_inicio',
            'status' => 'boolean',
        ];
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
        abort_if(!feature('ciclo.criar'), 403, 'O módulo de ciclos de inscrição está temporariamente desativado no sistema.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('ciclo.criar'), 403, 'Sem permissão');

        $this->resetValidation();
        $this->reset(['nome', 'ano', 'semestre', 'data_inicio', 'data_fim', 'status']);
        
        $this->ano = date('Y');
        $this->semestre = date('n') <= 6 ? 1 : 2;
        $this->modalAberto = true;
    }

    public function salvar()
    {
        abort_if(!feature('ciclo.criar'), 403, 'O módulo de ciclos de inscrição está temporariamente desativado no sistema.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('ciclo.criar'), 403, 'Sem permissão');
        $this->validate();

        if ($this->status) {
            Ciclo::query()->update(['status' => false]);
        }

        $nomeFinal = trim($this->nome);
        if (empty($nomeFinal)) {
            $nomeFinal = "{$this->ano} - {$this->semestre}º Semestre";
        }

        $cicloSalvo = Ciclo::create([
            'nome' => $nomeFinal,
            'ano' => $this->ano,
            'semestre' => $this->semestre,
            'data_inicio' => $this->data_inicio,
            'data_fim' => $this->data_fim,
            'status' => $this->status,
            'slug' => Str::slug($nomeFinal) . '-' . time()
        ]);

        $this->modalAberto = false;
        session()->flash('sucesso', 'Ciclo criado! Agora configure os Cursos e Vagas.');
        
        return redirect()->route('ciclos.edit', $cicloSalvo->id);
    }

    public function delete(int $id)
    {
        abort_if(!feature('ciclo.excluir'), 403, 'O módulo de ciclos de inscrição está temporariamente desativado no sistema.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('ciclo.excluir'), 403);
        $ciclo = Ciclo::findOrFail($id);
        $ciclo->delete();
        $this->dispatch('sucesso', msg: 'Ciclo excluído com sucesso!');
    }

    public function duplicar(int $id)
    {
        abort_if(!feature('ciclo.criar'), 403, 'O módulo de ciclos de inscrição está temporariamente desativado no sistema.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('ciclo.criar'), 403);
        
        $cicloOriginal = Ciclo::with(['cursos', 'statusPipeline', 'unidades', 'turnos'])->findOrFail($id);

        $novoCiclo = $cicloOriginal->replicate();
        $novoCiclo->nome = $cicloOriginal->nome . ' (Cópia)'; 
        $novoCiclo->slug = Str::slug($novoCiclo->nome) . '-' . time(); 
        $novoCiclo->status = false;
        $novoCiclo->save();

        if ($cicloOriginal->cursos) {
            $novoCiclo->cursos()->sync($cicloOriginal->cursos->pluck('id')->toArray());
        }

        if ($cicloOriginal->unidades) {
            $novoCiclo->unidades()->sync($cicloOriginal->unidades->pluck('id')->toArray());
        }

        if ($cicloOriginal->turnos) {
            $novoCiclo->turnos()->sync($cicloOriginal->turnos->pluck('id')->toArray());
        }

        if ($cicloOriginal->statusPipeline) {
            $syncStatus = [];
            foreach ($cicloOriginal->statusPipeline as $status) {
                $syncStatus[$status->id] = ['ordem' => $status->pivot->ordem ?? 1];
            }
            $novoCiclo->statusPipeline()->sync($syncStatus);
        }

        $camposOriginais = \App\Models\CampoFormulario::where('ciclo_id', $id)->get();
        foreach ($camposOriginais as $campo) {
            $novoCampo = $campo->replicate();
            $novoCampo->ciclo_id = $novoCiclo->id;
            $novoCampo->save();
        }

        $ofertasOriginais = \App\Models\OfertaVaga::where('ciclo_id', $id)->get();
        foreach($ofertasOriginais as $oferta) {
            $novaOferta = $oferta->replicate();
            $novaOferta->ciclo_id = $novoCiclo->id;
            $novaOferta->save();
        }

        if (class_exists(\App\Models\Regra::class)) {
            $regrasOriginais = \App\Models\Regra::where('ciclo_id', $id)->get();
            foreach($regrasOriginais as $regra) {
                $novaRegra = $regra->replicate();
                $novaRegra->ciclo_id = $novoCiclo->id;
                $novaRegra->save();
            }
        }

        if (class_exists(\App\Modules\Matricula\Domain\Models\DocumentoExigido::class)) {
            $docsOriginais = \App\Modules\Matricula\Domain\Models\DocumentoExigido::where('ciclo_id', $id)->get();
            foreach($docsOriginais as $doc) {
                $novoDoc = $doc->replicate();
                $novoDoc->ciclo_id = $novoCiclo->id;
                $novoDoc->save();
            }
        }

        $this->dispatch('sucesso', msg: 'Ciclo duplicado com sucesso!');
    }

    // =========================================
    // QUICK VIEW DO CICLO
    // =========================================
    public function showQuickView(int $id)
    {
        // Traz o ciclo, faz contagem de inscritos e carrega as relações
        $ciclo = Ciclo::withCount('inscricoes')
            ->with(['cursos', 'unidades'])
            ->findOrFail($id);

        // Processa totais de Vagas ofertadas e preenchidas
        $vagasOfertadas = \App\Models\OfertaVaga::where('ciclo_id', $id)->sum('vagas');
        $vagasPreenchidas = \App\Models\Inscricao::where('ciclo_id', $id)
            ->whereHas('statusInscricao', function ($q) {
                $q->whereIn('nome', ['Aprovado', 'aprovado', 'Selecionado', 'selecionado']);
            })->count();

        $percentual = $vagasOfertadas > 0 ? round(($vagasPreenchidas / $vagasOfertadas) * 100, 1) : 0;
        $corBarra = $percentual >= 100 ? 'bg-red-500' : ($percentual >= 80 ? 'bg-orange-500' : 'bg-emerald-500');

        // Gera as Labels visuais do HTML para injetar
        $statusLabel = $ciclo->status 
            ? '<span class="px-2 py-1 bg-green-50 text-green-700 text-[10px] uppercase font-bold rounded border border-green-200 shadow-sm"><i class="ph-fill ph-check-circle"></i> ATIVO</span>' 
            : '<span class="px-2 py-1 bg-gray-50 text-gray-500 text-[10px] uppercase font-bold rounded border border-gray-200 shadow-sm"><i class="ph-fill ph-minus-circle"></i> INATIVO</span>';

        // Formata os Cursos
        $cursosTags = $ciclo->cursos->take(8)->pluck('nome')->map(fn($c) => "<span class='px-2 py-1 bg-orange-50 border border-orange-200 rounded-md text-[10px] font-bold text-orange-700'>$c</span>")->implode(' ');
        if ($ciclo->cursos->count() > 8) {
            $cursosTags .= " <span class='text-[10px] font-bold text-gray-400'>+ " . ($ciclo->cursos->count() - 8) . " cursos</span>";
        }

        // Formata as Unidades
        $unidadesTags = $ciclo->unidades->take(8)->pluck('nome')->map(fn($u) => "<span class='px-2 py-1 bg-blue-50 border border-blue-200 text-blue-700 font-bold rounded-md text-[10px]'><i class=\"ph-fill ph-map-pin\"></i> $u</span>")->implode(' ');
        if ($ciclo->unidades->count() > 8) {
            $unidadesTags .= " <span class='text-[10px] font-bold text-gray-400'>+ " . ($ciclo->unidades->count() - 8) . " unidades</span>";
        }

        // Constrói a barra de ocupação HTML para o Modal
        $barraOcupacao = '
            <div class="w-full mt-1 bg-gray-50 p-3 rounded-lg border border-gray-100">
                <div class="flex justify-between text-[10px] font-bold mb-1.5 uppercase tracking-wider">
                    <span class="text-gray-500">'.$vagasPreenchidas.' Preenchidas</span>
                    <span class="text-gray-800">'.$vagasOfertadas.' Total Ofertado</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 flex overflow-hidden shadow-inner">
                    <div class="'.$corBarra.' h-2 rounded-full transition-all duration-500" style="width: '.min($percentual, 100).'%"></div>
                </div>
                <span class="text-[10px] font-bold text-gray-400 mt-1 block">'.$percentual.'% da capacidade ocupada.</span>
            </div>
        ';

        // Dispara o evento global
        $this->dispatch('load-quick-view', [
            'title' => $ciclo->nome,
            'subtitle' => "Semestre {$ciclo->ano}.{$ciclo->semestre} • De " . $ciclo->data_inicio->format('d/m/Y') . " até " . $ciclo->data_fim->format('d/m/Y'),
            'icon' => 'ph-calendar-check',
            'data' => [
                'Status do Ciclo' => $statusLabel,
                'Inscrições Realizadas' => '<span class="font-black text-2xl text-purpura-600 bg-purpura-50 px-3 py-1 rounded-lg border border-purpura-100 shadow-sm inline-flex items-center gap-2"><i class="ph-fill ph-users"></i> '.$ciclo->inscricoes_count.'</span>',
                'Ocupação de Vagas' => $barraOcupacao,
                'Cursos Ofertados' => '<div class="flex flex-wrap gap-1.5 mt-1">' . ($cursosTags ?: '<span class="text-xs font-bold text-gray-400 italic">Nenhum</span>') . '</div>',
                'Unidades Vinculadas' => '<div class="flex flex-wrap gap-1.5 mt-1">' . ($unidadesTags ?: '<span class="text-xs font-bold text-gray-400 italic">Nenhuma</span>') . '</div>',
                'Ações Extras' => '<a href="'.route('ciclos.show', $ciclo->id).'" class="font-bold text-purpura-600 hover:text-purpura-800 hover:underline text-sm flex items-center gap-1 mt-2"><i class="ph-bold ph-arrow-square-out"></i> Acessar Ficha Completa do Ciclo</a>'
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
            ['key' => 'ocupacao', 'label' => 'Ocupação de Vagas', 'sortable' => false, 'class' => 'text-center'],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function render()
    {
        $query = Ciclo::query()->withCount('inscricoes');
        
        $query->addSelect([
            'total_vagas' => \App\Models\OfertaVaga::selectRaw('COALESCE(SUM(vagas), 0)')
                ->whereColumn('ciclo_id', 'ciclos.id'),
                
            'vagas_preenchidas' => \App\Models\Inscricao::selectRaw('COUNT(*)')
                ->whereColumn('ciclo_id', 'ciclos.id')
                ->whereHas('statusInscricao', function ($q) {
                    $q->whereIn('nome', ['Aprovado', 'aprovado', 'Selecionado', 'selecionado']);
                })
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
        ]);
    }
}