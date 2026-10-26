<?php

namespace App\Modules\Website\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;
use App\Models\Formulario;
use App\Models\RespostaFormulario;
use App\Modules\Unidade\Domain\Models\Unidade;
use App\Models\Curso;
use App\Modules\Turno\Domain\Models\Turno;

#[Layout('components.layouts.public')]
class PreInscricao extends Component
{
    use WithFileUploads;
    use \App\Traits\WithFichaPdfDownload;

    public Formulario $formulario;
    public $respostaId = null;
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
    public bool $isPreview = false;
    public bool $isTeste = false;


    public function mount($slug)
    {
        $querPreview = filter_var(request()->query('preview', false), FILTER_VALIDATE_BOOLEAN);
        $querTeste   = request()->query('modo') === 'teste';

        $temPermissaoTeste = auth('web')->check() && (
            auth('web')->user()->hasAnyRole(['dev', 'admin']) || 
            auth('web')->user()->can('formulario.testar') || 
            auth('web')->user()->can('formulario.criar') ||
            auth('web')->user()->can('inscricao.criar')
        );

        if (($querPreview || $querTeste) && !$temPermissaoTeste) {
            abort(403, 'Acesso restrito: Você não possui permissão para testar formulários em modo Sandbox.');
        }

        $query = Formulario::with(['campos' => function($query) {
            $query->orderBy('etapa', 'asc')->orderBy('ordem', 'asc');
        }])->where('slug', $slug)
           ->where('tipo', 'pre_inscricao');

        if (!$querPreview && !$querTeste) {
            $query->where('status', true);
        }

        $this->formulario = $query->firstOrFail();

        if ($this->formulario->is_rascunho) {
            abort_unless($temPermissaoTeste, 403, 'Este formulário encontra-se em Rascunho e está restrito à equipe autorizada.');
            if (!$querPreview) {
                $querTeste = true;
            }
        }

        $this->isPreview = $querPreview;
        $this->isTeste   = $querTeste;
        
        $this->camposDinamicos = $this->formulario->campos;
        
        $cfg = $this->camposDinamicos->firstWhere('name', '_form_config');
        if ($cfg && $cfg->configuracoes) {
            $this->formSettings = is_string($cfg->configuracoes) ? json_decode($cfg->configuracoes, true) : $cfg->configuracoes;
        }

        $this->totalEtapas = max(1, $this->camposDinamicos->where('tipo', '!=', 'config')->max('etapa') ?? 1);
        
        foreach ($this->camposDinamicos->whereNotIn('tipo', ['config', 'html', 'divider', 'media', 'spacer', 'link']) as $campo) {
            if (!isset($this->respostas[$campo->name])) {
                $cfgCampo = is_string($campo->configuracoes) ? json_decode($campo->configuracoes, true) : ($campo->configuracoes ?? []);
                if (in_array($campo->tipo, ['check', 'matriz', 'social']) || ($campo->tipo === 'file' && !empty($cfgCampo['aceita_multiplos']))) {
                    $this->respostas[$campo->name] = [];
                } else {
                    $this->respostas[$campo->name] = '';
                }
            }
        }
    }

    public function salvarRascunhoSilencioso()
    {
        if ($this->isPreview || $this->isTeste || $this->finalizado) {
            return;
        }

        $temPreenchimento = !empty(array_filter($this->respostas, function ($v) {
            return is_array($v) ? !empty(array_filter($v)) : ($v !== '' && $v !== null);
        }));

        if (!$temPreenchimento && !$this->deseja_informar) {
            return;
        }

        $respostasPayload = $this->respostas;
        if ($this->deseja_informar) {
            $respostasPayload['_interesse_captacao'] = [
                'unidade_id' => $this->unidade_interesse,
                'curso_id'   => $this->curso_interesse,
                'turno_id'   => $this->turno_interesse,
            ];
        }

        $registro = RespostaFormulario::updateOrCreate(
            ['id' => $this->respostaId],
            [
                'formulario_id' => $this->formulario->id,
                'user_id'       => auth()->check() ? auth()->id() : null,
                'respostas'     => $respostasPayload,
                'etapa_parada'  => $this->etapaAtual,
            ]
        );

        $this->respostaId = $registro->id;
    }

    protected function regrasPorEtapa($etapa)
    {
        $regras = [];

        if ($etapa === 1 && $this->deseja_informar) {
            $regras['unidade_interesse'] = 'required';
            $regras['curso_interesse']   = 'required';
            $regras['turno_interesse']   = 'required';
        }

        foreach ($this->camposDinamicos->where('etapa', $etapa)->whereNotIn('tipo', ['config', 'html', 'divider', 'media', 'spacer', 'link']) as $campo) {
            
            if (!empty($campo->depende_de) && !empty($campo->depende_valor)) {
                $valorGatilho = $this->respostas[$campo->depende_de] ?? null;
                $op = $campo->depende_operador ?? '=';
                $tgt = strtolower(trim((string)$campo->depende_valor));
                $condicaoAtendida = false;

                if (is_array($valorGatilho)) {
                    $arrayValores = array_map('strtolower', array_map('trim', $valorGatilho));
                    $arrayAlvos = array_map('trim', explode(',', $tgt));
                    
                    switch($op) {
                        case '=': $condicaoAtendida = in_array($tgt, $arrayValores); break;
                        case '!=': $condicaoAtendida = !in_array($tgt, $arrayValores); break;
                        case 'in': 
                            $condicaoAtendida = !empty(array_intersect($arrayValores, $arrayAlvos)); 
                            break;
                    }
                } else {
                    $val = strtolower(trim((string)$valorGatilho));
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
                }
                
                if (!$condicaoAtendida) continue; 
            }

            if ($campo->tipo === 'signature') {
                $regras['respostas.' . $campo->name] = $campo->obrigatorio
                    ? 'required|string|starts_with:data:image/'
                    : 'nullable|string';
                continue;
            }

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
                    $regras['uploads.' . $campo->name . '.*'] = $baseRule;
                } else {
                    if ($campo->obrigatorio && !$jaPossuiArquivo && empty($this->uploads[$campo->name])) {
                        $baseRule[] = 'required';
                    } else {
                        $baseRule[] = 'nullable';
                    }
                    $regras['uploads.' . $campo->name] = $baseRule;
                }
                continue;
            }

            // --- REGRA ARRAY ---
            $ruleStr = [];
            if ($campo->obrigatorio) {
                $ruleStr[] = 'required';
            } else {
                $ruleStr[] = 'nullable';
            }

            $cfgVal = is_string($campo->configuracoes) ? json_decode($campo->configuracoes, true) : ($campo->configuracoes ?? []);

            if ($campo->subtipo === 'email') {
                $ruleStr[] = 'email';
                if (!$this->isTeste && filter_var($cfgVal['unico'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    $ruleStr[] = Rule::unique('resposta_formularios', 'respostas->' . $campo->name)
                        ->where('formulario_id', $this->formulario->id)
                        ->ignore($this->respostaId);
                }
            }

            if ($campo->subtipo === 'cpf') {
                if (filter_var($cfgVal['validar_cpf'] ?? true, FILTER_VALIDATE_BOOLEAN)) {
                    $ruleStr[] = function ($attribute, $value, $fail) {
                        if (empty($value)) return;
                        $c = preg_replace('/\D/', '', $value);
                        if (strlen($c) != 11 || preg_match("/^{$c[0]}{11}$/", $c)) return $fail('O CPF informado é inválido.');
                        for ($s = 10, $n = 0, $i = 0; $s >= 2; $n += $c[$i++] * $s--);
                        if ($c[9] != ((($n %= 11) < 2) ? 0 : 11 - $n)) return $fail('O CPF informado é inválido.');
                        for ($s = 11, $n = 0, $i = 0; $s >= 2; $n += $c[$i++] * $s--);
                        if ($c[10] != ((($n %= 11) < 2) ? 0 : 11 - $n)) return $fail('O CPF informado é inválido.');
                    };
                }
                if (!$this->isTeste && filter_var($cfgVal['unico'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    $ruleStr[] = Rule::unique('resposta_formularios', 'respostas->' . $campo->name)
                        ->where('formulario_id', $this->formulario->id)
                        ->ignore($this->respostaId);
                }
            }

            if ($campo->subtipo === 'number') $ruleStr[] = 'numeric';
            if ($campo->subtipo === 'date') $ruleStr[] = 'date';
            if ($campo->tamanho_min !== null) $ruleStr[] = 'min:'.$campo->tamanho_min;
            if ($campo->tamanho_max !== null) $ruleStr[] = 'max:'.$campo->tamanho_max;

            if (!empty($campo->regras_validacao)) {
                $regrasPersonalizadas = explode('|', $campo->regras_validacao);
                $ruleStr = array_merge($ruleStr, $regrasPersonalizadas);
            }
            if (!empty($ruleStr)) {
                $regras['respostas.' . $campo->name] = $ruleStr;
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
                    'uploads.*.required' => 'O envio do arquivo é obrigatório.',
                    'uploads.*.max'      => 'O arquivo excede o tamanho máximo permitido.',
                    'uploads.*.mimes'    => 'Formato de arquivo não suportado.',
                    'uploads.*.*.max'    => 'Um dos arquivos excede o tamanho.',
                    'uploads.*.*.mimes'  => 'Um dos formatos não é suportado.',
                ]);
            }
            return;
        }

        if (str_starts_with($propertyName, 'respostas.')) {
            $changedKey = str_replace('respostas.', '', $propertyName);
            foreach ($this->camposDinamicos->where('tipo', 'system') as $cSys) {
                $cfgSys = is_string($cSys->configuracoes) ? json_decode($cSys->configuracoes, true) : ($cSys->configuracoes ?? []);
                if (($cfgSys['combo_parent_unidade'] ?? null) === $changedKey) {
                    $this->respostas[$cSys->name] = '';
                }
                if (($cfgSys['combo_parent_curso'] ?? null) === $changedKey && $cSys->subtipo === 'turno') {
                    $this->respostas[$cSys->name] = '';
                }
            }
        }

        $this->salvarRascunhoSilencioso();

        if (str_starts_with($propertyName, 'respostas.')) {
            $regras = $this->regrasPorEtapa($this->etapaAtual);
            if (array_key_exists($propertyName, $regras)) {
                $this->validateOnly($propertyName, $regras, [
                    'respostas.*.required' => 'Este campo é obrigatório.',
                    'respostas.*.email'    => 'Indique um e-mail válido.'
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

        $this->salvarRascunhoSilencioso();
    }

    public function avancarEtapa()
    {
        if ($this->isPreview) {
            if ($this->etapaAtual < $this->totalEtapas) {
                $this->etapaAtual++;
            }
            return;
        }

        $regras = $this->regrasPorEtapa($this->etapaAtual);

        if (!empty($regras)) {
            $this->validate($regras, [
                'unidade_interesse.required' => 'A unidade de interesse é obrigatória.',
                'curso_interesse.required'   => 'O curso de interesse é obrigatório.',
                'turno_interesse.required'   => 'O turno de interesse é obrigatório.',
                'respostas.*.required'       => 'Este campo é obrigatório.',
                'respostas.*.email'          => 'Indique um e-mail válido.',
                'respostas.*.numeric'        => 'Este campo aceita apenas números.',
                'uploads.*.required'         => 'O envio do arquivo é obrigatório.',
                'uploads.*.max'              => 'O arquivo excede o tamanho máximo.',
                'uploads.*.mimes'            => 'Formato de arquivo não suportado.',
                'uploads.*.*.max'            => 'Um arquivo excede o tamanho.',
                'uploads.*.*.mimes'          => 'Um formato de arquivo não é suportado.',
            ]);
        }

        if (!$this->isTeste) {
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
        }

        if ($this->etapaAtual < $this->totalEtapas) {
            $this->etapaAtual++;
            $this->salvarRascunhoSilencioso();
        } else {
            if ($this->deseja_informar) {
                $this->respostas['_interesse_captacao'] = [
                    'unidade_id' => $this->unidade_interesse,
                    'curso_id'   => $this->curso_interesse,
                    'turno_id'   => $this->turno_interesse,
                ];
            }

            if (!$this->isTeste) {
                $registro = RespostaFormulario::updateOrCreate(
                    ['id' => $this->respostaId],
                    [
                        'formulario_id' => $this->formulario->id,
                        'user_id'       => auth()->check() ? auth()->id() : null,
                        'respostas'     => $this->respostas,
                        'etapa_parada'  => $this->etapaAtual
                    ]
                );
                $this->respostaId = $registro->id;
            }
            $this->finalizado = true;
            $this->dispatch('limpar-storage-pre-inscricao');
        }
    }

    public function render()
    {
        $unidadesInteresseDb = Unidade::whereIn('status', ['Ativa', '1', true])->get();
        $cursosInteresseDb = Curso::whereIn('status', ['Ativo', 'ativo', '1', 1, true])->get();
        $turnosInteresseDb = Turno::all(); 

        return view('livewire.website.pre-inscricao', [
            'unidadesInteresseDb' => $unidadesInteresseDb,
            'cursosInteresseDb'   => $cursosInteresseDb,
            'turnosInteresseDb'   => $turnosInteresseDb,
        ])->title($this->formulario->titulo);
    }
}