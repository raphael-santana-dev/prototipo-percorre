<?php

namespace App\Modules\Registration\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Inscricao;
use App\Models\StatusInscricao;
use App\Modules\Student\Domain\Models\Student;

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

        // Carrega a inscrição e seus relacionamentos
        $this->inscricao = Inscricao::with(['unidade', 'curso', 'turno', 'ciclo'])->findOrFail($id);
        
        $this->status_selecionado = $this->inscricao->status_inscricao_id;
    }

    public function atualizarStatus()
    {
        abort_if(!feature('inscricao.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('inscricao.editar'), 403);

        $statusNovo = StatusInscricao::find($this->status_selecionado);
        if (!$statusNovo) return;

        if (strtolower($statusNovo->nome) === 'aprovado') {
            if (empty($this->inscricao->token_matricula)) {
                $this->inscricao->token_matricula = \Illuminate\Support\Str::random(60);
            }

            if (!$this->inscricao->student_id) {
                $estudante = \App\Modules\Student\Domain\Models\Student::firstOrCreate(
                    ['email' => $this->inscricao->email],
                    [
                        'name' => $this->inscricao->nome,
                        'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(12)),
                        'is_active' => true,
                    ]
                );
                $this->inscricao->student_id = $estudante->id;
            }
        }

        $this->inscricao->status_inscricao_id = $statusNovo->id;
        $this->inscricao->save();

        $eventoGatilho = 'inscricao.status.' . \Illuminate\Support\Str::slug($statusNovo->nome, '_');
        $automacao = \App\Modules\Comunicacao\Domain\Models\Automacao::where('evento_gatilho', $eventoGatilho)
                                                                       ->where('status', true)
                                                                       ->first();
        if ($automacao) {
            dispatch(new \App\Jobs\ProcessarDisparoAutomacaoJob($automacao, $this->inscricao));
        }

        $this->inscricao->refresh(); 
        $this->dispatch('sucesso', msg: 'Status atualizado com sucesso!');
    }

    public function render()
    {
        // Traz todos os status para montar o Select do painel
        $todosStatus = StatusInscricao::orderBy('nome')->get();

        return view('livewire.registration.registration-details', [
            'todosStatus' => $todosStatus
        ]);
    }
}