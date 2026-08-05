<?php

namespace App\Modules\Forms\UI\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Formulario;
use App\Models\RespostaFormulario;
use App\Models\CampoFormulario;

#[Layout('components.layouts.app')]
#[Title('Detalhes do Formulário')]
class FormDetails extends Component
{
    use WithPagination;

    public Formulario $formulario;
    public $search = '';
    
    // Variável para alternar entre "resumo" e "tabela"
    public $modoExibicao = 'resumo'; 

    public function mount($id)
    {
        $this->formulario = Formulario::withCount('respostas')->findOrFail($id);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        // 1. Busca as respostas paginadas
        $respostas = RespostaFormulario::where('formulario_id', $this->formulario->id)
            ->when($this->search, function ($query) {
                $query->where('id', 'like', "%{$this->search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // 2. Busca todas as perguntas estruturais do formulário para formar as colunas da tabela
        $campos = CampoFormulario::where('formulario_id', $this->formulario->id)
            ->whereNotIn('tipo', ['config', 'html', 'divider', 'media', 'social'])
            ->orderBy('etapa')
            ->orderBy('ordem')
            ->get();

        return view('livewire.forms.form-details', [
            'respostas' => $respostas,
            'campos'    => $campos
        ]);
    }
}