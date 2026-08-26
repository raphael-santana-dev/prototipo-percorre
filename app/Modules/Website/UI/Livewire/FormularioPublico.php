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
    public array $unidadesDisponiveis = [];
    public array $cursosDisponiveis = [];
    public array $turnosDisponiveis = [];

    public int $etapaAtual = 1;
    public int $totalEtapas = 1;
    public bool $finalizado = false;

    // Propriedades do Gatekeeper de Segurança
    public bool $bloqueado = false;
    public string $mensagemBloqueio = '';
    public string $iconeBloqueio = 'ph-lock-key';
    public bool $exibirBotaoLogin = false;

    // Guardará as configurações globais (Fundo, Cor, Opacidade)
    public array $formSettings = [];

    public function mount($id, $slug)
    {
        $this->formulario = Formulario::with(['campos' => function($query) {
            $query->orderBy('etapa', 'asc')->orderBy('ordem', 'asc');
        }])->where('id', $id)->where('status', true)->firstOrFail();
        
        $this->camposDinamicos = $this->formulario->campos;
        
        // Carrega o Papel de Parede Global mesmo se for bloquear o acesso (mantém a estética)
        $cfg = $this->camposDinamicos->firstWhere('name', '_form_config');
        if ($cfg && $cfg->configuracoes) {
            $this->formSettings = is_string($cfg->configuracoes) ? json_decode($cfg->configuracoes, true) : $cfg->configuracoes;
        }

        // ==========================================
        // GATEKEEPER: REGRAS DE TEMPO E ACESSO
        // ==========================================
        $agora = now();
        
        // 1. Regra de Tempo
        if ($this->formulario->data_inicio && $agora->lt($this->formulario->data_inicio)) {
            $this->bloquearAcesso('Este formulário estará disponível para respostas a partir de ' . $this->formulario->data_inicio->format('d/m/Y \à\s H:i') . '.', 'ph-calendar-plus');
            return;
        }
        if ($this->formulario->data_fim && $agora->gt($this->formulario->data_fim)) {
            $this->bloquearAcesso('O período para responder este formulário já foi encerrado.', 'ph-clock-countdown');
            return;
        }

        // 2. Regra de Privacidade (Restrição de Usuários)
        if (!$this->formulario->acesso_livre) {
            
            // Ninguém está logado
            if (!auth('web')->check() && !auth('student')->check() && !auth('company')->check()) {
                $this->bloquearAcesso('Este formulário é restrito. Você precisa fazer login para acessar.', 'ph-identification-badge', true);
                return;
            }

            // Exige apenas Alunos
            if ($this->formulario->apenas_estudantes) {
                if (!auth('student')->check()) {
                    $this->bloquearAcesso('Acesso negado. Apenas alunos do instituto podem responder a este formulário.', 'ph-student');
                    return;
                }
            } 
            // Exige Funcionários/Administração (Roles)
            else {
                $rolesPermitidas = is_string($this->formulario->roles_permitidas) ? json_decode($this->formulario->roles_permitidas, true) : ($this->formulario->roles_permitidas ?? []);
                
                if (!empty($rolesPermitidas)) {
                    if (!auth('web')->check()) {
                        $this->bloquearAcesso('Acesso negado. Formulário restrito a colaboradores internos.', 'ph-shield-warning');
                        return;
                    }

                    $user = auth('web')->user();
                    if (!$user->hasRole('dev') && !$user->hasAnyRole($rolesPermitidas)) {
                        $this->bloquearAcesso('Seu nível de acesso não possui permissão para visualizar este formulário.', 'ph-hand-waving');
                        return;
                    }
                }
            }
        }

        // ==========================================
        // SUCESSO: INICIALIZA O FORMULÁRIO NORMALMENTE
        // ==========================================
        $this->totalEtapas = max(1, $this->camposDinamicos->where('tipo', '!=', 'config')->max('etapa') ?? 1);
        $this->carregarOpcoesSistemaInicial();
        
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

        foreach ($this->camposDinamicos->where('etapa', $etapa)->where('tipo', '!=', 'config') as $campo) {
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

            $fieldName = str_replace('respostas.', '', $propertyName);
            $campoAlterado = $this->camposDinamicos->firstWhere('name', $fieldName);

            if ($campoAlterado && $campoAlterado->tipo === 'system') {
                $cfg = is_string($campoAlterado->configuracoes) ? json_decode($campoAlterado->configuracoes, true) : ($campoAlterado->configuracoes ?? []);
                $aplicarRegras = filter_var($cfg['aplicar_regras'] ?? false, FILTER_VALIDATE_BOOLEAN);

                if ($aplicarRegras) {
                    $valorId = $this->respostas[$fieldName];

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

    public function avancarEtapa()
    {
        $regras = $this->regrasPorEtapa($this->etapaAtual);

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