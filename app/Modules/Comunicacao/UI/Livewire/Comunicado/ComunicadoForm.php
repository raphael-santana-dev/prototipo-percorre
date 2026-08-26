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
    
    // Novo Modo de Destinatários
    public $modo_selecao = 'manual'; // 'manual' ou 'dinamico'
    public $filtro_publico = ''; // 'todos', 'grupo', 'unidade', 'curso'
    public $filtro_role = '';
    public $filtro_unidade = '';
    public $filtro_curso = '';

    // Arrays de E-mails
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
        
        // 1. Limpeza dos e-mails manuais
        $this->destinatarios = array_filter(array_map('trim', $this->destinatarios));
        $this->cc = array_filter(array_map('trim', $this->cc));
        $this->bcc = array_filter(array_map('trim', $this->bcc));

        // 2. Lógica do Modo Dinâmico
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

        // 3. Validações
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

        // 4. Salva Anexos
        $caminhosAnexos = [];
        if (!empty($this->anexos_upload)) {
            foreach ($this->anexos_upload as $anexo) {
                $caminhosAnexos[] = $anexo->store('comunicados/anexos', 'public');
            }
        }

        $dataEnvio = $this->tipo_envio === 'agendado' ? \Carbon\Carbon::parse($this->data_agendamento) : now();

        // 5. Criação do Registro Principal
        $comunicado = Comunicado::create([
            'template_id' => $this->template_id,
            'destinatarios' => $this->destinatarios,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'anexos' => $caminhosAnexos,
            'data_agendamento' => $dataEnvio,
            'status' => 'pendente', 
        ]);

        // 6. GERAÇÃO DOS LOGS INDIVIDUAIS (PARA PREVIEW E ENVIO)
        $template = EmailTemplate::find($this->template_id);
        foreach ($this->destinatarios as $email) {
            $user = \App\Models\User::where('email', $email)->first();
            $inscricao = \App\Models\Inscricao::where('email', $email)->latest()->first();
            $contexto = ['user' => $user, 'inscricao' => $inscricao];

            \App\Modules\Comunicacao\Domain\Models\ComunicacaoLog::create([
                'comunicado_id' => $comunicado->id,
                'origem' => 'comunicado',
                'destinatario' => $email,
                'assunto' => \App\Modules\Comunicacao\Services\EmailParserService::parse($template->assunto, $contexto),
                'corpo' => \App\Modules\Comunicacao\Services\EmailParserService::parse($template->corpo, $contexto),
                'anexos' => $caminhosAnexos,
                'data_agendamento' => $dataEnvio,
                'status' => 'pendente'
            ]);
        }

        // 7. Se for imediato, já manda pro Worker
        if ($this->tipo_envio === 'imediato') {
            \App\Modules\Comunicacao\Jobs\ProcessarComunicadoJob::dispatch($comunicado);
        }

        $this->dispatch('sucesso', msg: $this->tipo_envio === 'agendado' ? 'Comunicado agendado com sucesso!' : 'Disparo colocado na fila de envio!');
        return redirect()->route('comunicados.index');
    }

    public function render()
    {
        // Busca os dados para alimentar os dropdowns usando DB query direta por segurança de caminhos
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