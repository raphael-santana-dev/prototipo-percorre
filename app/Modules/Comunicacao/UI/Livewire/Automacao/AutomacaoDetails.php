<?php

namespace App\Modules\Comunicacao\UI\Livewire\Automacao;

use Livewire\Component;
use Livewire\WithPagination;
use App\Modules\Comunicacao\Domain\Models\Automacao;
use App\Modules\Comunicacao\Domain\Models\Comunicado;
use App\Helpers\BreadcrumbHelper;

class AutomacaoDetails extends Component
{
    use WithPagination;

    public Automacao $automacao;
    public array $breadcrumbs = [];

    // Variáveis de Filtro
    public $filtro_busca = '';
    public $filtro_data_inicio = '';
    public $filtro_data_fim = '';

    public function mount($id)
    {
        $this->automacao = Automacao::with('template')->findOrFail($id);
        $this->breadcrumbs = BreadcrumbHelper::generate('Histórico: ' . $this->automacao->nome);
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtro_busca', 'filtro_data_inicio', 'filtro_data_fim'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtro_busca', 'filtro_data_inicio', 'filtro_data_fim']);
        $this->resetPage();
    }

    public function showQuickView($id)
    {
        $log = Comunicado::with('template')->findOrFail($id);

        // Formata a lista de destinatários para exibição no drawer
        $destinatariosHtml = '';
        if (is_array($log->destinatarios)) {
            $destinatariosHtml = '<ul class="list-disc pl-4 text-sm text-gray-700 dark:text-gray-300 space-y-1">';
            foreach($log->destinatarios as $dest) {
                $destinatariosHtml .= "<li>{$dest}</li>";
            }
            $destinatariosHtml .= '</ul>';
        } else {
            $destinatariosHtml = '<span class="text-sm text-gray-700 dark:text-gray-300">'.($log->destinatarios ?? 'Nenhum').'</span>';
        }

        $this->dispatch('load-quick-view', [
            'title' => 'Detalhes do Disparo',
            'subtitle' => 'Enviado em ' . $log->created_at->format('d/m/Y \à\s H:i:s'),
            'icon' => 'ph-paper-plane-tilt',
            'data' => [
                'Status do Envio' => '<span class="px-2.5 py-1 text-xs font-bold rounded-full border border-green-200 bg-green-50 text-green-700 dark:bg-green-900/30 dark:border-green-800 dark:text-green-400"><i class="ph-bold ph-check mr-1"></i> Entregue</span>',
                'Template Utilizado' => '<div class="text-sm font-medium text-gray-900 dark:text-gray-100"><i class="ph ph-layout text-purpura-500 mr-1"></i> '.($log->template->nome ?? 'Excluído').'</div>',
                'Destinatários' => '<div class="max-h-48 overflow-y-auto custom-scrollbar bg-gray-50 dark:bg-gray-800 p-3 rounded border border-gray-100 dark:border-gray-700 mt-1">'.$destinatariosHtml.'</div>',
            ]
        ]);
    }

    public function render()
    {
        $query = Comunicado::where('template_id', $this->automacao->template_id)
            ->where('status', 'concluido');

        // Aplicação dos Filtros
        $query->when($this->filtro_busca, function($q) {
            $q->where('destinatarios', 'like', '%' . $this->filtro_busca . '%');
        })
        ->when($this->filtro_data_inicio, fn($q) => $q->whereDate('created_at', '>=', $this->filtro_data_inicio))
        ->when($this->filtro_data_fim, fn($q) => $q->whereDate('created_at', '<=', $this->filtro_data_fim));

        $historico = $query->orderBy('created_at', 'desc')->paginate(15);

        $metricas = [
            [
                'label' => 'Apelido da Regra',
                'value' => $this->automacao->nome,
                'value_size' => 'text-base md:text-lg',
                'color_text' => 'text-gray-500 dark:text-gray-400',
                'color_bg' => 'bg-gray-100 dark:bg-gray-800',
                'icon' => '<i class="ph-fill ph-tag text-2xl text-gray-500 dark:text-gray-400"></i>'
            ],
            [
                'label' => 'Evento Gatilho',
                'value' => $this->automacao->evento_gatilho,
                'value_size' => 'text-base md:text-lg',
                'color_text' => 'text-blue-500 dark:text-blue-400',
                'color_bg' => 'bg-blue-100 dark:bg-blue-900/30',
                'icon' => '<i class="ph-fill ph-lightning text-2xl text-blue-500 dark:text-blue-400"></i>'
            ],
            [
                'label' => 'Template Disparado',
                'value' => $this->automacao->template->nome ?? 'Excluído',
                'value_size' => 'text-base md:text-lg',
                'color_text' => 'text-purpura-500 dark:text-purpura-400',
                'color_bg' => 'bg-purpura-100 dark:bg-purpura-900/30',
                'icon' => '<i class="ph-fill ph-layout text-2xl text-purpura-500 dark:text-purpura-400"></i>'
            ]
        ];

        return view('livewire.comunicacao.automacao.automacao-details', [
            'historico' => $historico,
            'metricas' => $metricas
        ])->layout('components.layouts.app', ['title' => 'Histórico de Automação']);
    }
}