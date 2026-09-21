<?php

namespace App\Modules\FormBuilder\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Str;
use App\Models\Ciclo;
use App\Models\Formulario;
use App\Models\CicloAprendizagem;

#[Layout('components.layouts.app')]
#[Title('Central de Formulários')]
class Hub extends Component
{
    public $modalCicloAberto = false;
    public $modalPreInscricaoAberto = false;
    public $modalAprendizagemAberto = false;

    public $ciclos, $unidades, $cursos, $ciclosAprendizagem;

    public $preInscricao = ['ciclo_id' => '', 'unidade_id' => '', 'curso_id' => ''];
    
    public $aprendizagem = ['ciclo_id' => '', 'fase_id' => ''];
    public $fasesAprendizagem = [];

    public function mount()
    {
        abort_if(!feature('formulario.listar'), 403, 'Módulo desativado.');
        abort_if(!auth()->user()->hasRole('dev|admin') && !auth()->user()->can('formulario.listar'), 403, 'Acesso restrito.');
        
        $this->ciclos = Ciclo::orderBy('id', 'desc')->take(10)->get();
        $this->unidades = \App\Modules\Unidade\Domain\Models\Unidade::where('status', 'Ativa')->orderBy('nome')->get();
        $this->cursos = \App\Models\Curso::where('status', 'Ativo')->orderBy('nome')->get();
        $this->ciclosAprendizagem = CicloAprendizagem::with('fases')->orderBy('id', 'desc')->get();
    }

    public function selecionarCicloSeletivo($cicloId)
    {
        return redirect()->route('construtor.campos', ['tipo' => 'ciclo', 'id' => $cicloId]);
    }

    public function updatedAprendizagemCicloId($cicloId)
    {
        $cicloSelecionado = $this->ciclosAprendizagem->firstWhere('id', $cicloId);
        $this->fasesAprendizagem = $cicloSelecionado ? $cicloSelecionado->fases : [];
        $this->aprendizagem['fase_id'] = '';
    }

    /**
     * Cria o rascunho de Pré-Inscrição e joga o usuário para o Builder
     */
    public function criarFormularioPreInscricao()
    {
        $hash = substr(md5(uniqid()), 0, 6);
        
        $form = Formulario::create([
            'titulo' => 'Nova Pré-Inscrição',
            'slug' => 'pre-inscricao-' . $hash,
            'tipo' => 'pre_inscricao',
            'ciclo_id' => $this->preInscricao['ciclo_id'] ?: null,
            'unidade_id' => $this->preInscricao['unidade_id'] ?: null,
            'curso_id' => $this->preInscricao['curso_id'] ?: null,
            'status' => false
        ]);

        return redirect()->route('construtor.campos', ['tipo' => 'formulario', 'id' => $form->id]);
    }

    /**
     * Cria o rascunho de Aprendizagem e joga o usuário para o Builder
     */
    public function criarFormularioAprendizagem()
    {
        $jaExiste = \App\Models\Formulario::where('ciclo_aprendizagem_fase_id', $this->aprendizagem['fase_id'])->exists();
        if ($jaExiste) {
            $this->dispatch('erro', msg: 'Esta fase já possui um formulário vinculado. Não é permitido criar mais de um.');
            return;
        }

        $this->validate([
            'aprendizagem.ciclo_id' => 'required',
            'aprendizagem.fase_id' => 'required'
        ], [
            'aprendizagem.fase_id.required' => 'Você precisa selecionar a fase do ciclo.'
        ]);

        $hash = substr(md5(uniqid()), 0, 6);

        $form = Formulario::create([
            'titulo' => 'Formulário de Aprendizagem',
            'slug' => 'aprendizagem-' . $hash,
            'tipo' => 'aprendizagem',
            'ciclo_aprendizagem_fase_id' => $this->aprendizagem['fase_id'],
            'status' => false
        ]);

        return redirect()->route('construtor.campos', ['tipo' => 'formulario', 'id' => $form->id]);
    }

    public function render()
    {
        return view('livewire.form-builder.hub');
    }
}