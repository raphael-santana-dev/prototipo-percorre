<?php

namespace App\Modules\Period\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Ciclo;
use App\Models\Curso;
use App\Models\StatusInscricao;
use App\Models\OfertaVaga;
use Livewire\WithPagination;
use App\Helpers\BreadcrumbHelper;
use App\Traits\ComPadraoListagem;
use App\Traits\WithToggleStatus;
use Illuminate\Support\Str;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Ciclos - Administrativo')]
class PeriodManager extends Component
{
    use WithPagination;
    use ComPadraoListagem;
    use WithToggleStatus;

    public $modalAberto = false;
    public $unicoAtivo = true;
    public $status = false;
    public $cicloId = null;
    
    public $modelClass = Ciclo::class;

    public array $breadcrumbs = [];

    public $nome, $ano, $semestre, $data_inicio, $data_fim;

    public array $cursosSelecionados = []; 
    public array $statusSelecionados = [];
    
    // NOVA PROPRIEDADE: Matriz Dinâmica de Vagas
    public array $ofertasVagas = [];

    public $filtro_ano = '';
    public $filtro_semestre = '';
    public $filtro_status = '';
    
    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev'), 403, 'Acesso restrito a Desenvolvedores.');
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

    // --- NOVOS MÉTODOS PARA GESTÃO DE VAGAS DINÂMICAS ---
    public function addOferta()
    {
        $this->ofertasVagas[] = [
            'unidade_id' => '',
            'curso_id' => '',
            'turno_id' => '',
            'vagas' => 0
        ];
    }

    public function removeOferta($index)
    {
        unset($this->ofertasVagas[$index]);
        $this->ofertasVagas = array_values($this->ofertasVagas);
    }

    public function duplicar(int $id)
    {
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

        // DUPLICAR OS CAMPOS DO FORMULÁRIO
        $camposOriginais = \App\Models\CampoFormulario::where('ciclo_id', $id)->get();
        foreach ($camposOriginais as $campo) {
            $novoCampo = $campo->replicate();
            $novoCampo->ciclo_id = $novoCiclo->id;
            $novoCampo->save();
        }

        // DUPLICAR A MATRIZ DE OFERTAS/VAGAS
        $ofertasOriginais = OfertaVaga::where('ciclo_id', $id)->get();
        foreach($ofertasOriginais as $oferta) {
            $novaOferta = $oferta->replicate();
            $novaOferta->ciclo_id = $novoCiclo->id;
            $novaOferta->save();
        }

        $this->dispatch('sucesso', msg: 'Ciclo, formulário e limite de vagas duplicados com sucesso!');
    }

    public function showQuickView(int $id)
    {
        // Mantido o código original intacto[cite: 51]
        $ciclo = Ciclo::findOrFail($id);

        $status = $ciclo->status
            ? '<span class="inline-flex items-center px-3 py-1 rounded-full bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 font-semibold">Ativo</span>'
            : '<span class="inline-flex items-center px-3 py-1 rounded-full bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300 font-semibold">Inativo</span>';

        $informacoes = '
            <div class="grid grid-cols-1 gap-2 text-sm">
                <div class="bg-gray-50 dark:bg-gray-800 p-2 rounded border border-gray-100 dark:border-gray-700">
                    <span class="block text-[10px] uppercase text-gray-500 font-bold">Ano</span>
                    <span class="font-medium">'.$ciclo->ano.'</span>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800 p-2 rounded border border-gray-100 dark:border-gray-700">
                    <span class="block text-[10px] uppercase text-gray-500 font-bold">Semestre</span>
                    <span class="font-medium">'.$ciclo->semestre.'º</span>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800 p-2 rounded border border-gray-100 dark:border-gray-700">
                    <span class="block text-[10px] uppercase text-gray-500 font-bold">Início</span>
                    <span class="font-medium">'.$ciclo->data_inicio->format('d/m/Y H:i').'</span>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800 p-2 rounded border border-gray-100 dark:border-gray-700">
                    <span class="block text-[10px] uppercase text-gray-500 font-bold">Encerramento</span>
                    <span class="font-medium">'.$ciclo->data_fim->format('d/m/Y H:i').'</span>
                </div>
            </div>
        ';

        $this->dispatch('load-quick-view', [
            'title' => $ciclo->nome,
            'subtitle' => "Ano {$ciclo->ano} • {$ciclo->semestre}º semestre",
            'icon' => 'ph-calendar',
            'data' => [
                'Status' => $status,
                'Informações do Ciclo' => $informacoes,
                'Período de Inscrição' => '
                    <div class="text-sm leading-6">
                        <b>Início:</b> '.$ciclo->data_inicio->format('d/m/Y H:i').'<br>
                        <b>Fim:</b> '.$ciclo->data_fim->format('d/m/Y H:i').'
                    </div>
                '
            ],
        ]);
    }

    public function abrirModal($id = null)
    {
        $this->resetValidation();
        $this->reset(['cicloId', 'nome', 'ano', 'semestre', 'data_inicio', 'data_fim', 'status', 'cursosSelecionados', 'statusSelecionados', 'ofertasVagas']);

        if ($id) {
            $ciclo = Ciclo::with(['cursos', 'statusPipeline'])->findOrFail($id);
            $this->cicloId = $ciclo->id;
            $this->nome = $ciclo->nome;
            $this->ano = $ciclo->ano;
            $this->semestre = $ciclo->semestre;
            $this->data_inicio = $ciclo->data_inicio->format('Y-m-d\TH:i');
            $this->data_fim = $ciclo->data_fim->format('Y-m-d\TH:i');
            $this->status = $ciclo->status;
            
            $this->cursosSelecionados = $ciclo->cursos->pluck('id')->toArray();
            $this->statusSelecionados = $ciclo->statusPipeline->pluck('id')->toArray();

            // Carrega as ofertas salvas
            $ofertas = OfertaVaga::where('ciclo_id', $id)->get();
            foreach ($ofertas as $oferta) {
                $this->ofertasVagas[] = [
                    'unidade_id' => $oferta->unidade_id,
                    'curso_id' => $oferta->curso_id,
                    'turno_id' => $oferta->turno_id,
                    'vagas' => $oferta->vagas,
                ];
            }
        } else {
            $this->ano = date('Y');
            $this->semestre = date('n') <= 6 ? 1 : 2;
        }

        $this->modalAberto = true;
    }

    public function fecharModal()
    {
        $this->modalAberto = false;
    }

    public function salvar()
    {
        $this->validate();

        if ($this->status) {
            Ciclo::where('id', '!=', $this->cicloId)->update(['status' => false]);
        }

        $nomeFinal = trim($this->nome);
        if (empty($nomeFinal)) {
            $nomeFinal = "{$this->ano} - {$this->semestre}º Semestre";
        }

        $cicloSalvo = Ciclo::updateOrCreate(
            ['id' => $this->cicloId],
            [
                'nome' => $nomeFinal,
                'ano' => $this->ano,
                'semestre' => $this->semestre,
                'data_inicio' => $this->data_inicio,
                'data_fim' => $this->data_fim,
                'status' => $this->status,
                'slug' => Str::slug($nomeFinal)
            ]
        );

        $cicloSalvo->cursos()->sync($this->cursosSelecionados);

        $syncStatus = [];
        foreach ($this->statusSelecionados as $index => $statusId) {
            $syncStatus[$statusId] = ['ordem' => $index + 1];
        }
        $cicloSalvo->statusPipeline()->sync($syncStatus);

        // SALVA AS OFERTAS/VAGAS
        // 1. Apaga as ofertas antigas
        OfertaVaga::where('ciclo_id', $cicloSalvo->id)->delete();
        
        // 2. Insere as novas
        foreach ($this->ofertasVagas as $oferta) {
            if (!empty($oferta['curso_id']) && !empty($oferta['unidade_id']) && !empty($oferta['turno_id'])) {
                OfertaVaga::create([
                    'ciclo_id' => $cicloSalvo->id,
                    'unidade_id' => $oferta['unidade_id'],
                    'curso_id' => $oferta['curso_id'],
                    'turno_id' => $oferta['turno_id'],
                    'vagas' => (int) ($oferta['vagas'] ?? 0),
                ]);
            }
        }

        $this->fecharModal();
        $this->dispatch('sucesso', msg: 'Ciclo e grade de vagas salvos com sucesso!');
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'nome', 'label' => 'Nome / Período', 'sortable' => true],
            ['key' => 'data_inicio', 'label' => 'Abertura', 'sortable' => true],
            ['key' => 'data_fim', 'label' => 'Encerramento', 'sortable' => true],
            ['key' => 'inscricoes_count', 'label' => 'Inscrições', 'sortable' => true, 'class' => 'text-center'],
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
            'cursosDisponiveis' => Curso::where('status', 'Ativo')->orderBy('nome')->get(),
            'statusDisponiveis' => StatusInscricao::orderBy('nome')->get(),
            'anosDisponiveis' => Ciclo::select('ano')->distinct()->orderBy('ano', 'desc')->pluck('ano'),
            'unidadesDisponiveis' => \App\Modules\Unidade\Domain\Models\Unidade::whereIn('status', ['Ativa', '1', true])->orderBy('nome')->get(),
            'turnosDisponiveis' => \App\Modules\Turno\Domain\Models\Turno::where('status', true)->orderBy('nome')->get(),
        ]);
    }
}