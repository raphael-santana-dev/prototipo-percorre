<?php

namespace App\Modules\Period\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Ciclo;
use App\Models\Curso; // Importação necessária
use Livewire\WithPagination;
use App\Helpers\BreadcrumbHelper;
use App\Traits\ComPadraoListagem;
use App\Traits\WithToggleStatus;

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
    
    // Novo array para guardar os cursos marcados no modal
    public array $cursosSelecionados = []; 
    
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

    public function showQuickView(int $id)
    {
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

                <div class="bg-gray-50 dark:bg-gray-800 p-2 rounded border border-gray-100 dark:border-gray-700">
                    <span class="block text-[10px] uppercase text-gray-500 font-bold">Criado em</span>
                    <span class="font-medium">'.$ciclo->created_at->format('d/m/Y H:i').'</span>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 p-2 rounded border border-gray-100 dark:border-gray-700">
                    <span class="block text-[10px] uppercase text-gray-500 font-bold">Última atualização</span>
                    <span class="font-medium">'.$ciclo->updated_at->format('d/m/Y H:i').'</span>
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
        // Reseta também o array de cursos ao abrir o modal
        $this->reset(['cicloId', 'nome', 'ano', 'semestre', 'data_inicio', 'data_fim', 'status', 'cursosSelecionados']);

        if ($id) {
            $ciclo = Ciclo::with('cursos')->findOrFail($id);
            $this->cicloId = $ciclo->id;
            $this->nome = $ciclo->nome;
            $this->ano = $ciclo->ano;
            $this->semestre = $ciclo->semestre;
            $this->data_inicio = $ciclo->data_inicio->format('Y-m-d\TH:i');
            $this->data_fim = $ciclo->data_fim->format('Y-m-d\TH:i');
            $this->status = $ciclo->status;
            
            // Povoa os checkboxes com os IDs dos cursos já vinculados
            $this->cursosSelecionados = $ciclo->cursos->pluck('id')->toArray();
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

        // Recuperamos a instância do ciclo criado/atualizado na variável $cicloSalvo
        $cicloSalvo = Ciclo::updateOrCreate(
            ['id' => $this->cicloId],
            [
                'nome' => $nomeFinal,
                'ano' => $this->ano,
                'semestre' => $this->semestre,
                'data_inicio' => $this->data_inicio,
                'data_fim' => $this->data_fim,
                'status' => $this->status,
            ]
        );

        // MÁGICA: Sincroniza a tabela pivô 'ciclo_curso' automaticamente
        $cicloSalvo->cursos()->sync($this->cursosSelecionados);

        $this->fecharModal();
        session()->flash('sucesso', 'Ciclo salvo com sucesso!');
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
        
        // Busca os cursos ativos para exibir no modal
        $cursosDisponiveis = Curso::where('status', 'Ativo')->orderBy('nome')->get();

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('id', 'desc');
        }

        $ciclos = $query->paginate($this->porPagina);

        return view('livewire.period.period-manager', [
            'registros' => $ciclos,
            'cursosDisponiveis' => $cursosDisponiveis
        ]);
    }
}