<?php

namespace App\Modules\Comunicacao\UI\Livewire\Comunicado;

use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use App\Helpers\BreadcrumbHelper;
use App\Modules\Comunicacao\Domain\Models\Comunicado;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;

class ComunicadoManager extends Component
{
    use WithPagination, ComPadraoListagem;

    public array $breadcrumbs = [];
    public string $filtro_status = '';
    public string $filtro_template = '';
    public string $filtro_data_inicio = '';
    public string $filtro_data_fim = '';

    public function updating($nomePropriedade) 
    { 
        if (in_array($nomePropriedade, ['filtro_status', 'filtro_template', 'filtro_data_inicio', 'filtro_data_fim'])) {
            $this->resetPage(); 
        }
    }
    
    public function limparFiltros() 
    {
        $this->reset(['filtro_status', 'filtro_template', 'filtro_data_inicio', 'filtro_data_fim']);
        $this->resetPage();
    }

    public function mount()
    {
        abort_if(!feature('comunicado.listar'), 403, 'Módulo desativado.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('comunicado.listar'), 403);

        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->permiteGrid = false;
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => '#ID', 'sortable' => true, 'class' => 'w-16'],
            ['key' => 'template_id', 'label' => 'Template Utilizado', 'sortable' => true],
            ['key' => 'destinatarios', 'label' => 'Audiência / Anexos', 'sortable' => false],
            ['key' => 'data_agendamento', 'label' => 'Programação / Envio', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right w-28'],
        ];
    }

    public function showQuickView(int $id)
    {
        $comunicado = Comunicado::with('template')->findOrFail($id);
        $destinatarios = is_array($comunicado->destinatarios) ? $comunicado->destinatarios : [];
        $anexos = is_array($comunicado->anexos) ? $comunicado->anexos : [];

        $listaDestHtml = '<div class="max-h-40 overflow-y-auto custom-scrollbar bg-gray-50 dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700 text-xs space-y-1">';
        foreach ($destinatarios as $d) {
            $listaDestHtml .= '<div class="font-mono text-gray-700 dark:text-gray-300">' . e($d) . '</div>';
        }
        $listaDestHtml .= '</div>';

        $anexosHtml = '';
        if (!empty($anexos)) {
            $anexosHtml = '<div class="flex flex-wrap gap-2 mt-1">';
            foreach ($anexos as $path) {
                $url = asset('storage/' . $path);
                $anexosHtml .= '<a href="' . $url . '" target="_blank" class="px-2.5 py-1 bg-purpura-50 text-purpura-700 border border-purpura-200 rounded text-xs font-bold hover:underline">' . e(basename($path)) . ' ↗</a>';
            }
            $anexosHtml .= '</div>';
        } else {
            $anexosHtml = '<span class="text-xs text-gray-400 italic">Sem anexos adicionais.</span>';
        }

        $this->dispatch('load-quick-view', [
            'title' => 'Detalhes do Comunicado #' . $comunicado->id,
            'subtitle' => $comunicado->template->nome ?? 'Template Removido',
            'icon' => 'ph-paper-plane-tilt',
            'maxWidth' => '2xl',
            'data' => [
                'Assunto Disparado' => '<span class="font-bold text-sm text-gray-900 dark:text-white">' . e($comunicado->template->assunto ?? '-') . '</span>',
                'Destinatários (' . count($destinatarios) . ')' => $listaDestHtml,
                'Anexos Enviados' => $anexosHtml,
                'Prévia da Mensagem' => '<div class="p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-xs prose prose-sm max-w-none max-h-72 overflow-y-auto">' . ($comunicado->template->corpo ?? '-') . '</div>',
            ]
        ]);
    }

    public function excluir($id)
    {
        abort_if(!feature('comunicado.excluir'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('comunicado.excluir'), 403);
        
        $comunicado = Comunicado::findOrFail($id);
        
        if ($comunicado->status === 'enviando') {
            $this->dispatch('erro', msg: 'Não é possível excluir um comunicado que está sendo enviado neste momento.');
            return;
        }

        if (is_array($comunicado->anexos)) {
            foreach ($comunicado->anexos as $anexo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($anexo);
            }
        }

        $comunicado->delete();
        $this->dispatch('sucesso', msg: 'Registro de comunicado removido com sucesso!');
    }

    public function render()
    {
        $query = Comunicado::with('template');
        
        $query->when($this->filtro_status, fn($q) => $q->where('status', $this->filtro_status))
              ->when($this->filtro_template, fn($q) => $q->where('template_id', $this->filtro_template))
              ->when($this->filtro_data_inicio, fn($q) => $q->whereDate('data_agendamento', '>=', $this->filtro_data_inicio))
              ->when($this->filtro_data_fim, fn($q) => $q->whereDate('data_agendamento', '<=', $this->filtro_data_fim));

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('id', 'desc');
        }

        $templatesDisponiveis = EmailTemplate::orderBy('nome')->get();

        $metricas = [
            [
                'label' => 'Total de Disparos',
                'value' => Comunicado::count(),
                'color_text' => 'text-blue-600 dark:text-blue-400',
                'color_bg' => 'bg-blue-100 dark:bg-blue-900/30',
                'icon' => '<i class="ph-fill ph-paper-plane-tilt text-xl text-blue-600"></i>'
            ],
            [
                'label' => 'Concluídos',
                'value' => Comunicado::where('status', 'concluido')->count(),
                'color_text' => 'text-emerald-600 dark:text-emerald-400',
                'color_bg' => 'bg-emerald-100 dark:bg-emerald-900/30',
                'icon' => '<i class="ph-fill ph-check-circle text-xl text-emerald-600"></i>'
            ],
            [
                'label' => 'Na Fila / Agendados',
                'value' => Comunicado::whereIn('status', ['pendente', 'enviando'])->count(),
                'color_text' => 'text-amber-600 dark:text-amber-400',
                'color_bg' => 'bg-amber-100 dark:bg-amber-900/30',
                'icon' => '<i class="ph-fill ph-clock text-xl text-amber-600"></i>'
            ],
            [
                'label' => 'Com Falha',
                'value' => Comunicado::where('status', 'erro')->count(),
                'color_text' => 'text-red-600 dark:text-red-400',
                'color_bg' => 'bg-red-100 dark:bg-red-900/30',
                'icon' => '<i class="ph-fill ph-warning-circle text-xl text-red-600"></i>'
            ],
        ];

        return view('livewire.comunicacao.comunicado.comunicado-manager', [
            'registros'            => $query->paginate($this->porPagina),
            'templatesDisponiveis' => $templatesDisponiveis,
            'metricas'             => $metricas,
        ])->layout('components.layouts.app', ['title' => 'Gestão de Comunicados']);
    }
}