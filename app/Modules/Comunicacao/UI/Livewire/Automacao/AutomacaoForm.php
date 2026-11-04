<?php

namespace App\Modules\Comunicacao\UI\Livewire\Automacao;

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Modules\Comunicacao\Domain\Models\Automacao;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;
use App\Models\StatusInscricao;
use App\Helpers\BreadcrumbHelper;
use Illuminate\Support\Str;

class AutomacaoForm extends Component
{
    public ?int $automacaoId = null;
    public $nome = '';
    public $evento_gatilho = '';
    public $template_id = '';
    public $tipo_acao = 'enviar_email';
    public bool $status = true;

    public array $eventosDisponiveis = [];
    public array $breadcrumbs = [];

    public function mount($id = null)
    {
        $statusInscricoes = StatusInscricao::orderBy('nome')->get();
        foreach ($statusInscricoes as $st) {
            $slug = Str::slug($st->nome, '_');
            $this->eventosDisponiveis["inscricao.status.{$slug}"] = "Inscrição: Status alterado para '{$st->nome}'";
        }

        $this->eventosDisponiveis['inscricao.criada'] = 'Inscrição: Novo Cadastro Manual (Link de Retomada)';
        $this->eventosDisponiveis['inscricao.finalizada'] = 'Inscrição: Formulário Concluído (Etapa Finalizada)';
        $this->eventosDisponiveis['inscricao.solicitacao_cadastro'] = 'Coordenação: Solicitação de Cadastro de Inscrição';
        $this->eventosDisponiveis['inscricao.solicitacao_alteracao'] = 'Coordenação: Solicitação de Alteração em Ciclo Bloqueado';
        $this->eventosDisponiveis['avaliacao.solicitacao_aluno'] = 'Avaliação: Aluno solicita reabertura de fase';
        $this->eventosDisponiveis['avaliacao.solicitacao_admin'] = 'Avaliação: Professor solicita reabertura de matriz';
        $this->eventosDisponiveis['usuario.criado'] = 'Corporativo: Novo Cadastro de Usuário';
        $this->eventosDisponiveis['aprendizagem.nova_avaliacao_equipe'] = 'Aprendizagem: Nova Avaliação (Notificar Equipe)';
        $this->eventosDisponiveis['aprendizagem.nova_avaliacao_aluno'] = 'Aprendizagem: Nova Avaliação (Notificar Aluno)';
        $this->eventosDisponiveis['aprendizagem.nova_avaliacao_empresa'] = 'Aprendizagem: Nova Avaliação (Notificar Empresa/Gestor)';
        $this->eventosDisponiveis['solicitacao.alteracao_academica'] = 'Solicitações: Resposta de Alteração Acadêmica';

        $this->eventosDisponiveis = array_merge(
            $this->eventosDisponiveis, 
            $this->listarGatilhosDeFasesDisponiveis()
        );

        if ($id) {
            //abort_if(!feature('automacao.editar'), 403);
            //abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('automacao.editar'), 403);

            $automacao = Automacao::findOrFail($id);
            $this->automacaoId = $automacao->id;
            $this->nome = $automacao->nome;
            $this->evento_gatilho = $automacao->evento_gatilho;
            $this->template_id = $automacao->template_id;
            $this->tipo_acao = $automacao->tipo_acao ?? 'enviar_email';
            $this->status = (bool) $automacao->status;
        } else {
            //abort_if(!feature('automacao.criar'), 403);
            //abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('automacao.criar'), 403);
        }

        $this->breadcrumbs = BreadcrumbHelper::generate();
    }

    #[Computed]
    public function templateSelecionado()
    {
        if (empty($this->template_id)) {
            return null;
        }
        return EmailTemplate::find($this->template_id);
    }

    /**
     * Retorna a lista de gatilhos de Fases de Formulários/Ciclos disponíveis para criar automações.
     */
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

                $slugFase = \Illuminate\Support\Str::slug($fase->nome, '_');
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
        if ($this->automacaoId) {
            //abort_if(!feature('automacao.editar'), 403);
            //abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('automacao.editar'), 403);
        } else {
            //abort_if(!feature('automacao.criar'), 403);
            //abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('automacao.criar'), 403);
        }

        $this->validate([
            'nome'           => 'required|string|min:3|max:255',
            'evento_gatilho' => 'required|string',
            'template_id'    => 'required|exists:email_templates,id',
            'tipo_acao'      => 'required|string',
        ], [
            'nome.required'           => 'Defina um nome identificador para esta regra.',
            'evento_gatilho.required' => 'Selecione qual evento do sistema disparará esta automação.',
            'template_id.required'    => 'Selecione o template de mensagem que será enviado.',
        ]);

        $dados = [
            'nome'           => $this->nome,
            'evento_gatilho' => $this->evento_gatilho,
            'template_id'    => $this->template_id,
            'tipo_acao'      => $this->tipo_acao,
            'status'         => (bool) $this->status,
        ];

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
            'templates' => EmailTemplate::orderBy('nome')->get()
        ])->layout('components.layouts.app', [
            'title' => $this->automacaoId ? 'Editar Automação' : 'Nova Automação'
        ]);
    }
}