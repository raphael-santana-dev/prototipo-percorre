<?php

namespace App\Modules\Admin\UI\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Solicitacao;
use App\Modules\GestaoEducacional\Domain\Models\AlunoAvaliacao;

#[Layout('components.layouts.app')]
#[Title('Central de Solicitações')]
class SolicitacoesManager extends Component
{
    use WithPagination;

    public $filtroStatus = 'pendente';
    public $modalResposta = false;
    public $solicitacaoAtiva = null;
    public $textoResposta = '';
    public $acaoResposta = '';

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev|admin|professor'), 403, 'Acesso restrito.');
    }

    public function abrirResposta($id, $acao)
    {
        $this->reset(['textoResposta']);
        $this->solicitacaoAtiva = Solicitacao::findOrFail($id);
        $this->acaoResposta = $acao; 
        $this->modalResposta = true;
    }

    public function confirmarResposta()
    {
        $this->validate(['textoResposta' => 'required|string|min:5'], ['textoResposta.required' => 'Insira um feedback ou justificativa para esta decisão.']);

        $statusFinal = $this->acaoResposta === 'aprovar' ? 'aprovada' : 'rejeitada';
        
        $this->solicitacaoAtiva->update([
            'status' => $statusFinal,
            'resposta_admin' => $this->textoResposta,
            'responsavel_id' => auth()->id()
        ]);

        if ($statusFinal === 'aprovada') {
            $payload = $this->solicitacaoAtiva->payload;

            if ($this->solicitacaoAtiva->tema === 'cadastro_nova_inscricao') {
                $inscricao = \App\Models\Inscricao::create($payload);
                
                \App\Modules\Comunicacao\Services\AutomacaoService::disparar('inscricao.criada', $inscricao);
            }

            if ($this->solicitacaoAtiva->tema === 'avaliacao_aluno_fase') {
                AlunoAvaliacao::where('id', $payload['aluno_avaliacao_id'])->update(['status' => '1', 'data_resposta' => null]);
            }
            
            if ($this->solicitacaoAtiva->tema === 'avaliacao_prof_total') {
                AlunoAvaliacao::whereIn('id', $payload['fases_para_desbloquear'])->update(['status' => '1', 'data_resposta' => null]);
            }
        }

        $this->modalResposta = false;
        $this->dispatch('sucesso', msg: 'Solicitação processada com sucesso!');
    }

    public function render()
    {
        $query = Solicitacao::with('solicitante')->orderBy('created_at', 'desc');

        if ($this->filtroStatus) {
            $query->where('status', $this->filtroStatus);
        }

        if (auth()->user()->hasRole('professor') && !auth()->user()->hasRole('dev|admin')) {
            $query->where('responsavel_id', auth()->id())
                  ->orWhereNull('responsavel_id'); 
        }

        return view('livewire.admin.solicitacoes-manager', [
            'solicitacoes' => $query->paginate(15)
        ]);
    }
}