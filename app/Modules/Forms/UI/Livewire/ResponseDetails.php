<?php

namespace App\Modules\Forms\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\RespostaFormulario;
use App\Models\Formulario;
use App\Models\CampoFormulario;
use App\Traits\WithFichaPdfDownload;

#[Layout('components.layouts.app')]
#[Title('Ficha da Resposta')]
class ResponseDetails extends Component
{
    use WithFichaPdfDownload;

    public RespostaFormulario $resposta;
    public Formulario $formulario;

    public function mount($id)
    {
        $this->resposta = RespostaFormulario::findOrFail($id);
        $this->formulario = Formulario::findOrFail($this->resposta->formulario_id);
    }

    public function render()
    {
        $todosCampos = CampoFormulario::where('formulario_id', $this->formulario->id)
            ->whereNotIn('tipo', ['config', 'html', 'divider', 'media', 'spacer', 'link'])
            ->orderBy('etapa', 'asc')
            ->orderBy('ordem', 'asc')
            ->get();

        $respostasSalvas = is_string($this->resposta->respostas)
            ? (json_decode($this->resposta->respostas, true) ?? [])
            : ($this->resposta->respostas ?? []);

        $nomeParticipante = $this->extrairNomeDeRespostas($todosCampos, $respostasSalvas)
            ?: ('Participante #' . str_pad((string) $this->resposta->id, 5, '0', STR_PAD_LEFT));
        $cpfParticipante = $this->extrairCpfDeRespostas($todosCampos, $respostasSalvas);
        $emailParticipante = $respostasSalvas['_email_coletado'] ?? null;

        return view('livewire.forms.response-details', [
            'camposPorEtapa'    => $todosCampos->groupBy('etapa'),
            'totalPerguntas'    => $todosCampos->count(),
            'respostasSalvas'   => $respostasSalvas,
            'nomeParticipante'  => $nomeParticipante,
            'cpfParticipante'   => $cpfParticipante,
            'emailParticipante' => $emailParticipante,
        ]);
    }
}