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
        Inscricao::where('ciclo_id', $this->ciclo->id)
            ->where('id', $inscricaoId)
            ->update(['status_inscricao_id' => $novoStatusId]);
    }

    public function moverLote()
    {
        abort_if(!feature('inscricao.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('inscricao.editar'), 403);
        
        $this->validate([
            'selecionados' => 'required|array|min:1',
            'statusDestinoLote' => 'required|exists:status_inscricoes,id'
        ]);

        Inscricao::where('ciclo_id', $this->ciclo->id)
            ->whereIn('id', $this->selecionados)
            ->update(['status_inscricao_id' => $this->statusDestinoLote]);

        $this->reset(['selecionados', 'statusDestinoLote']);
        $this->dispatch('sucesso', msg: 'Candidatos movidos em lote com sucesso!');
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