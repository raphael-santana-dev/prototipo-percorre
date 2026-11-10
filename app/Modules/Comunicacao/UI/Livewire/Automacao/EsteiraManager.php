<?php

namespace App\Modules\Comunicacao\UI\Livewire\Automacao;

use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use App\Helpers\BreadcrumbHelper;
use App\Modules\Comunicacao\Domain\Models\EsteiraComunicacao;
use App\Modules\Comunicacao\Domain\Models\EsteiraDisparo;
use App\Modules\Comunicacao\Services\EsteiraComunicacaoService;

class EsteiraManager extends Component
{
    use WithPagination, ComPadraoListagem;

    public array $breadcrumbs = [];
    public string $filtro_busca = '';
    public string $filtro_status = '';
    public string $filtro_publico = '';

    public function mount()
    {
        $this->breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Automações', 'url' => route('automacoes.index')],
            ['label' => 'Esteiras de Disparo', 'url' => route('esteiras.index')],
        ];
        $this->permiteGrid = false;
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtro_busca', 'filtro_status', 'filtro_publico'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtro_busca', 'filtro_status', 'filtro_publico']);
        $this->resetPage();
    }

    public function toggleStatus(int $id): void
    {
        $esteira = EsteiraComunicacao::findOrFail($id);
        $esteira->status = !$esteira->status;
        $esteira->save();

        $this->dispatch('sucesso', msg: $esteira->status ? 'Esteira ativada com sucesso!' : 'Esteira pausada com sucesso!');
    }

    public function processarAgora(): void
    {
        $enviados = EsteiraComunicacaoService::processarEsteirasAtivas();
        $this->dispatch('sucesso', msg: "Processamento manual executado! {$enviados} disparo(s) enviado(s) para a fila.");
    }

    public function excluir(int $id): void
    {
        EsteiraComunicacao::findOrFail($id)->delete();
        $this->dispatch('sucesso', msg: 'Esteira de comunicação excluída.');
    }

    public function getHeadersProperty(): array
    {
        return [
            ['key' => 'id', 'label' => '#ID', 'sortable' => true, 'class' => 'w-16'],
            ['key' => 'nome', 'label' => 'Esteira & Público-Alvo', 'sortable' => true],
            ['key' => 'etapas', 'label' => 'Cadência / Etapas', 'sortable' => false],
            ['key' => 'progresso_etapa', 'label' => 'Etapa Atual / Situação', 'sortable' => false, 'class' => 'text-center'],
            ['key' => 'disparos', 'label' => 'Disparos Realizados', 'sortable' => false, 'class' => 'text-center'],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true, 'class' => 'text-center w-28'],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right w-32'],
        ];
    }

    public function previewTemplate(int $templateId): void
    {
        $template = \App\Modules\Comunicacao\Domain\Models\EmailTemplate::find($templateId);
        if (!$template) {
            $this->dispatch('erro', msg: 'Template não encontrado.');
            return;
        }

        $canal = strtoupper($template->canal ?? 'email');
        $this->dispatch('load-quick-view', [
            'title'    => 'Pré-visualização: ' . $template->nome,
            'subtitle' => "Canal: {$canal}",
            'icon'     => 'ph-eye',
            'maxWidth' => '2xl',
            'data'     => [
                'Assunto'  => '<span class="font-bold text-sm">' . e($template->assunto ?: $template->nome) . '</span>',
                'Mensagem' => '<div class="p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl prose prose-sm max-w-none">' . $template->corpo . '</div>',
            ],
        ]);
    }

    public function render()
    {
        $query = EsteiraComunicacao::with(['etapas.template', 'ciclo', 'formulario'])
            ->withCount([
                'disparos as disparos_enviados_count' => fn($q) => $q->where('status', 'enviado'),
            ]);

        $query->when($this->filtro_busca, function ($q) {
            $q->where('nome', 'ilike', '%' . $this->filtro_busca . '%');
        })
        ->when($this->filtro_status !== '', fn($q) => $q->where('status', (bool) $this->filtro_status))
        ->when($this->filtro_publico !== '', fn($q) => $q->where('publico_alvo', $this->filtro_publico));

        $query->orderBy($this->ordenacaoCampo ?: 'id', $this->ordenacaoDirecao ?: 'desc');

        $totalEsteiras = EsteiraComunicacao::count();
        $totalAtivas   = EsteiraComunicacao::where('status', true)->count();
        $totalDisparos = EsteiraDisparo::where('status', 'enviado')->count();

        $metricas = [
            [
                'label'      => 'Total de Esteiras',
                'value'      => $totalEsteiras,
                'color_text' => 'text-indigo-600 dark:text-indigo-400',
                'color_bg'   => 'bg-indigo-100 dark:bg-indigo-900/30',
                'icon'       => '<i class="ph-fill ph-git-merge text-xl text-indigo-600"></i>',
            ],
            [
                'label'      => 'Esteiras Ativas',
                'value'      => $totalAtivas,
                'color_text' => 'text-emerald-600 dark:text-emerald-400',
                'color_bg'   => 'bg-emerald-100 dark:bg-emerald-900/30',
                'icon'       => '<i class="ph-fill ph-check-circle text-xl text-emerald-600"></i>',
            ],
            [
                'label'      => 'E-mails Disparados',
                'value'      => $totalDisparos,
                'color_text' => 'text-purpura-600 dark:text-purpura-400',
                'color_bg'   => 'bg-purpura-100 dark:bg-purpura-900/30',
                'icon'       => '<i class="ph-fill ph-paper-plane-tilt text-xl text-purpura-600"></i>',
            ],
        ];

        return view('livewire.comunicacao.automacao.esteira-manager', [
            'registros' => $query->paginate($this->porPagina),
            'metricas'  => $metricas,
        ])->layout('components.layouts.app', ['title' => 'Esteiras de Disparo']);
    }
}