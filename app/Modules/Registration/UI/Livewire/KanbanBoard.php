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

    public function mount(int $cicloId)
    {
        // Carrega o ciclo com as colunas na ordem correta
        $this->ciclo = Ciclo::with('statusPipeline')->findOrFail($cicloId);
    }

    // Gatilho silencioso acionado pelo AlpineJS (Arrastar e Soltar)
    public function atualizarStatus($inscricaoId, $novoStatusId)
    {
        Inscricao::where('ciclo_id', $this->ciclo->id)
            ->where('id', $inscricaoId)
            ->update(['status_inscricao_id' => $novoStatusId]);
    }

    // Ação em Lote (Checkboxes)
    public function moverLote()
    {
        $this->validate([
            'selecionados' => 'required|array|min:1',
            'statusDestinoLote' => 'required|exists:status_inscricoes,id'
        ]);

        Inscricao::where('ciclo_id', $this->ciclo->id)
            ->whereIn('id', $this->selecionados)
            ->update(['status_inscricao_id' => $this->statusDestinoLote]);

        $this->reset(['selecionados', 'statusDestinoLote']);
        session()->flash('sucesso', 'Candidatos movidos em lote com sucesso!');
    }

    public function render()
    {
        // Pega as inscrições e agrupa pelo ID do status para popularmos as colunas
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