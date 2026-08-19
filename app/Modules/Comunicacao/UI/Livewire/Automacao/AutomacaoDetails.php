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

    public function mount($id)
    {
        $this->automacao = Automacao::with('template')->findOrFail($id);
        
        $this->breadcrumbs = BreadcrumbHelper::generate('Histórico: ' . $this->automacao->nome);
    }

    public function render()
    {
        $historico = \App\Modules\Comunicacao\Domain\Models\Comunicado::where('template_id', $this->automacao->template_id)
            ->where('status', 'concluido')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Montando as métricas no padrão do sistema
        // Montando as métricas no padrão do sistema
        $metricas = [
            [
                'label' => 'Apelido da Regra',
                'value' => $this->automacao->nome,
                'value_size' => 'text-base md:text-lg', // <-- Reduz a fonte do texto
                'color_text' => 'text-gray-500 dark:text-gray-400',
                'color_bg' => 'bg-gray-100 dark:bg-gray-800',
                'icon' => '<i class="ph-fill ph-tag text-2xl text-gray-500 dark:text-gray-400"></i>'
            ],
            [
                'label' => 'Evento Gatilho',
                'value' => $this->automacao->evento_gatilho,
                'value_size' => 'text-base md:text-lg', // <-- Reduz a fonte do texto
                'color_text' => 'text-blue-500 dark:text-blue-400',
                'color_bg' => 'bg-blue-100 dark:bg-blue-900/30',
                'icon' => '<i class="ph-fill ph-lightning text-2xl text-blue-500 dark:text-blue-400"></i>'
            ],
            [
                'label' => 'Template Disparado',
                'value' => $this->automacao->template->nome ?? 'Excluído',
                'value_size' => 'text-base md:text-lg', // <-- Reduz a fonte do texto
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