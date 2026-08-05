<?php

namespace App\Modules\Forms\UI\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Formulario;
use App\Models\RespostaFormulario;

#[Layout('components.layouts.app')]
#[Title('Detalhes do Formulário')]
class FormDetails extends Component
{
    use WithPagination;

    public Formulario $formulario;
    public $search = '';

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
        $respostas = RespostaFormulario::where('formulario_id', $this->formulario->id)
            ->when($this->search, function ($query) {
                // Se tivesse um campo de nome na resposta, poderiamos buscar aqui.
                // Como as respostas estão em JSON, a busca pode ser por ID no banco relacional
                $query->where('id', 'like', "%{$this->search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('livewire.forms.form-details', [
            'respostas' => $respostas
        ]);
    }
}