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

    public string $filtro_busca = '';
    public string $filtro_data_inicio = '';
    public string $filtro_data_fim = '';

    public function mount($id)
    {
        //abort_if(!feature('automacao.visualizar'), 403);
        //abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('automacao.visualizar'), 403);
        
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

    public function alternarStatusRegra()
    {
        //abort_if(!feature('automacao.editar'), 403);
        //abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('automacao.editar'), 403);

        $this->automacao->status = !$this->automacao->status;
        $this->automacao->save();
        $this->dispatch('sucesso', msg: $this->automacao->status ? 'Regra ativada!' : 'Regra pausada!');
    }

    public function showQuickView($id)
    {
        $log = Comunicado::with(['template', 'eventos'])->findOrFail($id);

        $destinatariosHtml = '';
        if (is_array($log->destinatarios)) {
            $destinatariosHtml = '<ul class="list-disc pl-4 text-xs font-medium text-gray-700 dark:text-gray-300 space-y-1">';
            foreach ($log->destinatarios as $dest) {
                $destinatariosHtml .= '<li>' . e($dest) . '</li>';
            }
            $destinatariosHtml .= '</ul>';
        } else {
            $destinatariosHtml = '<span class="text-xs text-gray-700 dark:text-gray-300">' . e($log->destinatarios ?? 'Nenhum') . '</span>';
        }

        $eventosHtml = '<div class="space-y-2 mt-1">';
        if ($log->eventos && $log->eventos->count() > 0) {
            foreach ($log->eventos as $ev) {
                $badge = $ev->tipo === 'clique'
                    ? '<span class="px-2 py-0.5 text-[10px] font-bold bg-purpura-100 text-purpura-700 rounded">CLIQUE</span>'
                    : '<span class="px-2 py-0.5 text-[10px] font-bold bg-emerald-100 text-emerald-700 rounded">ABERTURA</span>';
                $detalhe = $ev->tipo === 'clique'
                    ? "<div class='text-xs mt-1'><b>Botão:</b> " . e($ev->texto_link) . "<br><b>URL:</b> <a href='" . e($ev->url_destino) . "' target='_blank' class='text-purpura-600 underline'>" . e($ev->url_destino) . "</a></div>"
                    : '';
                $eventosHtml .= "<div class='p-2.5 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 text-xs'>
                    <div class='flex justify-between items-center'>{$badge} <span class='font-mono text-[10px] text-gray-500'>{$ev->created_at->format('d/m/Y H:i:s')}</span></div>
                    {$detalhe}
                    <div class='text-[10px] text-gray-400 mt-1'>{$ev->dispositivo} • {$ev->navegador} • IP: {$ev->ip}</div>
                </div>";
            }
        } else {
            $eventosHtml .= '<span class="text-xs text-gray-400 italic">Nenhuma abertura ou clique registrado até o momento.</span>';
        }
        $eventosHtml .= '</div>';

        $this->dispatch('load-quick-view', [
            'title' => 'Auditoria de Engajamento do Disparo',
            'subtitle' => 'Enviado em ' . $log->created_at->format('d/m/Y \à\s H:i:s'),
            'icon' => 'ph-paper-plane-tilt',
            'data' => [
                'Status do Envio' => '<span class="px-2.5 py-1 text-xs font-bold rounded-full border border-emerald-200 bg-emerald-50 text-emerald-700"><i class="ph-bold ph-check mr-1"></i> Entregue</span>',
                'Resumo de Leituras e Cliques' => '<div class="text-xs space-y-1"><b>Aberturas:</b> ' . $log->aberturas_count . 'x (1ª leitura: ' . ($log->aberto_em ? $log->aberto_em->format('d/m/Y H:i') : '-') . ')<br><b>Cliques em Links:</b> ' . $log->cliques_count . 'x</div>',
                'Destinatários' => '<div class="max-h-32 overflow-y-auto custom-scrollbar bg-gray-50 dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700 mt-1">' . $destinatariosHtml . '</div>',
                'Linha do Tempo (Aberturas e Cliques)' => $eventosHtml,
            ]
        ]);
    }

    public function render()
    {
        $query = Comunicado::where('template_id', $this->automacao->template_id)
            ->where('status', 'concluido');

        $query->when($this->filtro_busca, function ($q) {
            $q->where('destinatarios', 'ilike', '%' . $this->filtro_busca . '%');
        })
        ->when($this->filtro_data_inicio, fn($q) => $q->whereDate('created_at', '>=', $this->filtro_data_inicio))
        ->when($this->filtro_data_fim, fn($q) => $q->whereDate('created_at', '<=', $this->filtro_data_fim));

        $totalDisparos = (clone $query)->count();
        $totalAbertos  = (clone $query)->where('aberturas_count', '>', 0)->count();
        $totalClicados = (clone $query)->where('cliques_count', '>', 0)->count();

        $taxaAbertura = $totalDisparos > 0 ? round(($totalAbertos / $totalDisparos) * 100, 1) : 0;
        $taxaCliques  = $totalDisparos > 0 ? round(($totalClicados / $totalDisparos) * 100, 1) : 0;

        $historico = $query->orderBy('created_at', 'desc')->paginate(15);

        $metricas = [
            [
                'label' => 'Disparos Entregues',
                'value' => $totalDisparos,
                'value_size' => 'text-xl',
                'color_text' => 'text-blue-600 dark:text-blue-400',
                'color_bg' => 'bg-blue-100 dark:bg-blue-900/30',
                'icon' => '<i class="ph-fill ph-paper-plane-tilt text-2xl text-blue-600"></i>'
            ],
            [
                'label' => 'Taxa de Abertura',
                'value' => "{$taxaAbertura}% ({$totalAbertos})",
                'value_size' => 'text-xl',
                'color_text' => 'text-emerald-600 dark:text-emerald-400',
                'color_bg' => 'bg-emerald-100 dark:bg-emerald-900/30',
                'icon' => '<i class="ph-fill ph-envelope-open text-2xl text-emerald-600"></i>'
            ],
            [
                'label' => 'Taxa de Cliques (CTR)',
                'value' => "{$taxaCliques}% ({$totalClicados})",
                'value_size' => 'text-xl',
                'color_text' => 'text-purpura-600 dark:text-purpura-400',
                'color_bg' => 'bg-purpura-100 dark:bg-purpura-900/30',
                'icon' => '<i class="ph-fill ph-cursor-click text-2xl text-purpura-600"></i>'
            ]
        ];

        return view('livewire.comunicacao.automacao.automacao-details', [
            'historico' => $historico,
            'metricas'  => $metricas
        ])->layout('components.layouts.app', ['title' => 'Métricas da Automação']);
    }
}