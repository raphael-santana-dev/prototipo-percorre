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

    public $ocultar_fases_restritas = false;
    public $permitir_aluno_responder_ambos = false;

    // NOVAS CONFIGURAÇÕES DE VAGAS
    public $regra_ocupacao_vaga = 'por_status';
    public $status_ocupacao_vaga = [];

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403, 'Acesso restrito.');

        if (\Illuminate\Support\Facades\Schema::hasTable('configuracoes_gerais')) {
            $this->ocultar_fases_restritas = ConfiguracaoGeral::where('chave', 'ocultar_fases_restritas')->value('valor') === 'true';
            $this->permitir_aluno_responder_ambos = ConfiguracaoGeral::where('chave', 'permitir_aluno_responder_ambos')->value('valor') === 'true';
            
            // Carrega Configurações de Vagas
            $this->regra_ocupacao_vaga = ConfiguracaoGeral::where('chave', 'regra_ocupacao_vaga')->value('valor') ?? 'por_status';
            
            $statusJson = ConfiguracaoGeral::where('chave', 'status_ocupacao_vaga')->value('valor');
            if ($statusJson) {
                $this->status_ocupacao_vaga = json_decode($statusJson, true) ?? [];
            } else {
                $this->status_ocupacao_vaga = \App\Models\StatusInscricao::whereIn('nome', ['Aprovado', 'aprovado', 'Selecionado', 'selecionado'])->pluck('id')->map(fn($id) => (string)$id)->toArray();
            }
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

        ConfiguracaoGeral::updateOrCreate(
            ['chave' => 'regra_ocupacao_vaga'],
            ['valor' => $this->regra_ocupacao_vaga, 'grupo' => 'processos_seletivos']
        );

        ConfiguracaoGeral::updateOrCreate(
            ['chave' => 'status_ocupacao_vaga'],
            ['valor' => json_encode($this->status_ocupacao_vaga), 'grupo' => 'processos_seletivos']
        );

        $this->dispatch('sucesso', msg: 'Configurações do sistema salvas e registradas na auditoria!');
    }

    public function render()
    {
        $statusDb = \App\Models\StatusInscricao::orderBy('nome')->get();

        return view('livewire.admin.configuracoes-gerais-manager', [
            'statusDb' => $statusDb
        ]);
    }
}