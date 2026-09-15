<?php

namespace App\Modules\Forms\UI\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Formulario;
use App\Models\RespostaFormulario;
use App\Models\CampoFormulario;
use App\Models\Curso;
use App\Modules\Unidade\Domain\Models\Unidade;
use App\Modules\Turno\Domain\Models\Turno;
use Illuminate\Support\Str;

#[Layout('components.layouts.app')]
#[Title('Planilha de Respostas')]
class FormSpreadsheet extends Component
{
    use WithPagination;

    public Formulario $formulario;
    
    public $search = '';
    public $data_inicio = '';
    public $data_fim = '';
    public $filtro_curso = '';
    public $filtro_unidade = '';
    public $filtro_turno = '';

    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $porPagina = 50;

    public function mount($id)
    {
        abort_if(!feature('formulario.respostas'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('formulario.respostas'), 403);

        $this->formulario = Formulario::findOrFail($id);
    }

    public function updating($property)
    {
        if (in_array($property, ['search', 'data_inicio', 'data_fim', 'filtro_curso', 'filtro_unidade', 'filtro_turno'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros()
    {
        $this->reset(['search', 'data_inicio', 'data_fim', 'filtro_curso', 'filtro_unidade', 'filtro_turno']);
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function getCamposProperty()
    {
        return CampoFormulario::where('formulario_id', $this->formulario->id)
            ->whereNotIn('tipo', ['config', 'html', 'divider', 'media', 'social'])
            ->orderBy('etapa')
            ->orderBy('ordem')
            ->get();
    }

    protected function getQuery()
    {
        $query = RespostaFormulario::where('formulario_id', $this->formulario->id);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('id', 'like', "%{$this->search}%")
                  ->orWhere('respostas', 'ilike', "%{$this->search}%");
            });
        }
        if ($this->data_inicio) {
            $query->where('created_at', '>=', $this->data_inicio . ' 00:00:00');
        }
        if ($this->data_fim) {
            $query->where('created_at', '<=', $this->data_fim . ' 23:59:59');
        }
        if ($this->filtro_curso) {
            $curso = Curso::find($this->filtro_curso);
            if ($curso) $query->where('respostas', 'ilike', "%{$curso->nome}%");
        }
        if ($this->filtro_unidade) {
            $unidade = Unidade::find($this->filtro_unidade);
            if ($unidade) $query->where('respostas', 'ilike', "%{$unidade->nome}%");
        }
        if ($this->filtro_turno) {
            $turno = Turno::find($this->filtro_turno);
            if ($turno) $query->where('respostas', 'ilike', "%{$turno->nome}%");
        }

        return $query;
    }

    public function solicitarExportacao($formato = 'csv')
    {
        $queryCount = $this->getQuery()->count();

        if ($queryCount === 0) {
            $this->dispatch('erro', msg: 'Não há registros para exportar com base nesta busca.');
            return;
        }

        $tracking = \App\Models\Importacao::create([
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
            'filtro_curso' => $this->filtro_curso,
            'filtro_unidade' => $this->filtro_unidade,
            'filtro_turno' => $this->filtro_turno,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
        ];

        dispatch(new \App\Jobs\ExportarRespostasFormularioJob($tracking->id, $this->formulario->id, $filtrosAtuais))->afterResponse();

        $this->dispatch('sucesso', msg: 'Exportação processada em background. Acompanhe a aba de Integrações (I/O).');
    }

    public function render()
    {
        $query = $this->getQuery();
        $query->orderBy($this->sortField, $this->sortDirection);

        return view('livewire.forms.form-spreadsheet', [
            'respostas' => $query->paginate($this->porPagina),
            'campos' => $this->campos,
            'cursosDb' => Curso::orderBy('nome')->get(),
            'unidadesDb' => Unidade::orderBy('nome')->get(),
            'turnosDb' => Turno::orderBy('nome')->get(),
        ]);
    }
}