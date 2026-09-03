<?php

namespace App\Modules\Admin\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\ConfiguracaoGeral;

#[Layout('components.layouts.app')]
#[Title('Configurações Gerais')]
class ConfiguracoesGeraisManager extends Component
{
    public $abaLateral = 'gestao_educacional';

    // Opções de Gestão Educacional
    public $ocultar_fases_restritas = false;
    public $permitir_aluno_responder_ambos = false;

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403, 'Acesso restrito.');

        if (\Illuminate\Support\Facades\Schema::hasTable('configuracoes_gerais')) {
            $this->ocultar_fases_restritas = ConfiguracaoGeral::where('chave', 'ocultar_fases_restritas')->value('valor') === 'true';
            $this->permitir_aluno_responder_ambos = ConfiguracaoGeral::where('chave', 'permitir_aluno_responder_ambos')->value('valor') === 'true';
        }
    }

    public function salvar()
    {
        ConfiguracaoGeral::updateOrCreate(
            ['chave' => 'ocultar_fases_restritas'],
            ['valor' => $this->ocultar_fases_restritas ? 'true' : 'false', 'grupo' => 'gestao_educacional']
        );

        ConfiguracaoGeral::updateOrCreate(
            ['chave' => 'permitir_aluno_responder_ambos'],
            ['valor' => $this->permitir_aluno_responder_ambos ? 'true' : 'false', 'grupo' => 'gestao_educacional']
        );

        $this->dispatch('sucesso', msg: 'Configurações do sistema salvas e registradas na auditoria!');
    }

    public function render()
    {
        return view('livewire.admin.configuracoes-gerais-manager');
    }
}