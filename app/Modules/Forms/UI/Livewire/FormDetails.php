<?php

namespace App\Modules\Forms\UI\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Formulario;
use App\Models\RespostaFormulario;
use App\Models\CampoFormulario;
use App\Traits\ComPadraoListagem;
use Illuminate\Support\Str;

#[Layout('components.layouts.app')]
#[Title('Detalhes do Formulário')]
class FormDetails extends Component
{
    use WithPagination, ComPadraoListagem;

    public Formulario $formulario;
    
    public $search = '';
    public $data_inicio = '';
    public $data_fim = '';
    public array $filtrosDinamicos = []; // NOVO: Armazena os valores dos filtros dinâmicos por campo

    public array $selecionadas = [];
    public bool $selecionarTudo = false;

    public function mount($id)
    {
        //abort_if(!feature('formulario.detalhes'), 403);
        //abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('formulario.respostas'), 403);

        $this->formulario = Formulario::findOrFail($id);
        $this->ordenacaoCampo = 'created_at';
        $this->ordenacaoDirecao = 'desc';
    }

    public function updating($property)
    {
        if (in_array($property, ['search', 'data_inicio', 'data_fim']) || str_starts_with($property, 'filtrosDinamicos.')) {
            $this->resetPage();
            $this->selecionarTudo = false;
        }
    }

    public function limparFiltros()
    {
        $this->reset(['search', 'data_inicio', 'data_fim', 'filtrosDinamicos']);
        $this->resetPage();
    }

    public function updatedSelecionarTudo($value)
    {
        if ($value) {
            $idsPagina = $this->obterQueryFiltrada()
                ->paginate($this->porPagina ?? 15)
                ->pluck('id')
                ->map(fn($id) => (string) $id)
                ->toArray();

            $this->selecionadas = array_values(array_unique(array_merge($this->selecionadas, $idsPagina)));
        } else {
            $this->selecionadas = [];
        }
    }

    public function desmarcarTodas()
    {
        $this->selecionadas = [];
        $this->selecionarTudo = false;
    }

    public function solicitarExportacao($formato = 'csv')
    {
        $query = $this->obterQueryFiltrada();

        if (!empty($this->selecionadas)) {
            $query->whereIn('id', $this->selecionadas);
        }

        $queryCount = $query->count();

        if ($queryCount === 0) {
            $this->dispatch('erro', msg: 'Não há registros para exportar com base nesta seleção ou busca.');
            return;
        }

        $tracking = \App\Models\SystemTask::create([
            'user_id' => auth()->id(),
            'tipo' => 'respostas_formulario',
            'operacao' => 'exportacao',
            'formato' => strtolower($formato),
            'arquivo_nome' => 'Respostas - ' . Str::limit($this->formulario->titulo, 20) . ' (' . strtoupper($formato) . ')',
            'status' => 'na_fila',
            'total_linhas' => $queryCount,
            'linhas_processadas' => 0,
        ]);

        $filtrosAtuais = [
            'search' => $this->search,
            'data_inicio' => $this->data_inicio,
            'data_fim' => $this->data_fim,
            'filtrosDinamicos' => $this->filtrosDinamicos,
            'ids' => $this->selecionadas,
            'sortField' => str_starts_with($this->ordenacaoCampo, 'respostas->') || in_array($this->ordenacaoCampo, ['id', 'created_at']) ? $this->ordenacaoCampo : 'created_at',
            'sortDirection' => $this->ordenacaoDirecao ?? 'desc',
        ];

        dispatch(new \App\Jobs\ExportarRespostasFormularioJob($tracking->id, $this->formulario->id, $filtrosAtuais))->afterResponse();

        $this->dispatch('sucesso', msg: 'Exportação enviada para o plano de fundo! Acompanhe a geração do arquivo no Gerenciador (I/O).');
    }

    public function getHeadersProperty()
    {
        $headers = [
            ['key' => 'selecao', 'label' => '#', 'sortable' => false, 'class' => 'w-10 text-center'],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'w-16 text-center'],
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'created_at', 'label' => 'Data do Envio', 'sortable' => true],
        ];

        foreach ($this->getCamposDinamicos() as $campo) {
            $headers[] = [
                'key' => 'respostas->' . $campo->name,
                'label' => Str::limit($campo->label, 30),
                'sortable' => true
            ];
        }

        return $headers;
    }

    public function getCamposFiltraveisProperty()
    {
        // Retorna apenas campos que fazem sentido para filtragem (ignora visuais, divisórias, etc.)
        return CampoFormulario::where('formulario_id', $this->formulario->id)
            ->whereNotIn('tipo', ['config', 'html', 'divider', 'media', 'social', 'spacer', 'link', 'signature', 'file'])
            ->orderBy('etapa')
            ->orderBy('ordem')
            ->get();
    }

    private function getCamposDinamicos()
    {
        return CampoFormulario::where('formulario_id', $this->formulario->id)
            ->whereNotIn('tipo', ['config', 'html', 'divider', 'media', 'social', 'spacer', 'link'])
            ->orderBy('etapa')
            ->orderBy('ordem')
            ->get();
    }

    protected function obterQueryFiltrada()
    {
        $query = RespostaFormulario::where('formulario_id', $this->formulario->id)
            ->when($this->search, function ($q) {
                $termo = trim($this->search);
                $q->where(function ($sub) use ($termo) {
                    $sub->where('id', 'like', "%{$termo}%")
                        ->orWhere('respostas', 'ilike', "%{$termo}%");
                });
            })
            ->when($this->data_inicio, fn($q) => $q->whereDate('created_at', '>=', $this->data_inicio))
            ->when($this->data_fim, fn($q) => $q->whereDate('created_at', '<=', $this->data_fim));

        // Aplica os filtros dinâmicos iterando sobre cada campo preenchido
        if (!empty($this->filtrosDinamicos)) {
            foreach ($this->filtrosDinamicos as $nomeCampo => $valorFiltro) {
                if ($valorFiltro === null || $valorFiltro === '') {
                    continue;
                }

                $campoObj = CampoFormulario::where('formulario_id', $this->formulario->id)->where('name', $nomeCampo)->first();
                if (!$campoObj) continue;

                if (in_array($campoObj->subtipo, ['date', 'datetime-local'])) {
                    // Filtro de data De/Até (espera array com 'de' e 'ate')
                    if (is_array($valorFiltro)) {
                        if (!empty($valorFiltro['de'])) {
                            $query->whereRaw("respostas->>? >= ?", [$nomeCampo, $valorFiltro['de']]);
                        }
                        if (!empty($valorFiltro['ate'])) {
                            $query->whereRaw("respostas->>? <= ?", [$nomeCampo, $valorFiltro['ate']]);
                        }
                    }
                } elseif (in_array($campoObj->tipo, ['select', 'radio'])) {
                    // Filtro de seleção exata
                    $query->whereRaw("respostas->>? = ?", [$nomeCampo, $valorFiltro]);
                } else {
                    // Filtro de texto parcial (ilike)
                    $query->whereRaw("respostas->>? ilike ?", [$nomeCampo, "%{$valorFiltro}%"]);
                }
            }
        }

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    public function render()
    {
        $respostas = $this->obterQueryFiltrada()->paginate($this->porPagina ?? 15);
        $totalRespostas = RespostaFormulario::where('formulario_id', $this->formulario->id)->count();

        return view('livewire.forms.form-details', [
            'respostas' => $respostas,
            'totalRespostas' => $totalRespostas,
            'campos' => $this->getCamposDinamicos()
        ]);
    }
}