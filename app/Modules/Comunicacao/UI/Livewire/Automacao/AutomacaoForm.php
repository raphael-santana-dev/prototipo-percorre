<?php

namespace App\Modules\Comunicacao\UI\Livewire\Automacao;

use Livewire\Component;
use App\Modules\Comunicacao\Domain\Models\Automacao;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;

class AutomacaoForm extends Component
{
    public $nome = '';
    public $evento_gatilho = '';
    public $template_id = '';
    public $status = true;

    // Dicionário de Eventos (Onde você cadastra as opções pro usuário)
    public $eventosDisponiveis = [
        'inscricao.criada' => 'Inscrição: Nova Ficha de Inscrição Recebida',
        'inscricao.aprovada' => 'Inscrição: Status alterado para Aprovado / Selecionado',
        'inscricao.reprovada' => 'Inscrição: Status alterado para Reprovado / Cancelado',
        'inscricao.pendente' => 'Inscrição: Status alterado para Pendente',
        'usuario.criado' => 'Usuário: Novo Cadastro de Usuário',
        'usuario.bloqueado' => 'Usuário: Acesso ao Sistema Bloqueado',
        'usuario.desbloqueado' => 'Usuário: Acesso ao Sistema Liberado',
    ];

    public function salvar()
    {
        $this->validate([
            'nome' => 'required|string|max:255',
            'evento_gatilho' => 'required|string',
            'template_id' => 'required|exists:email_templates,id',
        ]);

        Automacao::create([
            'nome' => $this->nome,
            'evento_gatilho' => $this->evento_gatilho,
            'template_id' => $this->template_id,
            'status' => $this->status,
        ]);

        $this->dispatch('sucesso', msg: 'Automação criada com sucesso!');
        return redirect()->route('automacoes.index');
    }

    public function render()
    {
        return view('livewire.comunicacao.automacao.automacao-form', [
            'templates' => EmailTemplate::orderBy('nome')->get()
        ])->layout('components.layouts.app', ['title' => 'Nova Automação']);
    }
}