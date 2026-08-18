<?php

namespace App\Modules\Comunicacao\UI\Livewire\Comunicado;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Modules\Comunicacao\Domain\Models\Comunicado;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;

class ComunicadoForm extends Component
{
    use WithFileUploads;

    public $template_id = '';
    
    // Arrays que o Alpine.js irá manipular
    public $destinatarios = [];
    public $cc = [];
    public $bcc = [];
    
    public $anexos_upload = [];
    public $tipo_envio = 'imediato'; // 'imediato' ou 'agendado'
    public $data_agendamento = '';

    public function salvar()
    {
        // Limpa os arrays para garantir que não haja vazios
        $this->destinatarios = array_filter(array_map('trim', $this->destinatarios));
        $this->cc = array_filter(array_map('trim', $this->cc));
        $this->bcc = array_filter(array_map('trim', $this->bcc));

        $regras = [
            'template_id' => 'required',
            'destinatarios' => 'required|array|min:1',
            'destinatarios.*' => 'email',
            'cc.*' => 'email',
            'bcc.*' => 'email',
            'anexos_upload.*' => 'max:10240', // Max 10MB por arquivo
        ];

        if ($this->tipo_envio === 'agendado') {
            $regras['data_agendamento'] = 'required|date|after_or_equal:now';
        }

        $this->validate($regras, [
            'template_id.required' => 'Selecione um template.',
            'destinatarios.required' => 'Adicione pelo menos um e-mail de destinatário.',
            'data_agendamento.after_or_equal' => 'A data de agendamento não pode estar no passado.',
        ]);

        $caminhosAnexos = [];
        if (!empty($this->anexos_upload)) {
            foreach ($this->anexos_upload as $anexo) {
                $caminhosAnexos[] = $anexo->store('comunicados/anexos', 'public');
            }
        }

        $dataEnvio = $this->tipo_envio === 'agendado' ? \Carbon\Carbon::parse($this->data_agendamento) : now();

        Comunicado::create([
            'template_id' => $this->template_id,
            'destinatarios' => $this->destinatarios,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'anexos' => $caminhosAnexos,
            'data_agendamento' => $dataEnvio,
            'status' => 'pendente', // Será capturado por uma task/cron posteriormente
        ]);

        session()->flash('sucesso', $this->tipo_envio === 'agendado' ? 'Comunicado agendado com sucesso!' : 'Disparo colocado na fila de envio com sucesso!');
        return redirect()->route('comunicados.index');
    }

    public function render()
    {
        return view('livewire.comunicacao.comunicado.comunicado-form', [
            'templates' => EmailTemplate::orderBy('nome')->get()
        ])->layout('components.layouts.app', ['title' => 'Novo Comunicado']);
    }
}