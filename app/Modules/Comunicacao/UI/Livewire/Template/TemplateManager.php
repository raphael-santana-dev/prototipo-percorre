<?php

namespace App\Modules\Comunicacao\UI\Livewire\Template;

use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use App\Helpers\BreadcrumbHelper;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;
use App\Modules\Teste\Suri\Services\SuriApiService;

class TemplateManager extends Component
{
    use WithPagination, ComPadraoListagem;

    public array $breadcrumbs = [];
    public string $filtro_busca = '';

    // Controle de Abas: 'locais' ou 'suri_remotos'
    public string $abaAtiva = 'locais';

    // Listagem e Detalhes retornados da API da Suri (GET /api/templates e GET /api/templates/:id)
    public array $templatesSuriRemotos = [];
    public string $buscaSuriRemoto = '';
    public ?array $templateSuriDetalhe = null;
    public bool $modalDetalheSuriAberto = false;
    public ?string $erroApiSuri = null;

    public function updating($nomePropriedade)
    {
        if ($nomePropriedade === 'filtro_busca') {
            $this->resetPage();
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtro_busca', 'buscaSuriRemoto']);
        $this->resetPage();
    }

    public function mount()
    {
        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->permiteGrid = false;
    }

    public function alternarAba(string $aba): void
    {
        $this->abaAtiva = $aba;
        if ($aba === 'suri_remotos' && empty($this->templatesSuriRemotos)) {
            $this->carregarTemplatesDaSuri();
        }
    }

    /**
     * Consulta GET {{baseUrl}}/api/templates e popula a listagem específica da Suri.
     */
    public function carregarTemplatesDaSuri(): void
    {
        $this->erroApiSuri = null;
        $res = app(SuriApiService::class)->listarTemplates();

        if (!$res['sucesso']) {
            $this->erroApiSuri = data_get($res, 'resposta.error')
                ?? data_get($res, 'resposta.erro')
                ?? "Falha ao consultar GET /api/templates (HTTP {$res['http_status']}).";
            $this->templatesSuriRemotos = [];
            return;
        }

        $lista = data_get($res, 'resposta.data.items')
            ?? data_get($res, 'resposta.data.data')
            ?? data_get($res, 'resposta.data')
            ?? data_get($res, 'resposta.items')
            ?? (is_array($res['resposta']) && isset($res['resposta'][0]) ? $res['resposta'] : []);

        $this->templatesSuriRemotos = is_array($lista) ? $lista : [];
        $this->dispatch('sucesso', msg: count($this->templatesSuriRemotos) . ' template(s) retornado(s) da Suri!');
    }

    /**
     * Consulta GET {{baseUrl}}/api/templates/:template_id para inspecionar um template específico da Suri.
     */
    public function inspecionarTemplateSuriEspecifico(string $templateId): void
    {
        $res = app(SuriApiService::class)->obterTemplate($templateId);

        if (!$res['sucesso']) {
            $this->dispatch('erro', msg: "Não foi possível carregar os detalhes do template '{$templateId}' na Suri.");
            return;
        }

        $this->templateSuriDetalhe = data_get($res, 'resposta.data') ?? $res['resposta'];
        $this->modalDetalheSuriAberto = true;
    }

    /**
     * Importa um template retornado da Suri para a tabela local email_templates caso ainda não exista.
     */
    public function importarTemplateDaSuriParaLocal(string $suriTemplateId, string $titulo, string $corpo, string $categoria = 'MARKETING'): void
    {
        $existente = EmailTemplate::where('suri_template_id', $suriTemplateId)->first();
        if ($existente) {
            $this->dispatch('erro', msg: "Este template da Suri já está vinculado ao template local #{$existente->id} ({$existente->nome}).");
            return;
        }

        EmailTemplate::create([
            'nome'               => $titulo ?: "Template Suri {$suriTemplateId}",
            'assunto'            => $titulo ?: 'Mensagem WhatsApp',
            'corpo'              => nl2br(e($corpo)),
            'canal'              => 'whatsapp',
            'suri_template_id'   => $suriTemplateId,
            'configuracoes_suri' => ['category' => $categoria],
        ]);

        $this->dispatch('sucesso', msg: "Template '{$titulo}' importado da Suri para o sistema local!");
    }

    public function sincronizarSuri($id)
    {
        $template = EmailTemplate::findOrFail($id);

        if (!empty($template->suri_template_id)) {
            $this->dispatch('erro', msg: 'Este template já está sincronizado.');
            return;
        }

        $res = app(SuriApiService::class)->syncTemplateComDetalhes($template);

        if (!empty($res['suri_template_id'])) {
            $template->update(['suri_template_id' => $res['suri_template_id']]);
            $this->dispatch('sucesso', msg: 'Template sincronizado com a Suri com sucesso!');
        } else {
            $erroMsg = data_get($res, 'resposta.error') ?? 'Verifique os logs do sistema.';
            $this->dispatch('erro', msg: "Falha ao sincronizar: {$erroMsg}");
        }
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => '#ID', 'sortable' => true, 'class' => 'w-16'],
            ['key' => 'nome', 'label' => 'Nome do Template', 'sortable' => true],
            ['key' => 'canal', 'label' => 'Canal', 'sortable' => true, 'class' => 'w-36 text-center'],
            ['key' => 'assunto', 'label' => 'Assunto / ID Suri', 'sortable' => true],
            ['key' => 'acoes', 'label' => '', 'sortable' => false, 'class' => 'text-right w-32'],
        ];
    }

    public function preVisualizar($id)
    {
        $template = EmailTemplate::findOrFail($id);
        $canal = $template->canal ?? 'email';
        $botoes = is_array($template->botoes) ? $template->botoes : [];
        $conteudoHtml = $template->corpo ?? '<p class="text-gray-500 italic">Sem conteúdo disponível.</p>';

        $html = '<div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5 mt-2 shadow-inner">';
        $html .= '  <div class="mb-4 pb-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">';
        $html .= '      <div><span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-0.5">Assunto:</span>';
        $html .= '      <span class="block text-sm font-bold text-gray-900 dark:text-gray-100">' . e($template->assunto) . '</span></div>';
        $html .= '      <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded bg-purpura-100 text-purpura-700">' . strtoupper($canal) . '</span>';
        $html .= '  </div>';
        $html .= '  <div class="prose prose-sm max-w-none text-gray-700 dark:text-gray-300">' . $conteudoHtml . '</div>';

        if (!empty($botoes)) {
            $html .= '<div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700 space-y-1.5">';
            $html .= '<span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">Botões / Opções Configuradas:</span>';
            foreach ($botoes as $b) {
                $txt = $b['texto'] ?? $b['text'] ?? '';
                if (empty($txt)) continue;
                $html .= '<div class="px-3 py-1.5 bg-white dark:bg-gray-900 border border-gray-200 rounded text-xs font-bold text-purpura-700">' . e($txt) . '</div>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';

        $this->dispatch('load-quick-view', [
            'title'    => 'Pré-visualização do Template',
            'subtitle' => $template->nome,
            'icon'     => 'ph-envelope-open',
            'maxWidth' => '2xl',
            'data'     => ['Layout da Mensagem' => $html],
        ]);
    }

    public function excluir($id)
    {
        try {
            EmailTemplate::findOrFail($id)->delete();
            $this->dispatch('sucesso', msg: 'Template removido com sucesso!');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->dispatch('erro', msg: 'Não é possível excluir este template pois ele já está em uso.');
        }
    }

    public function render()
    {
        $query = EmailTemplate::query();
        $query->when($this->filtro_busca, function ($q) {
            $q->where('nome', 'ilike', '%' . $this->filtro_busca . '%')
              ->orWhere('assunto', 'ilike', '%' . $this->filtro_busca . '%')
              ->orWhere('suri_template_id', 'ilike', '%' . $this->filtro_busca . '%');
        });

        $query->orderBy($this->ordenacaoCampo ?: 'id', $this->ordenacaoDirecao ?: 'desc');

        $templatesSuriFiltrados = collect($this->templatesSuriRemotos);
        if (!empty(trim($this->buscaSuriRemoto))) {
            $termo = mb_strtolower(trim($this->buscaSuriRemoto), 'UTF-8');
            $templatesSuriFiltrados = $templatesSuriFiltrados->filter(function ($item) use ($termo) {
                $str = mb_strtolower(json_encode($item), 'UTF-8');
                return str_contains($str, $termo);
            })->values();
        }

        return view('livewire.comunicacao.template.template-manager', [
            'registros'              => $query->paginate($this->porPagina),
            'templatesSuriFiltrados' => $templatesSuriFiltrados,
        ])->layout('components.layouts.app', ['title' => 'Templates de Comunicação']);
    }
}