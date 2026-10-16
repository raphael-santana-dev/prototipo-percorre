<?php

namespace App\Modules\GestaoEducacional\UI\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Formulario;
use App\Modules\Student\Domain\Models\Student;
use App\Models\AlunoCicloAprendizagem;

#[Title('Avaliação de Aprendizagem')]
class FormularioAprendizagem extends Component
{
    use WithFileUploads;

    public Formulario $formulario;
    public Student $aluno;
    public $cicloAluno;
    
    public $camposDinamicos = [];
    public $respostas = [];
    public array $uploads = [];
    public $etapaAtual = 1;
    public $totalEtapas = 1;
    
    public bool $podeResponder = false;
    public string $mensagemBloqueio = '';

    public array $formSettings = [];

    public function mount($slug, $aluno_id)
    {
        $this->formulario = Formulario::with('faseAprendizagem.ciclo')
            ->where('slug', $slug)
            ->where('tipo', 'aprendizagem')
            ->firstOrFail();
            
        $this->aluno = Student::findOrFail($aluno_id);
        
        $tipoUsuarioLogado = '';
        
        if (auth('student')->check()) {
            if (auth('student')->id() !== $this->aluno->id) abort(403, 'Acesso negado.');
            $tipoUsuarioLogado = 'student';
            
        } elseif (auth('company')->check()) {
            $user = auth('company')->user();
            
            if ($user->empresa_id !== $this->aluno->empresa_id) {
                abort(403, 'Este aprendiz não pertence à sua organização.');
            }
            $tipoUsuarioLogado = 'company';
            
        } elseif (auth('web')->check()) {
            $tipoUsuarioLogado = 'teacher'; 
        } else {
            abort(403, 'Você precisa estar logado.');
        }

        $this->cicloAluno = AlunoCicloAprendizagem::where('student_id', $this->aluno->id)
            ->where('ciclo_aprendizagem_id', $this->formulario->faseAprendizagem->ciclo_aprendizagem_id)
            ->first();

        if (!$this->cicloAluno) {
            $this->mensagemBloqueio = 'O aluno não está vinculado a este ciclo de aprendizagem.';
            return;
        }

        $faseDoFormulario = $this->formulario->faseAprendizagem;
        
        if ($this->cicloAluno->fase_atual_id === $faseDoFormulario->id) {
            $permitidos = $faseDoFormulario->respondedores_permitidos ?? [];
            
            if (in_array($tipoUsuarioLogado, $permitidos)) {
                $this->podeResponder = true;
            } else {
                $this->mensagemBloqueio = 'Esta fase exige preenchimento de outro responsável (Ex: Aguardando o gestor da empresa).';
            }
        } else {
            $this->mensagemBloqueio = 'O aluno está em outra fase deste ciclo. Apenas leitura permitida.';
        }

        $this->camposDinamicos = $this->formulario->campos;
        
        $cfg = $this->camposDinamicos->firstWhere('name', '_form_config');
        if ($cfg && $cfg->configuracoes) {
            $this->formSettings = is_string($cfg->configuracoes) ? json_decode($cfg->configuracoes, true) : $cfg->configuracoes;
        }

        $this->totalEtapas = max(1, $this->camposDinamicos->where('tipo', '!=', 'config')->max('etapa') ?? 1);
        
        $respostaSalva = \App\Models\RespostaFormulario::where('formulario_id', $this->formulario->id)->where('user_id', $this->aluno->id)->first();
        if ($respostaSalva && is_array($respostaSalva->respostas)) {
            $this->respostas = $respostaSalva->respostas;
        } else {
            foreach ($this->camposDinamicos->whereNotIn('tipo', ['config', 'html', 'divider', 'media', 'spacer', 'link']) as $campo) {
                $cfgCampo = is_string($campo->configuracoes) ? json_decode($campo->configuracoes, true) : ($campo->configuracoes ?? []);
                $this->respostas[$campo->name] = (in_array($campo->tipo, ['check', 'matriz', 'social']) || ($campo->tipo === 'file' && !empty($cfgCampo['aceita_multiplos']))) ? [] : '';
            }
        }
    }

    protected function regrasPorEtapa($etapa)
    {
        $regras = [];
        foreach ($this->camposDinamicos->where('etapa', $etapa)->whereNotIn('tipo', ['config', 'html', 'divider', 'media', 'spacer', 'link']) as $campo) {
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
            
            if ($campo->subtipo === 'email') $ruleStr[] = 'email';
            if ($campo->subtipo === 'number') $ruleStr[] = 'numeric';
            if ($campo->subtipo === 'date') $ruleStr[] = 'date';
            if ($campo->tamanho_min !== null) $ruleStr[] = 'min:'.$campo->tamanho_min;
            if ($campo->tamanho_max !== null) $ruleStr[] = 'max:'.$campo->tamanho_max;

            if (!empty($ruleStr)) $regras['respostas.' . $campo->name] = implode('|', $ruleStr);
        }
        return $regras;
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

    public function salvarEAvancar()
    {
        if (!$this->podeResponder) return;

        $regras = $this->regrasPorEtapa($this->etapaAtual);
        if (!empty($regras)) {
            $this->validate($regras, [
                'respostas.*.required' => 'Campo obrigatório.',
                'uploads.*.required' => 'O envio de ficheiro é obrigatório.',
            ]);
        }

        foreach ($this->camposDinamicos->where('etapa', $this->etapaAtual)->where('tipo', 'file') as $campoFile) {
            if (isset($this->uploads[$campoFile->name]) && !empty($this->uploads[$campoFile->name])) {
                $cfg = is_string($campoFile->configuracoes) ? json_decode($campoFile->configuracoes, true) : ($campoFile->configuracoes ?? []);
                $aceitaMultiplos = !empty($cfg['aceita_multiplos']);

                if ($aceitaMultiplos && is_array($this->uploads[$campoFile->name])) {
                    $caminhos = is_array($this->respostas[$campoFile->name] ?? null) ? $this->respostas[$campoFile->name] : [];
                    foreach ($this->uploads[$campoFile->name] as $arquivo) {
                        if ($arquivo) {
                            $caminhos[] = $arquivo->store('aprendizagem/uploads', 'public');
                        }
                    }
                    $this->respostas[$campoFile->name] = $caminhos;
                } else {
                    $this->respostas[$campoFile->name] = $this->uploads[$campoFile->name]->store('aprendizagem/uploads', 'public');
                }

                unset($this->uploads[$campoFile->name]);
            }
        }

        if ($this->etapaAtual < $this->totalEtapas) {
            $this->etapaAtual++;
            return;
        }

        \App\Models\RespostaFormulario::updateOrCreate(
            ['formulario_id' => $this->formulario->id, 'user_id' => $this->aluno->id],
            ['respostas' => $this->respostas, 'etapa_parada' => $this->etapaAtual]
        );

        $this->cicloAluno->update(['data_resposta' => now(), 'status' => '3']);
        $proximaFase = \App\Models\CicloAprendizagemFase::where('ciclo_aprendizagem_id', $this->cicloAluno->ciclo_aprendizagem_id)
            ->where('ordem', '>', $this->cicloAluno->faseAtual->ordem)->orderBy('ordem', 'asc')->first();

        if ($proximaFase) {
            $this->cicloAluno->update([
                'fase_atual_id' => $proximaFase->id, 'status' => '2', 
                'data_resposta' => null, 'data_envio' => now(),
                'data_prazo' => now()->addDays($this->cicloAluno->ciclo->prazo_dias ?? 10)
            ]);
            $msg = "Formulário salvo! O aluno avançou para a fase: {$proximaFase->nome}.";
        } else {
            $msg = "Formulário salvo! Todas as fases de avaliação foram concluídas.";
        }

        $this->podeResponder = false; 
        $this->mensagemBloqueio = 'Formulário respondido e registrado com sucesso.';
        $this->dispatch('sucesso', msg: $msg);
    }

    public function updatingLayout()
    {
        return '';
    }

    public function render()
    {
        $layout = 'components.layouts.app';
        
        if (auth('student')->check()) {
            $layout = 'components.layouts.student-app';
        } elseif (auth('company')->check()) {
            $layout = 'components.layouts.company';
        }

        return view('livewire.gestao-educacional.formulario-aprendizagem')->layout($layout);
    }
}