<?php

namespace App\Modules\Matricula\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Ciclo;
use App\Modules\Matricula\Domain\Models\ConfiguracaoIa;
use App\Modules\Matricula\Domain\Models\DocumentoExigido;

#[Layout('components.layouts.app')]
#[Title('Motor de IA e Matrículas')]
class IaConfigManager extends Component
{
    // Configurações da IA
    public $provedor = 'gemini';
    public $api_key = '';
    public $prompt_documentos = 'Aja como um auditor rigoroso de RH. O usuário enviará a imagem de um documento e os dados que ele preencheu na inscrição. Verifique se a imagem corresponde ao tipo de documento solicitado (Ex: RG, CPF, Histórico). Depois, faça OCR e verifique se o Nome e o CPF da imagem batem perfeitamente com os dados do candidato. Responda ESTRITAMENTE em formato JSON: {"valido": true/false, "motivo_rejeicao": "Caso seja falso, explique brevemente o motivo."}';
    public $is_ativa = false;

    // Configurações de Documentos Exigidos
    public $cicloSelecionado = '';
    public $nomeDocumento = '';
    public $descricaoDocumento = '';
    public $isObrigatorio = true;

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('matricula.configurar'), 403, 'Acesso restrito.');

        $config = ConfiguracaoIa::first();
        if ($config) {
            $this->provedor = $config->provedor;
            $this->api_key = $config->api_key;
            $this->prompt_documentos = $config->prompt_documentos;
            $this->is_ativa = $config->is_ativa;
        }
    }

    public function salvarConfiguracaoIa()
    {
        $this->validate([
            'provedor' => 'required',
            'api_key' => 'required',
            'prompt_documentos' => 'required'
        ]);

        ConfiguracaoIa::updateOrCreate(
            ['id' => 1], 
            [
                'provedor' => $this->provedor,
                'api_key' => $this->api_key,
                'prompt_documentos' => $this->prompt_documentos,
                'is_ativa' => $this->is_ativa
            ]
        );

        $this->dispatch('sucesso', msg: 'Motor de Inteligência Artificial configurado com sucesso!');
    }

    public function adicionarDocumento()
    {
        $this->validate([
            'cicloSelecionado' => 'required|exists:ciclos,id',
            'nomeDocumento' => 'required|string|max:255'
        ]);

        DocumentoExigido::create([
            'ciclo_id' => $this->cicloSelecionado,
            'nome' => $this->nomeDocumento,
            'descricao' => $this->descricaoDocumento,
            'is_obrigatorio' => $this->isObrigatorio
        ]);

        $this->reset(['nomeDocumento', 'descricaoDocumento', 'isObrigatorio']);
        $this->dispatch('sucesso', msg: 'Exigência de documento adicionada ao ciclo.');
    }

    public function excluirDocumento($id)
    {
        DocumentoExigido::findOrFail($id)->delete();
        $this->dispatch('sucesso', msg: 'Exigência removida.');
    }

    public function render()
    {
        return view('livewire.matricula.ia-config-manager', [
            'ciclos' => Ciclo::orderBy('id', 'desc')->get(),
            'documentosAtuais' => DocumentoExigido::with('ciclo')->orderBy('ciclo_id')->get()
        ]);
    }
}