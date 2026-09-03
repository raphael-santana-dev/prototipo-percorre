<?php

namespace App\Modules\Comunicacao\UI\Livewire\Automacao;

use Livewire\Component;
use App\Modules\Comunicacao\Domain\Models\Automacao;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;
use App\Models\StatusInscricao;
use Illuminate\Support\Str;

class AutomacaoForm extends Component
{
    public ?int $automacaoId = null;
    public $nome = '';
    public $evento_gatilho = '';
    public $template_id = '';
    public $status = true;

    public $eventosDisponiveis = [];

    public function mount($id = null)
    {
        // 1. Busca TODOS os status criados no sistema dinamicamente
        $statusInscricoes = \App\Models\StatusInscricao::orderBy('nome')->get();
        
        foreach ($statusInscricoes as $st) {
            // Converte "Em Análise" para "em_analise" automaticamente
            $slug = \Illuminate\Support\Str::slug($st->nome, '_');
            $this->eventosDisponiveis["inscricao.status.{$slug}"] = "Inscrição: Status alterado para '{$st->nome}'";
        }

        $this->eventosDisponiveis['inscricao.criada'] = 'Inscrição: Novo Cadastro (Link de Retomada)';
        
        $this->eventosDisponiveis['usuario.criado'] = 'Usuário: Novo Cadastro de Usuário';

        if ($id) {
            abort_if(!feature('automacao.editar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('automacao.editar'), 403);

            $automacao = Automacao::findOrFail($id);
            $this->automacaoId = $automacao->id;
            $this->nome = $automacao->nome;
            $this->evento_gatilho = $automacao->evento_gatilho;
            $this->template_id = $automacao->template_id;
            $this->status = $automacao->status;
        }
    }

    public function salvar()
    {
        $this->validate([
            'nome' => 'required|string|max:255',
            'evento_gatilho' => 'required|string',
            'template_id' => 'required|exists:email_templates,id',
        ]);

        $dados = [
            'nome' => $this->nome,
            'evento_gatilho' => $this->evento_gatilho,
            'template_id' => $this->template_id,
            'status' => $this->status,
        ];

        if ($this->automacaoId) {
            Automacao::findOrFail($this->automacaoId)->update($dados);
            session()->flash('sucesso', 'Automação atualizada com sucesso!');
        } else {
            Automacao::create($dados);
            session()->flash('sucesso', 'Automação criada com sucesso!');
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