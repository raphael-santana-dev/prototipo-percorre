<?php

namespace App\Modules\GestaoEducacional\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Formulario;
use App\Modules\Student\Domain\Models\Student;
use App\Models\AlunoCicloAprendizagem;

#[Layout('components.layouts.app')]
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

    public function mount($slug, $aluno_id)
    {
        // 1. Carrega o Formulário e o Aluno
        $this->formulario = Formulario::with('faseAprendizagem.ciclo')
            ->where('slug', $slug)
            ->where('tipo', 'aprendizagem')
            ->firstOrFail();
            
        $this->aluno = Student::findOrFail($aluno_id);
        
        // 2. IDENTIFICAÇÃO E SEGURANÇA (Quem está acessando?)
        $tipoUsuarioLogado = '';
        
        if (auth('student')->check()) {
            if (auth('student')->id() !== $this->aluno->id) abort(403, 'Acesso negado.');
            $tipoUsuarioLogado = 'student';
            
        } elseif (auth('web')->check()) {
            $user = auth('web')->user();
            
            // Verifica se é um CompanyUser e se a empresa bate com a do aluno
            if ($user->tipo_acesso === 'company' || isset($user->empresa_id)) {
                if ($user->empresa_id !== $this->aluno->empresa_id) {
                    abort(403, 'Este aprendiz não pertence à sua organização.');
                }
                $tipoUsuarioLogado = 'company';
            } else {
                // Se for professor ou admin
                $tipoUsuarioLogado = 'teacher'; 
            }
        } else {
            abort(403, 'Você precisa estar logado.');
        }

        // 3. REGRA DO WORKFLOW (A Fase Atual)
        $this->cicloAluno = AlunoCicloAprendizagem::where('student_id', $this->aluno->id)
            ->where('ciclo_aprendizagem_id', $this->formulario->faseAprendizagem->ciclo_aprendizagem_id)
            ->first();

        if (!$this->cicloAluno) {
            $this->mensagemBloqueio = 'O aluno não está vinculado a este ciclo de aprendizagem.';
            return;
        }

        // 4. VALIDAÇÃO DE PERMISSÃO DA FASE
        $faseDoFormulario = $this->formulario->faseAprendizagem;
        
        if ($this->cicloAluno->fase_atual_id === $faseDoFormulario->id) {
            $permitidos = $faseDoFormulario->respondedores_permitidos ?? [];
            
            if (in_array($tipoUsuarioLogado, $permitidos)) {
                $this->podeResponder = true; // Liberado para escrever!
            } else {
                $this->mensagemBloqueio = 'Esta fase exige preenchimento de outro responsável (Ex: Aguardando o gestor da empresa).';
            }
        } else {
            $this->mensagemBloqueio = 'O aluno está em outra fase deste ciclo. Apenas leitura permitida.';
        }

        // 5. PREPARAÇÃO DO FORM BUILDER (Recuperando dados salvos)
        $this->camposDinamicos = $this->formulario->campos;
        $this->totalEtapas = max(1, $this->camposDinamicos->where('tipo', '!=', 'config')->max('etapa') ?? 1);
        
        // Puxa as respostas atreladas a este formulário e a este aluno específico
        $respostaSalva = \App\Models\RespostaFormulario::where('formulario_id', $this->formulario->id)
            ->where('user_id', $this->aluno->id) // Usamos o ID do aluno como âncora do documento
            ->first();

        if ($respostaSalva && is_array($respostaSalva->respostas)) {
            $this->respostas = $respostaSalva->respostas;
        } else {
            // Inicializa vazio se for a primeira vez
            foreach ($this->camposDinamicos->where('tipo', '!=', 'config') as $campo) {
                $this->respostas[$campo->name] = in_array($campo->tipo, ['check', 'matriz']) ? [] : '';
            }
        }


    }

    public function salvarEAvancar()
    {
        if (!$this->podeResponder) {
            $this->dispatch('erro', msg: 'Você possui apenas permissão de leitura nesta fase.');
            return;
        }

        // 1. Salva/Atualiza o "Documento" (JSON) mantendo o ID do aluno como âncora
        \App\Models\RespostaFormulario::updateOrCreate(
            [
                'formulario_id' => $this->formulario->id, 
                'user_id' => $this->aluno->id
            ],
            [
                'respostas' => $this->respostas,
                'etapa_parada' => $this->etapaAtual
            ]
        );

        // 2. Lógica de Workflow: Descobre qual é a próxima fase
        $faseAtualOrdem = $this->cicloAluno->faseAtual->ordem;
        
        $proximaFase = \App\Models\CicloAprendizagemFase::where('ciclo_aprendizagem_id', $this->cicloAluno->ciclo_aprendizagem_id)
            ->where('ordem', '>', $faseAtualOrdem)
            ->orderBy('ordem', 'asc')
            ->first();

        // 3. Atualiza o status do aluno
        if ($proximaFase) {
            $this->cicloAluno->update([
                'fase_atual_id' => $proximaFase->id,
                'status' => 'em_andamento'
            ]);
            $msg = "Formulário salvo! O aluno avançou para a fase: {$proximaFase->nome}.";
        } else {
            $this->cicloAluno->update(['status' => 'concluido']);
            $msg = "Formulário salvo! O ciclo de aprendizagem deste aluno foi concluído.";
        }

        // Atualiza a permissão na tela em tempo real para congelar os campos (Readonly)
        $this->podeResponder = false; 
        $this->mensagemBloqueio = 'Formulário já respondido por você e enviado para a próxima fase.';
        $this->dispatch('sucesso', msg: $msg);
    }

    public function render()
    {
        return view('livewire.gestao-educacional.formulario-aprendizagem');
    }
}