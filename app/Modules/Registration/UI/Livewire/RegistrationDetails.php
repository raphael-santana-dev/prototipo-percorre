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
    public bool $modalAntiSpamAberto = false;

    public function mount($id)
    {
        abort_if(!feature('inscricao.visualizar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('inscricao.visualizar'), 403);

        $this->inscricao = Inscricao::with(['unidade', 'curso', 'turno', 'ciclo'])->findOrFail($id);
        $this->status_selecionado = $this->inscricao->status_inscricao_id;
    }

    public function getDataInscricao()
    {
        $dinamicos = is_string($this->inscricao->dados_dinamicos) ? json_decode($this->inscricao->dados_dinamicos, true) : ($this->inscricao->dados_dinamicos ?? []);
        $dataRaw = $dinamicos['Submission started'] ?? $dinamicos['submission started'] ?? $dinamicos['Submission Started'] ?? $this->inscricao->created_at;
        
        try {
            return \Carbon\Carbon::parse($dataRaw);
        } catch (\Exception $e) {
            return clone $this->inscricao->created_at;
        }
    }

    public function getDataAtualizacao()
    {
        $dinamicos = is_string($this->inscricao->dados_dinamicos) ? json_decode($this->inscricao->dados_dinamicos, true) : ($this->inscricao->dados_dinamicos ?? []);
        $dataRaw = $dinamicos['Last updated'] ?? $dinamicos['last updated'] ?? $dinamicos['Last Updated'] ?? $this->inscricao->updated_at;
        
        try {
            return \Carbon\Carbon::parse($dataRaw);
        } catch (\Exception $e) {
            return clone $this->inscricao->updated_at;
        }
    }

    public function atualizarStatus()
    {
        abort_if(!feature('inscricao.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('inscricao.editar'), 403);

        if ($this->inscricao->status_inscricao_id == $this->status_selecionado) {
            $this->dispatch('erro', msg: 'O candidato já se encontra neste status!');
            return;
        }

        $statusNovo = StatusInscricao::find($this->status_selecionado);
        if (!$statusNovo) return;

        $eventoGatilho = 'inscricao.status.' . \Illuminate\Support\Str::slug($statusNovo->nome, '_');
        $automacao = \App\Modules\Comunicacao\Domain\Models\Automacao::where('evento_gatilho', $eventoGatilho)->where('status', true)->first();

        if ($automacao) {
            $jaRecebeu = \App\Modules\Comunicacao\Domain\Models\Comunicado::where('template_id', $automacao->template_id)
                ->where('inscricao_id', $this->inscricao->id)
                ->exists();

            if ($jaRecebeu) {
                $this->modalAntiSpamAberto = true;
                return; 
            }
        }

        $this->executarMudancaStatusFinal();
    }

    public function cancelarAntiSpam() 
    {
        $this->modalAntiSpamAberto = false;
        $this->status_selecionado = $this->inscricao->status_inscricao_id; 
    }

    public function executarMudancaStatusFinal()
    {
        $statusNovo = StatusInscricao::find($this->status_selecionado);
        
        $this->inscricao->status_inscricao_id = $statusNovo->id;
        $this->inscricao->save();

        $eventoGatilho = 'inscricao.status.' . \Illuminate\Support\Str::slug($statusNovo->nome, '_');
        \App\Modules\Comunicacao\Services\AutomacaoService::disparar($eventoGatilho, $this->inscricao);

        $this->inscricao->refresh(); 
        $this->modalAntiSpamAberto = false;
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