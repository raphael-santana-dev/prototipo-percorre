<?php

namespace App\Modules\Website\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithFileUploads;
use App\Models\Formulario;
use App\Models\RespostaFormulario;

#[Layout('components.layouts.public')]
class FormularioPublico extends Component
{
    use WithFileUploads;

    public Formulario $formulario;
    public $respostaId = null;
    public $camposDinamicos = [];
    public $respostas = [];
    public array $uploads = [];
    public array $unidadesDisponiveis = [];
    public array $cursosDisponiveis = [];
    public array $turnosDisponiveis = [];

    public int $etapaAtual = 1;
    public int $totalEtapas = 1;
    public bool $finalizado = false;

    public bool $bloqueado = false;
    public string $mensagemBloqueio = '';
    public string $iconeBloqueio = 'ph-lock-key';
    public bool $exibirBotaoLogin = false;
    public bool $isPreview = false;
    public bool $isTeste = false;


    public array $formSettings = [];

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
        }])->where('slug', $slug);

        // Permite abrir se for preview, teste ou se estiver ativo
        if (!$querPreview && !$querTeste) {
            $query->where('status', true);
        }
        
        $this->formulario = $query->firstOrFail();

        // Se o formulário estiver em Rascunho, exige permissão Spatie e força modo Teste
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

        if (!$this->isPreview && !$this->isTeste) {
            $agora = now();
            
            if ($this->formulario->data_inicio && $agora->lt($this->formulario->data_inicio)) {
                $this->bloquearAcesso('Este formulário estará disponível a partir de ' . $this->formulario->data_inicio->format('d/m/Y \à\s H:i') . '.', 'ph-calendar-plus');
                return;
            }
            if ($this->formulario->data_fim && $agora->gt($this->formulario->data_fim)) {
                $this->bloquearAcesso('O período para responder a este formulário já foi encerrado.', 'ph-clock-countdown');
                return;
            }
        }

        if (!$this->formulario->acesso_livre && !$this->isPreview && !$this->isTeste) {
            $liberado = false;

            if (!auth('web')->check() && !auth('student')->check()) {
                $this->bloquearAcesso('Formulário restrito. Inicie sessão na sua conta para aceder.', 'ph-lock-key', true);
                return;
            }

            if (auth('web')->check()) {
                $user = auth('web')->user();
                if ($user->hasRole('dev')) {
                    $liberado = true;
                } else {
                    $roles = is_array($this->formulario->roles_permitidas) ? $this->formulario->roles_permitidas : [];
                    $users = is_array($this->formulario->users_permitidos) ? $this->formulario->users_permitidos : [];

                    if (!empty($roles) && $user->hasAnyRole($roles)) $liberado = true;
                    if (!empty($users) && in_array((string)$user->id, $users)) $liberado = true;
                    
                    if (empty($roles) && empty($users) && !$this->formulario->apenas_estudantes) {
                        $liberado = true; 
                    }
                }
            }

            if (auth('student')->check()) {
                if ($this->formulario->apenas_estudantes) {
                    $student = auth('student')->user();
                    $student->load('matriculas');
                    $matriculas = $student->matriculas;

                    $unidades = is_array($this->formulario->unidades_permitidas) ? $this->formulario->unidades_permitidas : [];
                    $cursos = is_array($this->formulario->cursos_permitidos) ? $this->formulario->cursos_permitidos : [];
                    $turnos = $this->formulario->turnos_permitidas ? $this->formulario->turnos_permitidas : [];

                    if (empty($unidades) && empty($cursos) && empty($turnos)) {
                        $liberado = true;
                    } else {
                        $unidadesAluno = $matriculas->pluck('unidade_id')->map(fn($v) => (string)$v)->toArray();
                        $cursosAluno = $matriculas->pluck('curso_id')->map(fn($v) => (string)$v)->toArray();
                        $turnosAluno = $matriculas->pluck('turno_id')->map(fn($v) => (string)$v)->toArray();

                        $passouUnidade = empty($unidades) || !empty(array_intersect($unidades, $unidadesAluno));
                        $passouCurso = empty($cursos) || !empty(array_intersect($cursos, $cursosAluno));
                        $passouTurno = empty($turnos) || !empty(array_intersect($turnos, $turnosAluno));

                        if ($passouUnidade && $passouCurso && $passouTurno) {
                            $liberado = true;
                        }
                    }
                }
            }

            if (!$liberado) {
                $this->bloquearAcesso('O seu nível de acesso ou vínculo académico não permite visualizar este formulário.', 'ph-hand-waving');
                return;
            }
        }

        $this->totalEtapas = max(1, $this->camposDinamicos->where('tipo', '!=', 'config')->max('etapa') ?? 1);
        $this->carregarOpcoesSistemaInicial();
        
        if ($this->formulario->exigir_email) {
            $emailAutofill = '';
            if (auth('web')->check()) $emailAutofill = auth('web')->user()->email;
            elseif (auth('student')->check()) $emailAutofill = auth('student')->user()->email;
            
            $this->respostas['_email_coletado'] = $emailAutofill;
        }
        
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

    /**
     * Restaura os dados salvos no localStorage ao reabrir o formulário
     */
    public function restaurarRascunhoLocal(array $dados)
    {
        if (request()->query('preview') || $this->bloqueado || $this->finalizado) {
            return;
        }

        if (!empty($dados['respostaId'])) {
            $respBanco = RespostaFormulario::where('id', $dados['respostaId'])
                ->where('formulario_id', $this->formulario->id)
                ->first();

            if ($respBanco) {
                $this->respostaId = $respBanco->id;
            }
        }

        if (!empty($dados['respostas']) && is_array($dados['respostas'])) {
            foreach ($dados['respostas'] as $k => $v) {
                if ($v !== '' && $v !== null) {
                    $this->respostas[$k] = $v;
                }
            }
        }

        if (!empty($dados['etapaAtual']) && is_numeric($dados['etapaAtual']) && $dados['etapaAtual'] <= $this->totalEtapas) {
            $this->etapaAtual = (int) $dados['etapaAtual'];
        }

        // Recarrega cascatas de Unidade -> Curso -> Turno se houver campos 'system'
        foreach ($this->camposDinamicos->where('tipo', 'system') as $campoSys) {
            $cfg = is_string($campoSys->configuracoes) ? json_decode($campoSys->configuracoes, true) : ($campoSys->configuracoes ?? []);
            if (filter_var($cfg['aplicar_regras'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $valId = $this->respostas[$campoSys->name] ?? null;
                if ($campoSys->subtipo === 'unidade' && $valId) {
                    $this->cursosDisponiveis = \App\Models\Curso::whereIn('status', ['Ativo', 'ativo', '1', true])
                        ->whereHas('unidades', fn($q) => $q->where('unidades.id', $valId))
                        ->pluck('nome', 'id')->toArray();
                } elseif ($campoSys->subtipo === 'curso' && $valId) {
                    $curso = \App\Models\Curso::find($valId);
                    if ($curso && method_exists($curso, 'turnosVinculados')) {
                        $this->turnosDisponiveis = $curso->turnosVinculados()->pluck('nome', 'id')->toArray();
                    }
                }
            }
        }

        $this->salvarRascunhoSilencioso();
    }

    /**
     * Registra/atualiza a resposta no banco em tempo real enquanto o usuário preenche
     */
    public function salvarRascunhoSilencioso()
    {
        if ($this->isPreview || $this->isTeste || $this->bloqueado || $this->finalizado) {
            return;
        }

        $temPreenchimento = !empty(array_filter($this->respostas, function ($v) {
            return is_array($v) ? !empty(array_filter($v)) : ($v !== '' && $v !== null);
        }));

        if (!$temPreenchimento) {
            return;
        }

        $registro = RespostaFormulario::updateOrCreate(
            ['id' => $this->respostaId],
            [
                'formulario_id' => $this->formulario->id,
                'user_id' => auth('web')->id() ?? auth('student')->id() ?? null,
                'respostas' => $this->respostas,
                'etapa_parada' => $this->etapaAtual,
            ]
        );

        $this->respostaId = $registro->id;
    }

    private function bloquearAcesso(string $mensagem, string $icone, bool $exibeBotaoLogin = false)
    {
        $this->bloqueado = true;
        $this->mensagemBloqueio = $mensagem;
        $this->iconeBloqueio = $icone;
        $this->exibirBotaoLogin = $exibeBotaoLogin;
    }

    public function carregarOpcoesSistemaInicial()
    {
        foreach ($this->camposDinamicos->where('tipo', 'system') as $campo) {
            $cfg = is_string($campo->configuracoes) ? json_decode($campo->configuracoes, true) : ($campo->configuracoes ?? []);
            $aplicarRegras = filter_var($cfg['aplicar_regras'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($campo->subtipo === 'unidade') {
                $this->unidadesDisponiveis = \App\Modules\Unidade\Domain\Models\Unidade::whereIn('status', ['Ativa', 'ativa', '1', true])->orderBy('nome')->pluck('nome', 'id')->toArray();
            } 
            elseif ($campo->subtipo === 'curso') {
                if (!$aplicarRegras) {
                    $this->cursosDisponiveis = \App\Models\Curso::whereIn('status', ['Ativo', 'ativo', '1', true])->orderBy('nome')->pluck('nome', 'id')->toArray();
                }
            } 
            elseif ($campo->subtipo === 'turno') {
                if (!$aplicarRegras) {
                    if (class_exists(\App\Modules\Turno\Domain\Models\Turno::class)) {
                        $this->turnosDisponiveis = \App\Modules\Turno\Domain\Models\Turno::whereIn('status', ['Ativo', 'ativo', '1', true])->orderBy('nome')->pluck('nome', 'id')->toArray();
                    }
                }
            }
        }
    }

    public function rules() { return []; }

    protected function regrasPorEtapa($etapa)
    {
        $regras = [];

        if ($this->formulario->exigir_email && $etapa === 1) {
            $regras['respostas._email_coletado'] = 'required|email';
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

            $ruleStr = [];
            if ($campo->obrigatorio) $ruleStr[] = 'required';
            else $ruleStr[] = 'nullable';

            $cfgVal = is_string($campo->configuracoes) ? json_decode($campo->configuracoes, true) : ($campo->configuracoes ?? []);

            if ($campo->subtipo === 'email') {
                $ruleStr[] = 'email';
                if (filter_var($cfgVal['unico'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    $ruleStr[] = \Illuminate\Validation\Rule::unique('resposta_formularios', 'respostas->' . $campo->name)
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
                if (filter_var($cfgVal['unico'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    $ruleStr[] = \Illuminate\Validation\Rule::unique('resposta_formularios', 'respostas->' . $campo->name)
                        ->where('formulario_id', $this->formulario->id)
                        ->ignore($this->respostaId);
                }
            }

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
                    'uploads.*.required' => 'O envio de arquivo é obrigatório.',
                    'uploads.*.max' => 'O arquivo excede o tamanho máximo permitido.',
                    'uploads.*.mimes' => 'Formato de arquivo não suportado.',
                    'uploads.*.*.max' => 'Um dos arquivos excede o tamanho máximo permitido.',
                    'uploads.*.*.mimes' => 'Um dos arquivos possui formato não suportado.',
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

            $this->salvarRascunhoSilencioso();

            $regras = $this->regrasPorEtapa($this->etapaAtual);
            if (array_key_exists($propertyName, $regras)) {
                $this->validateOnly($propertyName, $regras, [
                    'respostas.*.required' => 'Este campo é obrigatório.',
                    'respostas.*.email' => 'Informe um e-mail válido.'
                ]);
            }

            $campoAlterado = $this->camposDinamicos->firstWhere('name', $changedKey);

            if ($campoAlterado && $campoAlterado->tipo === 'system') {
                $cfg = is_string($campoAlterado->configuracoes) ? json_decode($campoAlterado->configuracoes, true) : ($campoAlterado->configuracoes ?? []);
                $aplicarRegras = filter_var($cfg['aplicar_regras'] ?? false, FILTER_VALIDATE_BOOLEAN);

                if ($aplicarRegras) {
                    $valorId = $this->respostas[$changedKey];

                    if ($campoAlterado->subtipo === 'unidade') {
                        $this->cursosDisponiveis = [];
                        $this->turnosDisponiveis = [];
                        if ($valorId) {
                            $this->cursosDisponiveis = \App\Models\Curso::whereIn('status', ['Ativo', 'ativo', '1', true])
                                ->whereHas('unidades', function ($q) use ($valorId) {
                                    $q->where('unidades.id', $valorId);
                                })->pluck('nome', 'id')->toArray();
                        }
                        $cursoField = $this->camposDinamicos->where('tipo', 'system')->firstWhere('subtipo', 'curso');
                        if ($cursoField) $this->respostas[$cursoField->name] = '';
                        $turnoField = $this->camposDinamicos->where('tipo', 'system')->firstWhere('subtipo', 'turno');
                        if ($turnoField) $this->respostas[$turnoField->name] = '';
                    } 
                    elseif ($campoAlterado->subtipo === 'curso') {
                        $this->turnosDisponiveis = [];
                        if ($valorId) {
                            $curso = \App\Models\Curso::find($valorId);
                            if ($curso && method_exists($curso, 'turnosVinculados')) {
                                $this->turnosDisponiveis = $curso->turnosVinculados()->pluck('nome', 'id')->toArray();
                            }
                        }
                        $turnoField = $this->camposDinamicos->where('tipo', 'system')->firstWhere('subtipo', 'turno');
                        if ($turnoField) $this->respostas[$turnoField->name] = '';
                    }
                }
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
                'respostas.*.required' => 'Este campo é obrigatório.',
                'respostas.*.email' => 'Informe um e-mail válido.',
                'respostas.*.numeric' => 'Este campo aceita apenas números.',
                'uploads.*.required' => 'O envio de arquivo é obrigatório.',
                'uploads.*.max' => 'O arquivo excede o tamanho máximo permitido.',
                'uploads.*.mimes' => 'Formato de arquivo não suportado.',
                'uploads.*.*.max' => 'Um dos arquivos excede o tamanho máximo permitido.',
                'uploads.*.*.mimes' => 'Um dos arquivos possui formato não suportado.',
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
                                $caminhos[] = $arquivo->store('formularios/uploads', 'public');
                            }
                        }
                        $this->respostas[$campoFile->name] = $caminhos;
                    } else {
                        $this->respostas[$campoFile->name] = $this->uploads[$campoFile->name]->store('formularios/uploads', 'public');
                    }

                    unset($this->uploads[$campoFile->name]);
                }
            }
        }

        if ($this->etapaAtual < $this->totalEtapas) {
            $this->etapaAtual++;
            $this->salvarRascunhoSilencioso();
        } else {
            if (!$this->isTeste) {
                $registro = RespostaFormulario::updateOrCreate(
                    ['id' => $this->respostaId],
                    [
                        'formulario_id' => $this->formulario->id,
                        'user_id' => auth('web')->id() ?? auth('student')->id() ?? null,
                        'respostas' => $this->respostas,
                        'etapa_parada' => $this->etapaAtual
                    ]
                );
                $this->respostaId = $registro->id;
            }
            $this->finalizado = true;
            $this->dispatch('limpar-storage-formulario');
        }
    }

    public function render()
    {
        return view('livewire.website.formulario-publico')->title($this->formulario->titulo);
    }
}