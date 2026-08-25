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
        
        // Redireciona direto para a nova tela de edição
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
        $cicloOriginal = Ciclo::with(['cursos', 'statusPipeline'])->findOrFail($id);

        $novoCiclo = $cicloOriginal->replicate();
        $novoCiclo->nome = $cicloOriginal->nome . ' (Cópia)';
        $novoCiclo->slug = Str::slug($novoCiclo->nome) . '-' . time();
        $novoCiclo->status = false; 
        $novoCiclo->save();

        if ($cicloOriginal->cursos) {
            $novoCiclo->cursos()->sync($cicloOriginal->cursos->pluck('id')->toArray());
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

        $this->dispatch('sucesso', msg: 'Ciclo duplicado com sucesso!');
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'nome', 'label' => 'Nome / Período', 'sortable' => true],
            ['key' => 'data_inicio', 'label' => 'Abertura', 'sortable' => true],
            ['key' => 'data_fim', 'label' => 'Encerramento', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function render()
    {
        $query = Ciclo::query()->withCount('inscricoes');
        
        $query->when($this->filtro_ano, fn($q) => $q->where('ano', $this->filtro_ano))
              ->when($this->filtro_semestre, fn($q) => $q->where('semestre', $this->filtro_semestre))
              ->when($this->filtro_status !== '', fn($q) => $q->where('status', $this->filtro_status));
        
        if ($this->ordenacaoCampo) {
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