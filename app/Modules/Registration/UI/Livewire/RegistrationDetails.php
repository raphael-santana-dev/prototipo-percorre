<?php

namespace App\Modules\Registration\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Inscricao;
use App\Models\StatusInscricao;

#[Layout('components.layouts.app')]
#[Title('Detalhes da Inscrição')]
class RegistrationDetails extends Component
{
    public Inscricao $inscricao;
    public $status_selecionado; 

    public function mount($id)
    {
        abort_if(!feature('inscricao.visualizar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('inscricao.visualizar'), 403);

        $this->inscricao = Inscricao::with(['unidade', 'curso', 'turno', 'ciclo'])->findOrFail($id);
        $this->status_selecionado = $this->inscricao->status_inscricao_id;
    }

    public function atualizarStatus()
    {
        abort_if(!feature('inscricao.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('inscricao.editar'), 403);

        // TRAVA: Impede salvar se o status for exatamente o mesmo que o atual
        if ($this->inscricao->status_inscricao_id == $this->status_selecionado) {
            $this->dispatch('erro', msg: 'O candidato já se encontra neste status!');
            return;
        }

        $statusNovo = StatusInscricao::find($this->status_selecionado);
        if (!$statusNovo) return;

        // Atualiza apenas o banco
        $this->inscricao->status_inscricao_id = $statusNovo->id;
        $this->inscricao->save();

        // Delega 100% da inteligência para o Motor Central (Ele decide se cria aluno e manda e-mail)
        $eventoGatilho = 'inscricao.status.' . \Illuminate\Support\Str::slug($statusNovo->nome, '_');
        \App\Modules\Comunicacao\Services\AutomacaoService::disparar($eventoGatilho, $this->inscricao);

        $this->inscricao->refresh(); 
        $this->dispatch('sucesso', msg: 'Status atualizado com sucesso!');
    }

    public function render()
    {
        $todosStatus = StatusInscricao::orderBy('nome')->get();

        return view('livewire.registration.registration-details', [
            'todosStatus' => $todosStatus
        ]);
    }
}