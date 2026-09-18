<?php

namespace App\Modules\Matricula\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Ciclo;
use App\Modules\Matricula\Domain\Models\ConfiguracaoIa;
use App\Modules\Matricula\Domain\Models\DocumentoExigido;
use App\Modules\Matricula\Domain\Models\AiProvider;
use App\Modules\Matricula\Domain\Models\AiModel;
use Illuminate\Support\Facades\Crypt;

#[Layout('components.layouts.app')]
#[Title('Motor de IA e Matrículas')]
class IaConfigManager extends Component
{
    public $abaAtiva = 'config'; 

    public $ai_model_id = '';
    public $prompt_documentos = 'Aja como um auditor rigoroso de RH. O usuário enviará a imagem de um documento e os dados que ele preencheu na inscrição. Verifique se a imagem corresponde ao tipo de documento solicitado (Ex: RG, CPF, Histórico). Depois, faça OCR e verifique se o Nome e o CPF da imagem batem perfeitamente com os dados do candidato. Responda ESTRITAMENTE em formato JSON: {"valido": true/false, "motivo_rejeicao": "Caso seja falso, explique brevemente o motivo."}';
    public $is_ativa = false;

    public $modalProviderAberto = false;
    public $provider_id = null;
    public $provider_nome = '';
    public $provider_driver = 'openai_compatible';
    public $provider_api_url = '';
    public $provider_api_key = '';

    public $modalModelAberto = false;
    public $model_id = null;
    public $model_ai_provider_id = '';
    public $model_nome = '';
    public $model_codigo = '';

    // CADASTRO COMBINADO (PROVEDOR + MODELO)
    public $modalCombinedAberto = false;
    public $c_provider_nome = '';
    public $c_provider_driver = 'openai_compatible';
    public $c_provider_api_url = '';
    public $c_provider_api_key = '';
    public $c_model_nome = '';
    public $c_model_codigo = '';

    // DOCUMENTOS
    public $cicloSelecionado = '';
    public $nomeDocumento = '';
    public $descricaoDocumento = '';
    public $isObrigatorio = true;

    private const MASKED_KEY = '********_CHAVE_SALVA_********';

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('matricula.configurar'), 403, 'Acesso restrito.');

        $config = ConfiguracaoIa::first();
        if ($config) {
            $this->ai_model_id = $config->ai_model_id;
            $this->prompt_documentos = $config->prompt_documentos;
            $this->is_ativa = $config->is_ativa;
        }
    }

    public function salvarConfiguracaoIa()
    {
        $this->validate([
            'ai_model_id' => 'required',
            'prompt_documentos' => 'required'
        ], [
            'ai_model_id.required' => 'É obrigatório selecionar qual Modelo de IA será utilizado.'
        ]);

        ConfiguracaoIa::updateOrCreate(['id' => 1], [
            'ai_model_id' => $this->ai_model_id,
            'prompt_documentos' => $this->prompt_documentos,
            'is_ativa' => $this->is_ativa,
            'provedor' => 'dinamico', 
            'api_key' => 'dinamico'
        ]);

        $this->dispatch('sucesso', msg: 'Motor de Inteligência Artificial configurado com segurança!');
    }

    // ==========================================
    // CRUD COMBINADO: PROVEDOR E MODELO
    // ==========================================
    public function abrirModalCombined()
    {
        $this->reset([
            'c_provider_nome', 'c_provider_driver', 'c_provider_api_url', 'c_provider_api_key',
            'c_model_nome', 'c_model_codigo'
        ]);
        $this->modalCombinedAberto = true;
    }

    public function salvarCombined()
    {
        $this->validate([
            'c_provider_nome' => 'required',
            'c_provider_driver' => 'required',
            'c_model_nome' => 'required',
            'c_model_codigo' => 'required',
        ]);

        $providerData = [
            'nome' => $this->c_provider_nome,
            'driver' => $this->c_provider_driver,
            'api_url' => $this->c_provider_api_url,
        ];

        if (!empty($this->c_provider_api_key)) {
            $providerData['api_key'] = Crypt::encryptString($this->c_provider_api_key);
        }

        // 1. Cria o Provedor
        $novoProvedor = AiProvider::create($providerData);

        // 2. Cria o Modelo Vinculado ao novo Provedor
        AiModel::create([
            'ai_provider_id' => $novoProvedor->id,
            'nome' => $this->c_model_nome,
            'codigo' => $this->c_model_codigo,
        ]);

        $this->modalCombinedAberto = false;
        $this->dispatch('sucesso', msg: 'Provedor e Modelo criados e vinculados com sucesso!');
    }


    // ==========================================
    // CRUD: PROVEDORES
    // ==========================================
    public function abrirModalProvider($id = null)
    {
        $this->reset(['provider_id', 'provider_nome', 'provider_driver', 'provider_api_url', 'provider_api_key']);
        if ($id) {
            $p = AiProvider::findOrFail($id);
            $this->provider_id = $p->id;
            $this->provider_nome = $p->nome;
            $this->provider_driver = $p->driver;
            $this->provider_api_url = $p->api_url;
            $this->provider_api_key = empty($p->api_key) ? '' : self::MASKED_KEY;
        }
        $this->modalProviderAberto = true;
    }

    public function salvarProvider()
    {
        $this->validate([
            'provider_nome' => 'required',
            'provider_driver' => 'required',
        ]);

        $data = [
            'nome' => $this->provider_nome,
            'driver' => $this->provider_driver,
            'api_url' => $this->provider_api_url,
        ];

        if ($this->provider_api_key && $this->provider_api_key !== self::MASKED_KEY) {
            $data['api_key'] = Crypt::encryptString($this->provider_api_key);
        } elseif (empty($this->provider_api_key)) {
            $data['api_key'] = null;
        }

        if ($this->provider_id) {
            AiProvider::findOrFail($this->provider_id)->update($data);
        } else {
            AiProvider::create($data);
        }

        $this->modalProviderAberto = false;
        $this->dispatch('sucesso', msg: 'Provedor de IA salvo com sucesso!');
    }

    public function excluirProvider($id)
    {
        AiProvider::findOrFail($id)->delete();
        $this->dispatch('sucesso', msg: 'Provedor removido.');
    }

    public function abrirModalModel($id = null)
    {
        $this->reset(['model_id', 'model_ai_provider_id', 'model_nome', 'model_codigo']);
        if ($id) {
            $m = AiModel::findOrFail($id);
            $this->model_id = $m->id;
            $this->model_ai_provider_id = $m->ai_provider_id;
            $this->model_nome = $m->nome;
            $this->model_codigo = $m->codigo;
        }
        $this->modalModelAberto = true;
    }

    public function salvarModel()
    {
        $this->validate([
            'model_ai_provider_id' => 'required',
            'model_nome' => 'required',
            'model_codigo' => 'required',
        ]);

        $data = [
            'ai_provider_id' => $this->model_ai_provider_id,
            'nome' => $this->model_nome,
            'codigo' => $this->model_codigo,
        ];

        if ($this->model_id) {
            AiModel::findOrFail($this->model_id)->update($data);
        } else {
            AiModel::create($data);
        }

        $this->modalModelAberto = false;
        $this->dispatch('sucesso', msg: 'Modelo salvo com sucesso!');
    }

    public function excluirModel($id)
    {
        AiModel::findOrFail($id)->delete();
        $this->dispatch('sucesso', msg: 'Modelo removido.');
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
            'documentosAtuais' => DocumentoExigido::with('ciclo')->orderBy('ciclo_id')->get(),
            'provedoresBd' => AiProvider::orderBy('nome')->get(),
            'modelosBd' => AiModel::with('provider')->orderBy('nome')->get(),
        ]);
    }
}