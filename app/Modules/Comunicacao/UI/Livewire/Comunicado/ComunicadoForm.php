<?php

namespace App\Modules\Comunicacao\UI\Livewire\Comunicado;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Inscricao;
use App\Modules\Comunicacao\Domain\Models\Comunicado;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;

class ComunicadoForm extends Component
{
    use WithFileUploads;

    public $template_id = '';
    
    public $modo_selecao = 'manual'; 
    public $filtro_publico = ''; 
    public $filtro_role = '';
    public $filtro_unidade = '';
    public $filtro_curso = '';

    public $destinatarios = [];
    public $cc = [];
    public $bcc = [];
    
    public $anexos_upload = [];
    public $tipo_envio = 'imediato';
    public $data_agendamento = '';

    public function mount()
    {
        abort_if(!feature('comunicado.criar'), 403, 'Módulo desativado.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('comunicado.criar'), 403);
    }

    public function salvar()
    {
        abort_if(!feature('comunicado.criar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('comunicado.criar'), 403);
        
        $this->destinatarios = array_filter(array_map('trim', $this->destinatarios));
        $this->cc = array_filter(array_map('trim', $this->cc));
        $this->bcc = array_filter(array_map('trim', $this->bcc));

        if ($this->modo_selecao === 'dinamico' && !empty($this->filtro_publico)) {
            $emailsBuscados = [];
            if ($this->filtro_publico === 'todos') {
                $emailsBuscados = \App\Models\User::whereNotNull('email')->pluck('email')->toArray();
            } elseif ($this->filtro_publico === 'grupo' && !empty($this->filtro_role)) {
                $emailsBuscados = \App\Models\User::role($this->filtro_role)->whereNotNull('email')->pluck('email')->toArray();
            } elseif ($this->filtro_publico === 'unidade' && !empty($this->filtro_unidade)) {
                $emailsBuscados = \App\Models\Inscricao::where('unidade_id', $this->filtro_unidade)->whereNotNull('email')->pluck('email')->toArray();
            } elseif ($this->filtro_publico === 'curso' && !empty($this->filtro_curso)) {
                $emailsBuscados = \App\Models\Inscricao::where('curso_id', $this->filtro_curso)->whereNotNull('email')->pluck('email')->toArray();
            }
            $this->destinatarios = array_unique(array_merge($this->destinatarios, $emailsBuscados));
        }

        $regras = [
            'template_id' => 'required',
            'destinatarios' => 'required|array|min:1',
            'destinatarios.*' => 'email',
            'anexos_upload.*' => 'max:10240', 
        ];

        if ($this->tipo_envio === 'agendado') {
            $regras['data_agendamento'] = 'required|date|after_or_equal:now';
        }

        $this->validate($regras, [
            'template_id.required' => 'Selecione um template.',
            'destinatarios.required' => 'Nenhum destinatário válido encontrado ou informado.',
            'data_agendamento.after_or_equal' => 'A data de agendamento não pode estar no passado.',
        ]);

        $caminhosAnexos = [];
        if (!empty($this->anexos_upload)) {
            foreach ($this->anexos_upload as $anexo) {
                $caminhosAnexos[] = $anexo->store('comunicados/anexos', 'public');
            }
        }

        $dataEnvio = $this->tipo_envio === 'agendado' ? \Carbon\Carbon::parse($this->data_agendamento) : now();
        $inscricaoId = null;

        if (isset($this->inscricao) && !empty($this->inscricao)) {
            $inscricaoId = $this->inscricao->id;
        }

        $comunicado = Comunicado::create([
            'template_id' => $this->template_id,
            'inscricao_id' => $inscricaoId,
            'destinatarios' => $this->destinatarios,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'anexos' => $caminhosAnexos,
            'data_agendamento' => $dataEnvio,
            'status' => 'pendente', 
        ]);

        $template = EmailTemplate::find($this->template_id);
        foreach ($this->destinatarios as $email) {
            $user = \App\Models\User::where('email', $email)->first();
            $inscricao = \App\Models\Inscricao::where('email', $email)->latest()->first();

            \App\Modules\Comunicacao\Domain\Models\ComunicacaoLog::create([
                'comunicado_id' => $comunicado->id,
                'origem' => 'comunicado',
                'destinatario' => $email,
                'assunto' => \App\Modules\Comunicacao\Services\EmailParserService::parseTexto($template->assunto, $inscricao, ['user' => $user]),
                'corpo' => \App\Modules\Comunicacao\Services\EmailParserService::parseTexto($template->corpo, $inscricao, ['user' => $user]),
                'anexos' => $caminhosAnexos,
                'data_agendamento' => $dataEnvio,
                'status' => 'pendente'
            ]);
        }

        if ($this->tipo_envio === 'imediato') {
            \App\Modules\Comunicacao\Jobs\ProcessarComunicadoJob::dispatch($comunicado);
        }

        $this->dispatch('sucesso', msg: $this->tipo_envio === 'agendado' ? 'Comunicado agendado com sucesso!' : 'Disparo colocado na fila de envio!');
        return redirect()->route('comunicados.index');
    }

    public function render()
    {
        $rolesDisponiveis = DB::table('roles')->orderBy('name')->get();
        $unidadesDisponiveis = DB::table('unidades')->orderBy('nome')->get();
        $cursosDisponiveis = DB::table('cursos')->orderBy('nome')->get();

        return view('livewire.comunicacao.comunicado.comunicado-form', [
            'templates' => EmailTemplate::orderBy('nome')->get(),
            'rolesDisponiveis' => $rolesDisponiveis,
            'unidadesDisponiveis' => $unidadesDisponiveis,
            'cursosDisponiveis' => $cursosDisponiveis,
        ])->layout('components.layouts.app', ['title' => 'Novo Comunicado']);
    }
}