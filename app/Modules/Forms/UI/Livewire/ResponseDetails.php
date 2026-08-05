<?php

namespace App\Modules\Forms\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\RespostaFormulario;
use App\Models\Formulario;
use App\Models\CampoFormulario;

#[Layout('components.layouts.app')]
#[Title('Leitura de Resposta')]
class ResponseDetails extends Component
{
    public RespostaFormulario $resposta;
    public Formulario $formulario;

    // REMOVEMOS A VARIÁVEL PUBLIC $camposPorEtapa DAQUI

    public function mount($id)
    {
        $this->resposta = RespostaFormulario::findOrFail($id);
        $this->formulario = Formulario::findOrFail($this->resposta->formulario_id);
    }

    public function render()
    {
        // Movemos a consulta e o agrupamento para dentro do render(). 
        // Assim, o Livewire usa os dados apenas para desenhar a tela, 
        // sem tentar serializar a "Collection de Collections" e gerar erro.
        $campos = CampoFormulario::where('formulario_id', $this->formulario->id)
            ->whereNotIn('tipo', ['config', 'html', 'divider', 'media', 'social'])
            ->orderBy('etapa')
            ->orderBy('ordem')
            ->get();

        $camposPorEtapa = $campos->groupBy('etapa');

        return view('livewire.forms.response-details', [
            'camposPorEtapa' => $camposPorEtapa
        ]);
    }
}