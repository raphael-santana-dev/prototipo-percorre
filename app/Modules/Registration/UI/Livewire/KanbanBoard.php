<?php

namespace App\Modules\Registration\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Ciclo;
use App\Models\Inscricao;

#[Layout('components.layouts.app')]
#[Title('CRM - Funil de Inscrições')]
class KanbanBoard extends Component
{
    public Ciclo $ciclo;
    public array $selecionados = []; 
    public $statusDestinoLote = '';

    // CORREÇÃO: O parâmetro deve se chamar $id para dar "match" com a Route::get('/ciclos/{id}/crm')
    public function mount(int $id)
    {
        abort_if(!feature('crm.acessar'), 403, 'CRM Desativado.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('crm.acessar'), 403, 'Acesso restrito.');
        $this->ciclo = Ciclo::with('statusPipeline')->findOrFail($id);
    }

    public function atualizarStatus($inscricaoId, $novoStatusId)
    {
        abort_if(!feature('inscricao.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('inscricao.editar'), 403);
        
        // Em vez de alterar no banco direto, passamos para o Job que cuida da automação de e-mails!
        $tracking = \App\Models\Importacao::create([
            'user_id' => auth()->id(), 'tipo' => 'inscricoes', 'operacao' => 'atualizacao_lote', 'formato' => 'system',
            'arquivo_nome' => "Alteração via CRM Kanban", 'status' => 'na_fila', 'total_linhas' => 1, 'linhas_processadas' => 0,
        ]);
        dispatch(new \App\Jobs\ProcessarStatusEmLoteJob($tracking->id, [$inscricaoId], $novoStatusId))->afterResponse();
    }

    public function moverLote()
    {
        abort_if(!feature('inscricao.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('inscricao.editar'), 403);
        
        $this->validate([
            'selecionados' => 'required|array|min:1',
            'statusDestinoLote' => 'required|exists:status_inscricoes,id'
        ]);

        $tracking = \App\Models\Importacao::create([
            'user_id' => auth()->id(), 'tipo' => 'inscricoes', 'operacao' => 'atualizacao_lote', 'formato' => 'system',
            'arquivo_nome' => "Alteração em Lote via CRM Kanban", 'status' => 'na_fila', 'total_linhas' => count($this->selecionados), 'linhas_processadas' => 0,
        ]);
        dispatch(new \App\Jobs\ProcessarStatusEmLoteJob($tracking->id, $this->selecionados, $this->statusDestinoLote))->afterResponse();

        $this->reset(['selecionados', 'statusDestinoLote']);
        $this->dispatch('sucesso', msg: 'Ação enviada para processamento em background!');
    }

    public function render()
    {
        $inscricoesGrupadas = Inscricao::where('ciclo_id', $this->ciclo->id)
            ->orderBy('updated_at', 'desc')
            ->get()
            ->groupBy('status_inscricao_id');

        return view('livewire.registration.kanban-board', [
            'colunas' => $this->ciclo->statusPipeline,
            'inscricoesGrupadas' => $inscricoesGrupadas
        ]);
    }
}