<?php

namespace App\Modules\Comunicacao\UI\Livewire\Automacao;

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Modules\Comunicacao\Domain\Models\Automacao;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;
use App\Modules\Comunicacao\Services\EmailParserService;
use App\Models\StatusInscricao;
use App\Helpers\BreadcrumbHelper;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class AutomacaoForm extends Component
{
    public ?int $automacaoId = null;
    public $nome = '';
    public $evento_gatilho = '';
    public $parametro_dias = 3;
    public string $canal_disparo = 'email'; // 'email', 'whatsapp', 'ambos'
    public $template_id = '';
    public $tipo_acao = 'enviar_email';
    public bool $status = true;
    public string $abaPreview = 'email';

    public array $variaveisTemplateMap = [];
    public array $eventosDisponiveis = [];
    public array $breadcrumbs = [];

    public function mount($id = null)
    {
        $this->eventosDisponiveis['inscricao.primeiro_contato'] = 'Inscrição: 1º Contato Manual (Botão na Listagem de Inscrições)';
        $this->eventosDisponiveis['inscricao.criada']           = 'Inscrição: Novo Cadastro Manual (Link de Retomada)';
        $this->eventosDisponiveis['inscricao.finalizada']       = 'Inscrição: Formulário Concluído (Etapa Finalizada)';
        $this->eventosDisponiveis['inscricao.abandono']         = 'Inscrição: Abandono de Formulário (Todas as fases liberadas e não finalizado)';
        $this->eventosDisponiveis['formulario.abandono']        = 'Formulário Público: Abandono de Preenchimento (Não finalizado)';
        $this->eventosDisponiveis['inscricao.fim_proximo']      = 'Inscrição: Quando faltar N dias para o fim do processo de inscrição';
        
        $statusInscricoes = StatusInscricao::orderBy('nome')->get();
        foreach ($statusInscricoes as $st) {
            $slug = Str::slug($st->nome, '_');
            $this->eventosDisponiveis["inscricao.status.{$slug}"] = "Inscrição: Status alterado para '{$st->nome}'";
        }

        $this->eventosDisponiveis['inscricao.solicitacao_cadastro']    = 'Coordenação: Solicitação de Cadastro de Inscrição';
        $this->eventosDisponiveis['inscricao.solicitacao_alteracao']   = 'Coordenação: Solicitação de Alteração em Ciclo Bloqueado';
        $this->eventosDisponiveis['avaliacao.solicitacao_aluno']       = 'Avaliação: Aluno solicita reabertura de fase';
        $this->eventosDisponiveis['avaliacao.solicitacao_admin']       = 'Avaliação: Professor solicita reabertura de matriz';
        $this->eventosDisponiveis['usuario.criado']                    = 'Corporativo: Novo Cadastro de Usuário';
        $this->eventosDisponiveis['aprendizagem.nova_avaliacao_equipe']= 'Aprendizagem: Nova Avaliação (Notificar Equipe)';
        $this->eventosDisponiveis['aprendizagem.nova_avaliacao_aluno'] = 'Aprendizagem: Nova Avaliação (Notificar Aluno)';
        $this->eventosDisponiveis['aprendizagem.nova_avaliacao_empresa']= 'Aprendizagem: Nova Avaliação (Notificar Empresa/Gestor)';
        $this->eventosDisponiveis['solicitacao.alteracao_academica']   = 'Solicitações: Resposta de Alteração Acadêmica';

        $this->eventosDisponiveis = array_merge(
            $this->eventosDisponiveis,
            $this->listarGatilhosDeFasesDisponiveis()
        );

        if ($id) {
            $automacao = Automacao::with('template')->findOrFail($id);
            $this->automacaoId    = $automacao->id;
            $this->nome           = $automacao->nome;
            $this->evento_gatilho = $automacao->evento_gatilho;
            $this->parametro_dias = $automacao->parametro_dias ?? 3;
            $this->template_id    = $automacao->template_id;
            $this->canal_disparo  = $automacao->canal_disparo ?: ($automacao->template->canal ?? 'email');
            $this->tipo_acao      = $automacao->tipo_acao ?? 'enviar_email';
            $this->status         = (bool) $automacao->status;

            $this->carregarMapaVariaveisDoTemplate($this->template_id);
        }

        $this->abaPreview = $this->canal_disparo === 'whatsapp' ? 'whatsapp' : 'email';
        $this->breadcrumbs = BreadcrumbHelper::generate();
    }

    #[Computed]
    public function exigeParametroDias(): bool
    {
        return in_array($this->evento_gatilho, [
            'inscricao.fim_proximo',
            'inscricao.abandono',
            'formulario.abandono',
        ], true);
    }

    public function updatedCanalDisparo(string $novoCanal): void
    {
        $this->abaPreview = $novoCanal === 'whatsapp' ? 'whatsapp' : 'email';

        if ($this->template_id) {
            $tpl = EmailTemplate::find($this->template_id);
            if ($tpl && !$this->templateCompativelComCanal($tpl, $novoCanal)) {
                $this->template_id = '';
                $this->variaveisTemplateMap = [];
            }
        }
    }

    public function updatedTemplateId($id): void
    {
        $this->carregarMapaVariaveisDoTemplate($id);
    }

    public function carregarMapaVariaveisDoTemplate($id): void
    {
        $this->variaveisTemplateMap = [];
        if (!$id) return;

        $tpl = EmailTemplate::find($id);
        if (!$tpl) return;

        $vars = EmailParserService::extrairVariaveisDoCorpo($tpl->corpo);
        $cfg  = is_array($tpl->configuracoes_suri)
            ? $tpl->configuracoes_suri
            : (json_decode($tpl->configuracoes_suri ?? '{}', true) ?: []);

        $mapaSalvo = is_array($cfg['variable_map'] ?? null) ? $cfg['variable_map'] : [];

        foreach ($vars as $v) {
            $this->variaveisTemplateMap[$v] = $mapaSalvo[$v] ?? (is_numeric($v) ? 'nome' : strtolower($v));
        }
    }

    public function templateCompativelComCanal(EmailTemplate $tpl, string $canalDisparo): bool
    {
        $canalTpl = $tpl->canal ?? 'email';

        return match ($canalDisparo) {
            'email'    => in_array($canalTpl, ['email', 'ambos'], true),
            'whatsapp' => in_array($canalTpl, ['whatsapp', 'ambos'], true),
            'ambos'    => $canalTpl === 'ambos',
            default    => false,
        };
    }

    #[Computed]
    public function templatesCompativeis()
    {
        return EmailTemplate::orderBy('nome')->get()->filter(function (EmailTemplate $tpl) {
            return $this->templateCompativelComCanal($tpl, $this->canal_disparo);
        })->values();
    }

    #[Computed]
    public function templateSelecionado()
    {
        if (empty($this->template_id)) {
            return null;
        }
        return EmailTemplate::find($this->template_id);
    }

    public function abrirQuickViewTemplate(): void
    {
        $tpl = $this->templateSelecionado;
        if (!$tpl) return;

        $canal = strtoupper($tpl->canal ?? 'email');
        $suriInfo = $tpl->suri_template_id ? "<span class='font-mono text-xs text-emerald-600'>Suri ID: {$tpl->suri_template_id}</span>" : '';

        $this->dispatch('load-quick-view', [
            'title'    => 'Pré-visualização: ' . $tpl->nome,
            'subtitle' => "Canal do Template: {$canal}",
            'icon'     => 'ph-eye',
            'maxWidth' => '2xl',
            'data'     => [
                'Assunto / Identificação' => "<div class='flex items-center justify-between'><span class='font-bold'>".e($tpl->assunto ?: $tpl->nome)."</span>{$suriInfo}</div>",
                'Conteúdo da Mensagem'    => "<div class='p-4 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 prose prose-sm max-w-none'>{$tpl->corpo}</div>",
            ],
        ]);
    }

    public function listarGatilhosDeFasesDisponiveis(): array
    {
        $gatilhos = [
            'inscricao.fase_liberada'  => 'Inscrição: Qualquer Nova Fase Liberada (Todos os Ciclos)',
            'formulario.fase_liberada' => 'Formulário Público: Qualquer Nova Fase Liberada',
        ];

        if (class_exists(\App\Models\FormularioFase::class)) {
            $fases = \App\Models\FormularioFase::with(['ciclo', 'formulario'])
                ->orderBy('id', 'desc')
                ->get();

            foreach ($fases as $fase) {
                $contexto = $fase->ciclo
                    ? "Ciclo: {$fase->ciclo->nome}"
                    : ($fase->formulario ? "Formulário: {$fase->formulario->titulo}" : 'Geral');

                $gatilhos["formulario.fase_liberada.{$fase->id}"] = "Fase Liberada: {$fase->nome} ({$contexto})";

                $slugFase = Str::slug($fase->nome, '_');
                if ($fase->ciclo_id) {
                    $gatilhos["inscricao.fase.{$slugFase}"] = "Inscrição - Fase Liberada: {$fase->nome}";
                } elseif ($fase->formulario_id) {
                    $gatilhos["formulario.fase.{$slugFase}"] = "Formulário - Fase Liberada: {$fase->nome}";
                }
            }
        }

        return $gatilhos;
    }

    public function salvar()
    {
        $regras = [
            'nome'           => 'required|string|min:3|max:255',
            'evento_gatilho' => 'required|string',
            'canal_disparo'  => 'required|in:email,whatsapp,ambos',
            'template_id'    => 'required|exists:email_templates,id',
            'tipo_acao'      => 'required|string',
        ];

        if ($this->exigeParametroDias) {
            $regras['parametro_dias'] = 'required|integer|min:1|max:365';
        }

        $this->validate($regras, [
            'nome.required'           => 'Defina um nome identificador para esta regra.',
            'evento_gatilho.required' => 'Selecione qual evento do sistema disparará esta automação.',
            'template_id.required'    => 'Selecione um template compatível com o canal escolhido.',
            'parametro_dias.required' => 'Informe a quantidade de dias (N dias) para acionar este gatilho.',
        ]);

        $template = EmailTemplate::findOrFail($this->template_id);

        if (!$this->templateCompativelComCanal($template, $this->canal_disparo)) {
            $this->addError('template_id', "O template '{$template->nome}' é do tipo [" . strtoupper($template->canal ?? 'email') . "] e não pode ser usado para disparo [" . strtoupper($this->canal_disparo) . "].");
            return;
        }

        if (in_array($this->canal_disparo, ['whatsapp', 'ambos'], true) && empty($template->suri_template_id)) {
            $this->addError('template_id', "O template selecionado ainda não possui ID sincronizado na Suri (suri_template_id). Sincronize-o antes de ativar o disparo via WhatsApp.");
            return;
        }

        if (!empty($this->variaveisTemplateMap) && Schema::hasColumn('email_templates', 'configuracoes_suri')) {
            $cfgSuri = is_array($template->configuracoes_suri)
                ? $template->configuracoes_suri
                : (json_decode($template->configuracoes_suri ?? '{}', true) ?: []);
            $cfgSuri['variable_map'] = $this->variaveisTemplateMap;
            $template->update(['configuracoes_suri' => $cfgSuri]);
        }

        $dados = [
            'nome'           => $this->nome,
            'evento_gatilho' => $this->evento_gatilho,
            'canal_disparo'  => $this->canal_disparo,
            'template_id'    => $this->template_id,
            'tipo_acao'      => $this->tipo_acao,
            'status'         => (bool) $this->status,
        ];

        if (Schema::hasColumn('automacoes', 'parametro_dias')) {
            $dados['parametro_dias'] = $this->exigeParametroDias ? (int) $this->parametro_dias : null;
        }

        if ($this->automacaoId) {
            Automacao::findOrFail($this->automacaoId)->update($dados);
            $this->dispatch('sucesso', msg: 'Regra de automação atualizada com sucesso!');
        } else {
            Automacao::create($dados);
            $this->dispatch('sucesso', msg: 'Nova regra de automação criada com sucesso!');
        }

        return redirect()->route('automacoes.index');
    }

    public function render()
    {
        return view('livewire.comunicacao.automacao.automacao-form', [
            'templates'    => $this->templatesCompativeis,
            'colunasBanco' => EmailParserService::getColunasBancoDisponiveis(),
        ])->layout('components.layouts.app', [
            'title' => $this->automacaoId ? 'Editar Automação' : 'Nova Automação'
        ]);
    }
}