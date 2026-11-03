<?php

namespace App\Modules\Comunicacao\UI\Livewire\Automacao;

use Livewire\Component;
use Livewire\WithPagination;
use App\Modules\Comunicacao\Domain\Models\EsteiraComunicacao;
use App\Modules\Comunicacao\Domain\Models\EsteiraDisparo;
use App\Modules\Comunicacao\Domain\Models\Comunicado;
use App\Modules\Comunicacao\Services\EsteiraComunicacaoService;

class EsteiraDetails extends Component
{
    use WithPagination;

    public EsteiraComunicacao $esteira;
    public array $breadcrumbs = [];

    public string $filtro_busca = '';
    public string $filtro_etapa = '';
    public string $filtro_status = '';
    public string $filtro_data_inicio = '';
    public string $filtro_data_fim = '';

    public function mount($id)
    {
        $this->esteira = EsteiraComunicacao::with(['etapas.template', 'ciclo', 'formulario'])->findOrFail($id);
        $this->breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Esteiras de Disparo', 'url' => route('esteiras.index')],
            ['label' => $this->esteira->nome, 'url' => '#'],
        ];
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtro_busca', 'filtro_etapa', 'filtro_status', 'filtro_data_inicio', 'filtro_data_fim'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtro_busca', 'filtro_etapa', 'filtro_status', 'filtro_data_inicio', 'filtro_data_fim']);
        $this->resetPage();
    }

    public function alternarStatusEsteira()
    {
        $this->esteira->status = !$this->esteira->status;
        $this->esteira->save();
        $this->dispatch('sucesso', msg: $this->esteira->status ? 'Esteira ativada!' : 'Esteira pausada!');
    }

    public function processarEsteiraAgora()
    {
        $enviados = EsteiraComunicacaoService::processarEsteirasAtivas();
        $this->dispatch('sucesso', msg: "Verificação executada! {$enviados} novo(s) disparo(s) processado(s).");
    }

    public function showQuickView(int $disparoId)
    {
        $disparo = EsteiraDisparo::with(['etapa.template'])->findOrFail($disparoId);

        // Localiza o registro correspondente em Comunicados para detalhar leituras e cliques
        $log = $disparo->comunicado_id
            ? Comunicado::with('eventos')->find($disparo->comunicado_id)
            : Comunicado::with('eventos')
                ->where('template_id', $disparo->etapa->template_id ?? 0)
                ->where('destinatarios', 'ilike', '%' . $disparo->destinatario_email . '%')
                ->latest('id')
                ->first();

        $eventosHtml = '<div class="space-y-2 mt-1">';
        if ($log && $log->eventos && $log->eventos->count() > 0) {
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
            'title'    => $disparo->destinatario_nome ?: $disparo->destinatario_email,
            'subtitle' => 'Etapa #' . ($disparo->etapa->ordem ?? '-') . ' • ' . ($disparo->enviado_em ? $disparo->enviado_em->format('d/m/Y \à\s H:i:s') : '-'),
            'icon'     => 'ph-paper-plane-tilt',
            'data'     => [
                'Destinatário' => '<div class="text-xs"><b>Nome:</b> ' . e($disparo->destinatario_nome ?: '-') . '<br><b>E-mail:</b> ' . e($disparo->destinatario_email) . '</div>',
                'Etapa e Template' => '<div class="text-xs"><b>Etapa:</b> ' . e($disparo->etapa->nome ?? '-') . '<br><b>Template:</b> ' . e($disparo->etapa->template->nome ?? '-') . '<br><b>Observação:</b> ' . e($disparo->motivo_status ?? '-') . '</div>',
                'Resumo de Leituras e Cliques' => $log
                    ? '<div class="text-xs space-y-1"><b>Aberturas:</b> ' . $log->aberturas_count . 'x (1ª leitura: ' . ($log->aberto_em ? $log->aberto_em->format('d/m/Y H:i') : '-') . ')<br><b>Cliques em Links:</b> ' . $log->cliques_count . 'x</div>'
                    : '<span class="text-xs text-gray-400">Sem rastreamento aplicável.</span>',
                'Linha do Tempo (Aberturas e Cliques)' => $eventosHtml,
            ]
        ]);
    }

    public function render()
    {
        $query = EsteiraDisparo::with(['etapa.template'])
            ->where('esteira_id', $this->esteira->id);

        $query->when($this->filtro_busca, function ($q) {
            $q->where(function ($sub) {
                $sub->where('destinatario_email', 'ilike', '%' . $this->filtro_busca . '%')
                    ->orWhere('destinatario_nome', 'ilike', '%' . $this->filtro_busca . '%');
            });
        })
        ->when($this->filtro_etapa !== '', fn($q) => $q->where('etapa_id', (int) $this->filtro_etapa))
        ->when($this->filtro_status !== '', fn($q) => $q->where('status', $this->filtro_status))
        ->when($this->filtro_data_inicio, fn($q) => $q->whereDate('enviado_em', '>=', $this->filtro_data_inicio))
        ->when($this->filtro_data_fim, fn($q) => $q->whereDate('enviado_em', '<=', $this->filtro_data_fim));

        $totalEnviados  = EsteiraDisparo::where('esteira_id', $this->esteira->id)->where('status', 'enviado')->count();
        $totalIgnorados = EsteiraDisparo::where('esteira_id', $this->esteira->id)->where('status', '!=', 'enviado')->count();
        $elegiveisAgora = EsteiraComunicacaoService::contarPublicoElegivel($this->esteira);

        $historico = $query->orderBy('enviado_em', 'desc')->orderBy('id', 'desc')->paginate(15);

        $metricas = [
            [
                'label'      => 'E-mails Enviados',
                'value'      => $totalEnviados,
                'value_size' => 'text-xl',
                'color_text' => 'text-blue-600 dark:text-blue-400',
                'color_bg'   => 'bg-blue-100 dark:bg-blue-900/30',
                'icon'       => '<i class="ph-fill ph-paper-plane-tilt text-2xl text-blue-600"></i>',
            ],
            [
                'label'      => 'Público Elegível Atual',
                'value'      => $elegiveisAgora,
                'value_size' => 'text-xl',
                'color_text' => 'text-purpura-600 dark:text-purpura-400',
                'color_bg'   => 'bg-purpura-100 dark:bg-purpura-900/30',
                'icon'       => '<i class="ph-fill ph-users-three text-2xl text-purpura-600"></i>',
            ],
            [
                'label'      => 'Poupados (Saída/Condição)',
                'value'      => $totalIgnorados,
                'value_size' => 'text-xl',
                'color_text' => 'text-emerald-600 dark:text-emerald-400',
                'color_bg'   => 'bg-emerald-100 dark:bg-emerald-900/30',
                'icon'       => '<i class="ph-fill ph-shield-check text-2xl text-emerald-600"></i>',
            ],
        ];

        return view('livewire.comunicacao.automacao.esteira-details', [
            'historico' => $historico,
            'metricas'  => $metricas,
        ])->layout('components.layouts.app', ['title' => 'Detalhes da Esteira']);
    }
}