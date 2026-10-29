<?php

namespace App\Modules\Comunicacao\UI\Livewire\Automacao;

use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use App\Helpers\BreadcrumbHelper;
use App\Modules\Comunicacao\Domain\Models\Automacao;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;

class AutomacaoManager extends Component
{
    use WithPagination, ComPadraoListagem;

    public array $breadcrumbs = [];
    public string $filtro_busca = '';
    public string $filtro_status = '';
    public string $filtro_acao = '';

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtro_busca', 'filtro_status', 'filtro_acao'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtro_busca', 'filtro_status', 'filtro_acao']);
        $this->resetPage();
    }

    public function mount()
    {
        //abort_if(!feature('automacao.listar'), 403, 'Módulo desativado.');
        //abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('automacao.listar'), 403);

        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->permiteGrid = false;
    }

    public function previewTemplate($templateId)
    {
        $template = EmailTemplate::find($templateId);
        
        if ($template) {
            $corpoEmail = $template->corpo ?? $template->conteudo ?? $template->mensagem ?? $template->html ?? null;
            $canal = strtoupper($template->canal ?? 'email');
            $botoes = is_array($template->botoes) ? $template->botoes : [];
            
            if (empty($corpoEmail)) {
                $corpoEmail = '<i class="text-gray-400">Sem conteúdo configurado.</i>';
            }

            $htmlBotoes = '';
            if (!empty($botoes)) {
                $htmlBotoes .= '<div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700 space-y-1.5">';
                $htmlBotoes .= '<span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Botões / Opções Configuradas:</span>';
                foreach ($botoes as $b) {
                    if (empty($b['texto'])) continue;
                    $urlInfo = !empty($b['url']) ? ' <span class="text-gray-400 font-mono">(' . e($b['url']) . ')</span>' : '';
                    $htmlBotoes .= '<div class="px-3 py-1.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded text-xs font-bold text-purpura-600 dark:text-purpura-400">' . e($b['texto']) . $urlInfo . '</div>';
                }
                $htmlBotoes .= '</div>';
            }

            $this->dispatch('load-quick-view', [
                'title' => 'Pré-visualização do Template',
                'subtitle' => 'Layout vinculado a esta regra de automação',
                'icon' => 'ph-layout',
                'maxWidth' => '2xl',
                'data' => [
                    'Identificação' => '<div class="flex items-center justify-between"><span class="font-bold text-sm text-gray-900 dark:text-white">' . e($template->nome) . '</span><span class="px-2 py-0.5 text-[10px] font-bold rounded bg-purpura-100 text-purpura-700">' . $canal . '</span></div>',
                    'Assunto do E-mail' => '<div class="font-bold text-gray-900 dark:text-white text-sm">' . e($template->assunto) . '</div>',
                    'Corpo da Mensagem' => '<div class="p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-800 dark:text-gray-200 mt-1 max-h-96 overflow-y-auto prose prose-sm max-w-none">' . $corpoEmail . $htmlBotoes . '</div>'
                ]
            ]);
        } else {
            $this->dispatch('erro', msg: 'Template não encontrado ou excluído.');
        }
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => '#ID', 'sortable' => true, 'class' => 'w-16'],
            ['key' => 'nome', 'label' => 'Regra & Ação', 'sortable' => true],
            ['key' => 'evento_gatilho', 'label' => 'Evento Gatilho (Quando...)', 'sortable' => true],
            ['key' => 'template_id', 'label' => 'Template Vinculado', 'sortable' => false],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true, 'class' => 'text-center w-28'],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right w-28'],
        ];
    }

    public function toggleStatus($id)
    {
        //abort_if(!feature('automacao.editar'), 403);
        //abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('automacao.editar'), 403);

        $automacao = Automacao::findOrFail($id);
        $automacao->status = !$automacao->status;
        $automacao->save();
        
        $this->dispatch('sucesso', msg: $automacao->status ? 'Automação ativada com sucesso!' : 'Automação pausada com sucesso!');
    }

    public function excluir($id)
    {
        //abort_if(!feature('automacao.excluir'), 403);
        //abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('automacao.excluir'), 403);
        
        Automacao::findOrFail($id)->delete();
        $this->dispatch('sucesso', msg: 'Regra de automação excluída.');
    }

    public function render()
    {
        $query = Automacao::with('template');

        $query->when($this->filtro_busca, function ($q) {
            $q->where(function ($sub) {
                $sub->where('nome', 'ilike', '%' . $this->filtro_busca . '%')
                    ->orWhere('evento_gatilho', 'ilike', '%' . $this->filtro_busca . '%');
            });
        })
        ->when($this->filtro_status !== '', fn($q) => $q->where('status', (bool) $this->filtro_status))
        ->when($this->filtro_acao !== '', fn($q) => $q->where('tipo_acao', $this->filtro_acao));

        $query->orderBy($this->ordenacaoCampo ?: 'id', $this->ordenacaoDirecao ?: 'desc');

        $totalRegras  = Automacao::count();
        $totalAtivas  = Automacao::where('status', true)->count();
        $totalPausadas = $totalRegras - $totalAtivas;

        $metricas = [
            [
                'label' => 'Total de Regras',
                'value' => $totalRegras,
                'color_text' => 'text-indigo-600 dark:text-indigo-400',
                'color_bg' => 'bg-indigo-100 dark:bg-indigo-900/30',
                'icon' => '<i class="ph-fill ph-lightning text-xl text-indigo-600"></i>'
            ],
            [
                'label' => 'Regras Ativas',
                'value' => $totalAtivas,
                'color_text' => 'text-emerald-600 dark:text-emerald-400',
                'color_bg' => 'bg-emerald-100 dark:bg-emerald-900/30',
                'icon' => '<i class="ph-fill ph-check-circle text-xl text-emerald-600"></i>'
            ],
            [
                'label' => 'Regras Pausadas',
                'value' => $totalPausadas,
                'color_text' => 'text-gray-600 dark:text-gray-400',
                'color_bg' => 'bg-gray-100 dark:bg-gray-800',
                'icon' => '<i class="ph-fill ph-pause-circle text-xl text-gray-500"></i>'
            ],
        ];

        return view('livewire.comunicacao.automacao.automacao-manager', [
            'registros' => $query->paginate($this->porPagina),
            'metricas'  => $metricas,
        ])->layout('components.layouts.app', ['title' => 'Gestão de Automações']);
    }
}