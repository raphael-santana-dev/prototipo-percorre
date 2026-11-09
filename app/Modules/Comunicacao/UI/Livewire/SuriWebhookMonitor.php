<?php

namespace App\Modules\Comunicacao\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use App\Traits\ValidaAcesso;
use App\Helpers\BreadcrumbHelper;
use App\Models\SuriWebhookEvent;
use App\Jobs\ProcessarSuriWebhookJob;

#[Layout('components.layouts.app')]
#[Title('Monitor do Webhook Suri')]
class SuriWebhookMonitor extends Component
{
    use WithPagination;
    use ComPadraoListagem;
    use ValidaAcesso;

    public $filtroStatus = '';
    public $filtroTipo = '';
    public $filtroBusca = '';
    
    public array $breadcrumbs = [];

    public function mount()
    {
        // Trava de segurança utilizando a nova Trait padronizada
        // $this->autorizarTela('suri.monitor.listar', 'Monitor de Webhooks Suri');
        
        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->permiteGrid = false; // Como é um log técnico, forçamos a visualização em tabela
        $this->ordenacaoDirecao = 'desc';
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtroStatus', 'filtroTipo', 'filtroBusca'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtroStatus', 'filtroTipo', 'filtroBusca']);
        $this->resetPage();
    }

    /**
     * Reprocessa um evento que falhou ou que você deseja rodar novamente.
     */
    public function reprocessar(int $id)
    {
        // if (!$this->autorizarAcao('suri.monitor.reprocessar', 'reprocessar eventos da Suri')) {
        //     return;
        // }

        $evento = SuriWebhookEvent::findOrFail($id);
        
        // Reseta o status para pending e limpa o erro anterior
        $evento->update([
            'status' => 'pending',
            'error_message' => null,
            'processed_at' => null,
        ]);

        // Reenvia para a fila de processamento assíncrono
        ProcessarSuriWebhookJob::dispatch($evento->id);

        $this->dispatch('sucesso', msg: 'Evento reenviado para a fila de processamento com sucesso!');
    }

    public function showQuickView(int $id)
    {
        // if (!$this->autorizarAcao('suri.monitor.visualizar', 'visualizar payload da Suri')) {
        //     return;
        // }

        $evento = SuriWebhookEvent::findOrFail($id);

        $statusBadge = match($evento->status) {
            'pending'   => '<span class="bg-amber-100 text-amber-800 px-3 py-1 rounded text-xs font-bold uppercase tracking-wider border border-amber-200"><i class="ph-bold ph-spinner animate-spin"></i> Pendente</span>',
            'processed' => '<span class="bg-emerald-100 text-emerald-800 px-3 py-1 rounded text-xs font-bold uppercase tracking-wider border border-emerald-200"><i class="ph-bold ph-check"></i> Processado</span>',
            'failed'    => '<span class="bg-red-100 text-red-800 px-3 py-1 rounded text-xs font-bold uppercase tracking-wider border border-red-200"><i class="ph-bold ph-warning"></i> Falha</span>',
            default     => '<span class="bg-gray-100 text-gray-800 px-3 py-1 rounded text-xs font-bold uppercase tracking-wider border border-gray-200">' . $evento->status . '</span>',
        };

        $payloadFormatado = json_encode($evento->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $erroFormatado = $evento->error_message ? "<div class='mt-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-800 rounded-r-md text-xs font-mono whitespace-pre-wrap overflow-x-auto'><strong>Erro de Processamento:</strong>\n{$evento->error_message}</div>" : '';

        $this->dispatch('load-quick-view', [
            'title' => 'Evento: ' . $evento->event_type,
            'subtitle' => 'ID: ' . $evento->event_id,
            'icon' => 'ph-webhook',
            'maxWidth' => '3xl',
            'data' => [
                'Status do Processamento' => $statusBadge,
                'Data de Recebimento' => '<span class="text-sm font-bold text-gray-800 dark:text-gray-200">' . $evento->event_timestamp->format('d/m/Y \à\s H:i:s.u') . '</span>',
                'Data de Processamento' => '<span class="text-sm font-bold text-gray-800 dark:text-gray-200">' . ($evento->processed_at ? $evento->processed_at->format('d/m/Y \à\s H:i:s.u') : 'Aguardando na fila...') . '</span>',
                'Payload Original (JSON)' => $erroFormatado . '<div class="mt-2 p-4 bg-gray-900 text-green-400 rounded-xl overflow-x-auto max-h-[50vh] custom-scrollbar border border-gray-800 text-xs font-mono whitespace-pre-wrap">' . htmlspecialchars($payloadFormatado) . '</div>',
            ]
        ]);
    }

    public function getHeadersProperty(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'sortable' => true, 'class' => 'w-16'],
            ['key' => 'event_type', 'label' => 'Tipo de Evento', 'sortable' => true],
            ['key' => 'event_id', 'label' => 'ID da Suri (Rastreio)', 'sortable' => false],
            ['key' => 'event_timestamp', 'label' => 'Recebido Em', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right w-24'],
        ];
    }

    public function render()
    {
        $query = SuriWebhookEvent::query();

        if (!empty($this->filtroBusca)) {
            $query->where(function($q) {
                $q->where('event_id', 'like', '%' . $this->filtroBusca . '%')
                  ->orWhere('chatbot_id', 'like', '%' . $this->filtroBusca . '%');
            });
        }

        if (!empty($this->filtroStatus)) {
            $query->where('status', $this->filtroStatus);
        }

        if (!empty($this->filtroTipo)) {
            $query->where('event_type', $this->filtroTipo);
        }

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('id', 'desc');
        }

        // Obtém a lista de tipos distintos já gravados no banco para alimentar o select do filtro
        $tiposDisponiveis = SuriWebhookEvent::select('event_type')->distinct()->orderBy('event_type')->pluck('event_type');

        return view('livewire.comunicacao.suri-webhook-monitor', [
            'registros' => $query->paginate($this->porPagina),
            'tiposDisponiveis' => $tiposDisponiveis,
        ]);
    }
}