<?php

namespace App\Modules\Teste\RDCrm\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Modules\Teste\RDCrm\Domain\Models\RdCrmPipeline;
use App\Modules\Teste\RDCrm\Domain\Models\RdCrmDeal;
use App\Modules\Teste\RDCrm\Domain\Models\RdCrmContact;
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

    /**
     * NOVO: Gera um registro fictício no banco para testarmos o envio ao RD
     */
    public function criarCadastroTeste()
    {
        // 1. Cria o Contato Base (Lead) com dados aleatórios (para não dar duplicidade no RD)
        $timestamp = time();
        
        $contato = RdCrmContact::create([
            'nome' => 'Candidato Teste ' . rand(1000, 9999),
            'email' => "teste.rd.{$timestamp}@percorre.com.br",
            'telefone' => '119' . rand(1111111, 9999999),
            'data_nascimento' => '2005-08-15'
        ]);

        // 2. Cria a Negociação vinculada e a deixa na fila (sincronizado = false)
        RdCrmDeal::create([
            'rd_crm_contact_id' => $contato->id,
            'nome' => 'Negociação Gerada via Botão Teste - ' . date('d/m/Y H:i'),
            'rating' => 1,
            'sincronizado' => false,
            // Podemos mandar um campo customizado aleatório só para testar a flexibilidade
            'campos_customizados' => [
                [
                    "custom_field_id" => "6a43d5b0c1f909001d1deecf", // ID de teste do seu JSON
                    "value" => "Sim (Gerado por Teste Automático)"
                ]
            ]
        ]);

        // Muda a aba para focar nos Deals e ver o registro criado
        $this->abaAtiva = 'deals';
        $this->resetPage();

        $this->dispatch('sucesso', msg: 'Contato e Negociação criados! O registro está "Pendente" aguardando o envio.');
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