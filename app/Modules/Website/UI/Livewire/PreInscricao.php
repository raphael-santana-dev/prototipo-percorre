<?php

namespace App\Modules\Website\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithFileUploads;
use App\Models\Formulario;
use App\Models\RespostaFormulario;
use App\Modules\Unidade\Domain\Models\Unidade;
use App\Models\Curso;
use App\Modules\Turno\Domain\Models\Turno;

#[Layout('components.layouts.public')]
class PreInscricao extends Component
{
    use WithFileUploads;

    public Formulario $formulario;
    public $camposDinamicos = [];
    public array $respostas = [];
    public array $uploads = [];
    
    public int $etapaAtual = 1;
    public int $totalEtapas = 1;
    public bool $finalizado = false;
    public array $formSettings = [];

    // VARIÁVEIS PARA CAPTAÇÃO DE INTERESSE
    public bool $deseja_informar = false;
    public $unidade_interesse = null;
    public $curso_interesse = null;
    public $turno_interesse = null;

    public function mount($slug)
    {
        $this->formulario = Formulario::with(['campos' => function($query) {
            $query->orderBy('etapa', 'asc')->orderBy('ordem', 'asc');
        }])->where('slug', $slug)
           ->where('tipo', 'pre_inscricao')
           ->where('status', true)
           ->firstOrFail();
        
        $this->camposDinamicos = $this->formulario->campos;
        
        $cfg = $this->camposDinamicos->firstWhere('name', '_form_config');
        if ($cfg && $cfg->configuracoes) {
            $this->formSettings = is_string($cfg->configuracoes) ? json_decode($cfg->configuracoes, true) : $cfg->configuracoes;
        }

        $this->totalEtapas = max(1, $this->camposDinamicos->where('tipo', '!=', 'config')->max('etapa') ?? 1);
        
        foreach ($this->camposDinamicos->where('tipo', '!=', 'config') as $campo) {
            if (!isset($this->respostas[$campo->name])) {
                $cfgCampo = is_string($campo->configuracoes) ? json_decode($campo->configuracoes, true) : ($campo->configuracoes ?? []);
                if (in_array($campo->tipo, ['check', 'matriz']) || ($campo->tipo === 'file' && !empty($cfgCampo['aceita_multiplos']))) {
                    $this->respostas[$campo->name] = [];
                } else {
                    $this->respostas[$campo->name] = '';
                }
            }
        }
    }

    protected function regrasPorEtapa($etapa)
    {
        $regras = [];

        // Validação dos campos opcionais de captação de interesse na Etapa 1
        if ($etapa === 1 && $this->deseja_informar) {
            $regras['unidade_interesse'] = 'required';
            $regras['curso_interesse'] = 'required';
            $regras['turno_interesse'] = 'required';
        }

        foreach ($this->camposDinamicos->where('etapa', $etapa)->where('tipo', '!=', 'config') as $campo) {
            // Avaliação de campos condicionais
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

            // Regras dedicadas para upload de ficheiros
            if ($campo->tipo === 'file') {
                $cfg = is_string($campo->configuracoes) ? json_decode($campo->configuracoes, true) : ($campo->configuracoes ?? []);
                $aceitaMultiplos = !empty($cfg['aceita_multiplos']);
                $maxKb = ((int)($cfg['max_size_mb'] ?? 10)) * 1024;
                $exts = !empty($cfg['extensoes_permitidas']) ? str_replace(' ', '', $cfg['extensoes_permitidas']) : null;

                $baseRule = ['file', "max:{$maxKb}"];
                if ($exts) {
                    $baseRule[] = "mimes:{$exts}";
                }

                $jaPossuiArquivo = !empty($this->respostas[$campo->name]);

                if ($aceitaMultiplos) {
                    if ($campo->obrigatorio && !$jaPossuiArquivo && empty($this->uploads[$campo->name])) {
                        $regras['uploads.' . $campo->name] = 'required|array|min:1';
                    } else {
                        $regras['uploads.' . $campo->name] = 'nullable|array';
                    }
                    $regras['uploads.' . $campo->name . '.*'] = implode('|', $baseRule);
                } else {
                    if ($campo->obrigatorio && !$jaPossuiArquivo && empty($this->uploads[$campo->name])) {
                        $baseRule[] = 'required';
                    } else {
                        $baseRule[] = 'nullable';
                    }
                    $regras['uploads.' . $campo->name] = implode('|', $baseRule);
                }
                continue;
            }

            // Regras para campos comuns de texto, data, número, etc.
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

    public function updated($propertyName)
    {
        if (str_starts_with($propertyName, 'uploads.')) {
            $regras = $this->regrasPorEtapa($this->etapaAtual);
            if (array_key_exists($propertyName, $regras) || array_key_exists($propertyName . '.*', $regras)) {
                $this->validateOnly($propertyName, $regras, [
                    'uploads.*.required' => 'O envio do ficheiro é obrigatório.',
                    'uploads.*.max' => 'O ficheiro excede o tamanho máximo permitido.',
                    'uploads.*.mimes' => 'Formato de ficheiro não suportado.',
                    'uploads.*.*.max' => 'Um dos ficheiros excede o tamanho máximo permitido.',
                    'uploads.*.*.mimes' => 'Um dos ficheiros possui formato não suportado.',
                ]);
            }
            return;
        }

        if (str_starts_with($propertyName, 'respostas.')) {
            $regras = $this->regrasPorEtapa($this->etapaAtual);
            if (array_key_exists($propertyName, $regras)) {
                $this->validateOnly($propertyName, $regras, [
                    'respostas.*.required' => 'Este campo é obrigatório.',
                    'respostas.*.email' => 'Indique um e-mail válido.'
                ]);
            }
        }
    }

    public function removerArquivo(string $campoName, $index = null)
    {
        if (isset($this->respostas[$campoName])) {
            if ($index !== null && is_array($this->respostas[$campoName])) {
                unset($this->respostas[$campoName][$index]);
                $this->respostas[$campoName] = array_values($this->respostas[$campoName]);
            } else {
                $this->respostas[$campoName] = '';
            }
        }

        if (isset($this->uploads[$campoName])) {
            if ($index !== null && is_array($this->uploads[$campoName])) {
                unset($this->uploads[$campoName][$index]);
                $this->uploads[$campoName] = array_values($this->uploads[$campoName]);
            } else {
                unset($this->uploads[$campoName]);
            }
        }
    }

    public function avancarEtapa()
    {
        $regras = $this->regrasPorEtapa($this->etapaAtual);

        if (!empty($regras)) {
            $this->validate($regras, [
                'unidade_interesse.required' => 'A unidade de interesse é obrigatória.',
                'curso_interesse.required' => 'O curso de interesse é obrigatório.',
                'turno_interesse.required' => 'O turno de interesse é obrigatório.',
                'respostas.*.required' => 'Este campo é obrigatório.',
                'respostas.*.email' => 'Indique um e-mail válido.',
                'respostas.*.numeric' => 'Este campo aceita apenas números.',
                'uploads.*.required' => 'O envio do ficheiro é obrigatório.',
                'uploads.*.max' => 'O ficheiro excede o tamanho máximo permitido.',
                'uploads.*.mimes' => 'Formato de ficheiro não suportado.',
                'uploads.*.*.max' => 'Um dos ficheiros excede o tamanho máximo permitido.',
                'uploads.*.*.mimes' => 'Um dos ficheiros possui formato não suportado.',
            ]);
        }

        // Processa o armazenamento dos uploads temporários da etapa em curso
        foreach ($this->camposDinamicos->where('etapa', $this->etapaAtual)->where('tipo', 'file') as $campoFile) {
            if (isset($this->uploads[$campoFile->name]) && !empty($this->uploads[$campoFile->name])) {
                $cfg = is_string($campoFile->configuracoes) ? json_decode($campoFile->configuracoes, true) : ($campoFile->configuracoes ?? []);
                $aceitaMultiplos = !empty($cfg['aceita_multiplos']);

                if ($aceitaMultiplos && is_array($this->uploads[$campoFile->name])) {
                    $caminhos = is_array($this->respostas[$campoFile->name] ?? null) ? $this->respostas[$campoFile->name] : [];
                    foreach ($this->uploads[$campoFile->name] as $arquivo) {
                        if ($arquivo) {
                            $caminhos[] = $arquivo->store('pre_inscricoes/uploads', 'public');
                        }
                    }
                    $this->respostas[$campoFile->name] = $caminhos;
                } else {
                    $this->respostas[$campoFile->name] = $this->uploads[$campoFile->name]->store('pre_inscricoes/uploads', 'public');
                }

                unset($this->uploads[$campoFile->name]);
            }
        }

        if ($this->etapaAtual < $this->totalEtapas) {
            $this->etapaAtual++;
        } else {
            if ($this->deseja_informar) {
                $this->respostas['_interesse_captacao'] = [
                    'unidade_id' => $this->unidade_interesse,
                    'curso_id' => $this->curso_interesse,
                    'turno_id' => $this->turno_interesse,
                ];
            }

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
        $unidadesInteresseDb = Unidade::whereIn('status', ['Ativa', '1', true])->get();
        $cursosInteresseDb = Curso::whereIn('status', ['Ativo', 'ativo', '1', 1, true])->get();
        $turnosInteresseDb = Turno::all(); 

        return view('livewire.website.pre-inscricao', [
            'unidadesInteresseDb' => $unidadesInteresseDb,
            'cursosInteresseDb' => $cursosInteresseDb,
            'turnosInteresseDb' => $turnosInteresseDb,
        ])->title($this->formulario->titulo);
    }
}