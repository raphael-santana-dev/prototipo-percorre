<?php

namespace App\Modules\Importacao\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\ImportacaoConfig;

#[Layout('components.layouts.app')]
#[Title('Configurações de Integração - DEV')]
class ImportacaoConfigManager extends Component
{
    public $configs;
    public $modalAberto = false;
    
    // Campos do formulário
    public $configId, $coluna, $model_class, $campo_busca = 'nome', $auto_cadastro = false, $payload_padrao;

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev'), 403, 'Acesso exclusivo para Desenvolvedores.');
        $this->carregarConfigs();
    }

    public function carregarConfigs()
    {
        $this->configs = ImportacaoConfig::all();
    }

    public function abrirModalNovo()
    {
        $this->reset(['configId', 'coluna', 'model_class', 'campo_busca', 'auto_cadastro', 'payload_padrao']);
        $this->modalAberto = true;
    }

    public function editar($id)
    {
        $config = ImportacaoConfig::findOrFail($id);
        $this->configId = $config->id;
        $this->coluna = $config->coluna;
        $this->model_class = $config->model_class;
        $this->campo_busca = $config->campo_busca;
        $this->auto_cadastro = $config->auto_cadastro;
        $this->payload_padrao = $config->payload_padrao ? json_encode($config->payload_padrao) : '';
        $this->modalAberto = true;
    }

    public function salvar()
    {
        $this->validate([
            'coluna' => 'required|string',
            'model_class' => 'required|string',
            'campo_busca' => 'required|string',
        ]);

        $payload = null;
        if (!empty($this->payload_padrao)) {
            $payload = json_decode($this->payload_padrao, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->addError('payload_padrao', 'O JSON informado é inválido.');
                return;
            }
        }

        ImportacaoConfig::updateOrCreate(
            ['id' => $this->configId],
            [
                'coluna' => $this->coluna,
                'model_class' => $this->model_class,
                'campo_busca' => $this->campo_busca,
                'auto_cadastro' => $this->auto_cadastro,
                'payload_padrao' => $payload,
            ]
        );

        $this->modalAberto = false;
        $this->carregarConfigs();
        $this->dispatch('sucesso', msg: 'Configuração salva com sucesso!');
    }

    public function excluir($id)
    {
        ImportacaoConfig::findOrFail($id)->delete();
        $this->carregarConfigs();
        $this->dispatch('sucesso', msg: 'Configuração removida.');
    }

    public function render()
    {
        return view('livewire.importacao.importacao-config-manager');
    }
}