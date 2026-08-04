<?php

namespace App\Modules\Website\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Formulario;
use App\Models\RespostaFormulario;

#[Layout('components.layouts.public')]
class FormularioPublico extends Component
{
    public Formulario $formulario;
    public $camposDinamicos = [];
    public $respostas = [];
    public int $etapaAtual = 1;
    public int $totalEtapas = 1;
    public bool $finalizado = false;

    // Guardará as configurações globais (Fundo, Cor, Opacidade)
    public array $formSettings = [];

    public function mount($slug)
    {
        $this->formulario = Formulario::with('campos')->where('slug', $slug)->where('status', true)->firstOrFail();
        $this->camposDinamicos = $this->formulario->campos;
        
        $this->totalEtapas = max(1, $this->camposDinamicos->where('tipo', '!=', 'config')->max('etapa') ?? 1);

        // Carrega o Papel de Parede Global se existir
        $cfg = $this->camposDinamicos->firstWhere('name', '_form_config');
        if ($cfg && $cfg->configuracoes) {
            $this->formSettings = is_string($cfg->configuracoes) ? json_decode($cfg->configuracoes, true) : $cfg->configuracoes;
        }

        // Inicializa os campos nas respostas
        foreach ($this->camposDinamicos->where('tipo', '!=', 'config') as $campo) {
            if (!isset($this->respostas[$campo->name])) {
                if (in_array($campo->tipo, ['check', 'matriz'])) {
                    $this->respostas[$campo->name] = [];
                } else {
                    $this->respostas[$campo->name] = '';
                }
            }
        }
    }

    // Exigência do Livewire 3 para evitar o "MissingRulesException"
    public function rules()
    {
        return [];
    }

    // Motor Inteligente de Validação (Idêntico ao Inscricao.php)
    protected function regrasPorEtapa($etapa)
    {
        $regras = [];

        foreach ($this->camposDinamicos->where('etapa', $etapa)->where('tipo', '!=', 'config') as $campo) {
            
            // Verifica Condicionais: Se o campo estiver oculto, não valida!
            if (!empty($campo->depende_de) && !empty($campo->depende_valor)) {
                $valorGatilho = $this->respostas[$campo->depende_de] ?? null;
                $val = strtolower(trim((string)$valorGatilho));
                $tgt = strtolower(trim((string)$campo->depende_valor));
                $op = $campo->depende_operador ?? '=';
                $condicaoAtendida = false;

                switch($op) {
                    case '=': $condicaoAtendida = ($val === $tgt); break;
                    case '!=': $condicaoAtendida = ($val !== $tgt); break;
                    case '>': $condicaoAtendida = (is_numeric($val) && is_numeric($tgt) && $val > $tgt); break;
                    case '<': $condicaoAtendida = (is_numeric($val) && is_numeric($tgt) && $val < $tgt); break;
                    case '>=': $condicaoAtendida = (is_numeric($val) && is_numeric($tgt) && $val >= $tgt); break;
                    case '<=': $condicaoAtendida = (is_numeric($val) && is_numeric($tgt) && $val <= $tgt); break;
                    case 'in': 
                        $arrayAlvos = array_map('trim', explode(',', $tgt));
                        $condicaoAtendida = in_array($val, $arrayAlvos);
                        break;
                }

                if (!$condicaoAtendida) continue; 
            }

            $ruleStr = [];
            if ($campo->obrigatorio) $ruleStr[] = 'required';
            else $ruleStr[] = 'nullable';

            if ($campo->subtipo === 'email') $ruleStr[] = 'email';
            if ($campo->subtipo === 'number') $ruleStr[] = 'numeric';
            if ($campo->subtipo === 'date') $ruleStr[] = 'date';

            if ($campo->tamanho_min !== null) $ruleStr[] = 'min:'.$campo->tamanho_min;
            if ($campo->tamanho_max !== null) $ruleStr[] = 'max:'.$campo->tamanho_max;

            if (!empty($campo->regras_validacao)) {
                $ruleStr = array_merge($ruleStr, explode('|', $campo->regras_validacao));
            }
            
            if (!empty($ruleStr)) {
                $regras['respostas.' . $campo->name] = implode('|', $ruleStr);
            }
        }

        return $regras;
    }

    // Validação em Tempo Real
    public function updated($propertyName)
    {
        if (str_starts_with($propertyName, 'respostas.')) {
            $regras = $this->regrasPorEtapa($this->etapaAtual);
            if (array_key_exists($propertyName, $regras)) {
                $this->validateOnly($propertyName, $regras, [
                    'respostas.*.required' => 'Este campo é obrigatório.',
                    'respostas.*.email' => 'Informe um e-mail válido.'
                ]);
            }
        }
    }

    public function avancarEtapa()
    {
        $regras = $this->regrasPorEtapa($this->etapaAtual);

        // Só executa o validate() se existirem regras para a etapa
        if (!empty($regras)) {
            $this->validate($regras, [
                'respostas.*.required' => 'Este campo é obrigatório.',
                'respostas.*.email' => 'Informe um e-mail válido.',
                'respostas.*.numeric' => 'Este campo aceita apenas números.'
            ]);
        }

        if ($this->etapaAtual < $this->totalEtapas) {
            $this->etapaAtual++;
        } else {
            RespostaFormulario::create([
                'formulario_id' => $this->formulario->id,
                'user_id' => auth()->check() ? auth()->id() : null,
                'respostas' => $this->respostas,
                'etapa_parada' => $this->etapaAtual
            ]);
            $this->finalizado = true;
        }
    }

    public function render()
    {
        return view('livewire.website.formulario-publico')->title($this->formulario->titulo);
    }
}