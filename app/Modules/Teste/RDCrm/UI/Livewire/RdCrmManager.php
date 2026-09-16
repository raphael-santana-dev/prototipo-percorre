<?php

namespace App\Modules\Teste\RDCrm\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Modules\Teste\RDCrm\Domain\Models\RdCrmPipeline;
use App\Modules\Teste\RDCrm\Domain\Models\RdCrmDeal;
use App\Modules\Teste\RDCrm\Services\RdCrmService;

#[Layout('components.layouts.app')]
#[Title('RD Station CRM - Integração')]
class RdCrmManager extends Component
{
    use WithPagination;

    public $abaAtiva = 'funis';
    public array $breadcrumbs = [];

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('rdcrm.acessar'), 403, 'Acesso restrito.');

        $this->breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Integrações', 'url' => '#'],
            ['label' => 'RD Station CRM', 'url' => route('teste.rdcrm')],
        ];
    }

    /**
     * Ação manual para baixar os funis e etapas do RD
     */
    public function atualizarFunis()
    {
        $resultado = RdCrmService::sincronizarFunis();

        if ($resultado['sucesso']) {
            $this->dispatch('sucesso', msg: $resultado['mensagem']);
        } else {
            $this->dispatch('erro', msg: $resultado['mensagem']);
        }
    }

    /**
     * Ação manual para forçar o envio de deals presos na fila
     */
    public function forcarEnvioPendentes()
    {
        $resultado = RdCrmService::enviarNegociacoesPendentes();

        if ($resultado['sucesso']) {
            $this->dispatch('sucesso', msg: "Processamento concluído. {$resultado['enviados']} negociações enviadas.");
        } else {
            $this->dispatch('erro', msg: 'Ocorreu um erro ao tentar processar o envio.');
        }
    }

    public function render()
    {
        // Puxa a estrutura de funis do banco
        $pipelines = RdCrmPipeline::with('stages')->orderBy('ordem', 'asc')->get();
        
        // Puxa as negociações locais (Deals)
        $deals = RdCrmDeal::with('contact')->orderBy('id', 'desc')->paginate(15);

        // Métricas de topo de tela
        $metricas = [
            ['label' => 'Total de Deals', 'value' => RdCrmDeal::count(), 'color_text' => 'text-blue-600 dark:text-blue-400', 'color_bg' => 'bg-blue-100 dark:bg-blue-900/30'],
            ['label' => 'Sincronizados (Nuvem)', 'value' => RdCrmDeal::where('sincronizado', true)->count(), 'color_text' => 'text-emerald-600 dark:text-emerald-400', 'color_bg' => 'bg-emerald-100 dark:bg-emerald-900/30'],
            ['label' => 'Pendentes na Fila', 'value' => RdCrmDeal::where('sincronizado', false)->count(), 'color_text' => 'text-orange-600 dark:text-orange-400', 'color_bg' => 'bg-orange-100 dark:bg-orange-900/30'],
        ];

        return view('livewire.teste.rd-crm.rd-crm-manager', [
            'pipelines' => $pipelines,
            'deals' => $deals,
            'metricas' => $metricas
        ]);
    }
}