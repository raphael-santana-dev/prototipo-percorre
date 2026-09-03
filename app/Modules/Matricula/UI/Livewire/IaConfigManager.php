<?php

namespace App\Modules\Matricula\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Ciclo;
use App\Modules\Matricula\Domain\Models\ConfiguracaoIa;
use App\Modules\Matricula\Domain\Models\DocumentoExigido;
use Illuminate\Support\Facades\Crypt;

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

    // Constante para mascarar a chave no front-end
    private const MASKED_KEY = '********_CHAVE_SALVA_********';

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('matricula.configurar'), 403, 'Acesso restrito.');

        $config = ConfiguracaoIa::first();
        if ($config) {
            $this->provedor = $config->provedor;
            $this->prompt_documentos = $config->prompt_documentos;
            $this->is_ativa = $config->is_ativa;
            
            // Mascara a chave: se existir no banco, o Livewire exibirá apenas asteriscos no front-end
            $this->api_key = empty($config->api_key) ? '' : self::MASKED_KEY;
        }
    }

    public function salvarConfiguracaoIa()
    {
        $this->validate([
            'provedor' => 'required',
            'api_key' => 'required',
            'prompt_documentos' => 'required'
        ]);

        $dadosParaSalvar = [
            'provedor' => $this->provedor,
            'prompt_documentos' => $this->prompt_documentos,
            'is_ativa' => $this->is_ativa
        ];

        // Só atualiza a chave no banco se o usuário digitou uma nova (diferente da máscara)
        if ($this->api_key !== self::MASKED_KEY && !empty($this->api_key)) {
            // Criptografa a chave antes de salvar no banco de dados
            $dadosParaSalvar['api_key'] = Crypt::encryptString($this->api_key);
        }

        ConfiguracaoIa::updateOrCreate(['id' => 1], $dadosParaSalvar);

        // Se o usuário digitou uma chave nova, voltamos a exibir a máscara na tela após salvar
        if ($this->api_key !== self::MASKED_KEY) {
            $this->api_key = self::MASKED_KEY;
        }

        $this->dispatch('sucesso', msg: 'Motor de Inteligência Artificial configurado com segurança!');
    }

    // ... [Restante dos métodos adicionarDocumento, excluirDocumento e render permanecem iguais]

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