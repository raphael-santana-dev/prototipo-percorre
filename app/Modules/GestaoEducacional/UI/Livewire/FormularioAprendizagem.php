<?php

namespace App\Modules\GestaoEducacional\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Formulario;
use App\Modules\Student\Domain\Models\Student;
use App\Models\AlunoCicloAprendizagem;

#[Title('Avaliação de Aprendizagem')]
class FormularioAprendizagem extends Component
{
    public Formulario $formulario;
    public Student $aluno;
    public $cicloAluno;
    
    public $camposDinamicos = [];
    public $respostas = [];
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
            foreach ($this->camposDinamicos->where('tipo', '!=', 'config') as $campo) {
                $this->respostas[$campo->name] = in_array($campo->tipo, ['check', 'matriz']) ? [] : '';
            }
        }
    }
    protected function regrasPorEtapa($etapa)
    {
        $regras = [];
        foreach ($this->camposDinamicos->where('etapa', $etapa)->where('tipo', '!=', 'config') as $campo) {
            $ruleStr = [];
            if ($campo->obrigatorio) $ruleStr[] = 'required';
            else $ruleStr[] = 'nullable';
            
            if ($campo->subtipo === 'email') $ruleStr[] = 'email';
            if ($campo->subtipo === 'number') $ruleStr[] = 'numeric';
            if ($campo->tamanho_min !== null) $ruleStr[] = 'min:'.$campo->tamanho_min;
            if ($campo->tamanho_max !== null) $ruleStr[] = 'max:'.$campo->tamanho_max;

            if (!empty($ruleStr)) $regras['respostas.' . $campo->name] = implode('|', $ruleStr);
        }
        return $regras;
    }

    public function salvarEAvancar()
    {
        if (!$this->podeResponder) return;

        $regras = $this->regrasPorEtapa($this->etapaAtual);
        if (!empty($regras)) {
            $this->validate($regras, ['respostas.*.required' => 'Campo obrigatório.']);
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