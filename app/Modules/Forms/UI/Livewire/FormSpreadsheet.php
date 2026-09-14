<?php

namespace App\Modules\Forms\UI\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Formulario;
use App\Models\RespostaFormulario;
use App\Models\CampoFormulario;
use Illuminate\Support\Str;

#[Layout('components.layouts.app')]
#[Title('Planilha de Respostas')]
class FormSpreadsheet extends Component
{
    use WithPagination;

    public Formulario $formulario;
    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $porPagina = 50;

    public function mount($id)
    {
        abort_if(!feature('formulario.respostas'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('formulario.respostas'), 403);

        $this->formulario = Formulario::findOrFail($id);
    }

    public function updatingSearch()
    {
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

    public function solicitarExportacao($formato = 'csv')
    {
        $queryCount = RespostaFormulario::where('formulario_id', $this->formulario->id)->count();

        if ($queryCount === 0) {
            $this->dispatch('erro', msg: 'Não há registros para exportar.');
            return;
        }

        $tracking = \App\Models\Importacao::create([
            'user_id' => auth()->id(),
            'tipo' => 'respostas_formulario', // Identificador para o I/O
            'operacao' => 'exportacao',
            'formato' => strtolower($formato),
            'arquivo_nome' => 'Respostas - ' . Str::limit($this->formulario->titulo, 20) . ' (' . strtoupper($formato) . ')',
            'status' => 'na_fila',
            'total_linhas' => $queryCount,
            'linhas_processadas' => 0,
        ]);

        $filtrosAtuais = [
            'search' => $this->search,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
        ];

        // Dispara o Job em background
        dispatch(new \App\Jobs\ExportarRespostasFormularioJob($tracking->id, $this->formulario->id, $filtrosAtuais))->afterResponse();

        $this->dispatch('sucesso', msg: 'Exportação enviada para o plano de fundo! Acompanhe a geração do arquivo no Gerenciador (I/O).');
    }

    public function render()
    {
        $query = RespostaFormulario::where('formulario_id', $this->formulario->id);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('id', 'like', "%{$this->search}%")
                  ->orWhere('respostas', 'like', "%{$this->search}%"); // Busca bruta no JSON
            });
        }

        // Ordenação dinâmica (Suporta JSON no Postgres: respostas->campo)
        $query->orderBy($this->sortField, $this->sortDirection);

        return view('livewire.forms.form-spreadsheet', [
            'respostas' => $query->paginate($this->porPagina),
            'campos' => $this->campos
        ]);
    }
}