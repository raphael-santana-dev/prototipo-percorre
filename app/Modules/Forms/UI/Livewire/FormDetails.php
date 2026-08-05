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
    
    public $tipoVisao = 'resumo'; 
    public $search = ''; // VARIÁVEL RESTAURADA AQUI!

    public function mount($id)
    {
        $this->formulario = Formulario::findOrFail($id);
        $this->ordenacaoCampo = 'created_at';
        $this->ordenacaoDirecao = 'desc';
    }

    // FUNÇÃO RESTAURADA: Reseta a página ao buscar
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedTipoVisao()
    {
        if (str_starts_with($this->ordenacaoCampo, 'respostas->')) {
            $this->ordenacaoCampo = 'created_at';
            $this->ordenacaoDirecao = 'desc';
        }
    }

    public function getHeadersProperty()
    {
        $headers = [
            ['key' => 'id', 'label' => 'Protocolo', 'sortable' => true],
            ['key' => 'created_at', 'label' => 'Data do Envio', 'sortable' => true],
        ];

        if ($this->tipoVisao === 'tabela') {
            $campos = $this->getCamposDinamicos();
            foreach ($campos as $campo) {
                $headers[] = [
                    'key' => 'respostas->' . $campo->name,
                    'label' => Str::limit($campo->label, 30),
                    'sortable' => true
                ];
            }
        } else {
            $headers[] = ['key' => 'etapa_parada', 'label' => 'Progresso', 'sortable' => true];
        }

        $headers[] = [
            'key' => 'acoes', 
            'label' => 'Ações', 
            'sortable' => false, 
            'class' => 'text-right sticky right-0 bg-white shadow-[-4px_0_6px_-2px_rgba(0,0,0,0.05)] z-20'
        ];

        return $headers;
    }

    private function getCamposDinamicos()
    {
        return CampoFormulario::where('formulario_id', $this->formulario->id)
            ->whereNotIn('tipo', ['config', 'html', 'divider', 'media', 'social'])
            ->orderBy('etapa')
            ->orderBy('ordem')
            ->get();
    }

    public function render()
    {
        $query = RespostaFormulario::where('formulario_id', $this->formulario->id)
            ->when($this->search, function ($q) {
                $q->where('id', 'like', "%{$this->search}%");
            });

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $respostas = $query->paginate($this->porPagina ?? 15);
        $totalRespostas = RespostaFormulario::where('formulario_id', $this->formulario->id)->count();

        return view('livewire.forms.form-details', [
            'respostas' => $respostas,
            'totalRespostas' => $totalRespostas,
            'campos' => $this->getCamposDinamicos()
        ]);
    }
}