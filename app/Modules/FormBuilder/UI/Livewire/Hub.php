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
    public $modalVincularAberto = false;

    public $ciclos, $unidades, $cursos, $ciclosAprendizagem;

    public $preInscricao = ['titulo' => '', 'ciclo_id' => '', 'unidade_id' => '', 'curso_id' => '', 'is_rascunho' => true];
    public $aprendizagem = ['titulo' => '', 'ciclo_id' => '', 'fase_id' => '', 'is_rascunho' => false];
    public $fasesAprendizagem = [];

    public string $abaStatus = 'ativos'; // 'ativos' ou 'rascunhos'

    // Para vincular um formulário rascunho de aprendizagem antes de publicar
    public ?int $formVincularId = null;
    public string $formVincularTipo = '';

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

    public function criarFormularioPreInscricao()
    {
        $hash = substr(md5(uniqid()), 0, 6);
        $titulo = trim($this->preInscricao['titulo'] ?? '') ?: 'Nova Pré-Inscrição';
        $isRascunho = (bool) ($this->preInscricao['is_rascunho'] ?? true);

        $form = Formulario::create([
            'titulo'      => $titulo,
            'slug'        => Str::slug($titulo) . '-' . $hash,
            'tipo'        => 'pre_inscricao',
            'ciclo_id'    => $this->preInscricao['ciclo_id'] ?: null,
            'unidade_id'  => $this->preInscricao['unidade_id'] ?: null,
            'curso_id'    => $this->preInscricao['curso_id'] ?: null,
            'is_rascunho' => $isRascunho,
            'status'      => !$isRascunho,
        ]);

        return redirect()->route('construtor.campos', ['tipo' => 'formulario', 'id' => $form->id]);
    }

    public function criarFormularioAprendizagem()
    {
        $isRascunho = (bool) ($this->aprendizagem['is_rascunho'] ?? false);

        // Se não escolheu fase, cria obrigatoriamente como Rascunho desvinculado
        if (empty($this->aprendizagem['fase_id'])) {
            $isRascunho = true;
        } else {
            $jaExiste = Formulario::where('ciclo_aprendizagem_fase_id', $this->aprendizagem['fase_id'])->exists();
            if ($jaExiste) {
                $this->dispatch('erro', msg: 'Esta fase já possui um formulário vinculado. Escolha outra fase ou crie como Rascunho sem vínculo.');
                return;
            }
        }

        if (!$isRascunho) {
            $this->validate([
                'aprendizagem.ciclo_id' => 'required',
                'aprendizagem.fase_id'  => 'required'
            ], [
                'aprendizagem.ciclo_id.required' => 'Selecione o ciclo ou marque a opção de salvar como Rascunho.',
                'aprendizagem.fase_id.required'  => 'Você precisa selecionar a fase do ciclo.'
            ]);
        }

        $hash = substr(md5(uniqid()), 0, 6);
        $titulo = trim($this->aprendizagem['titulo'] ?? '') ?: 'Formulário de Aprendizagem';

        $form = Formulario::create([
            'titulo'                    => $titulo,
            'slug'                      => Str::slug($titulo) . '-' . $hash,
            'tipo'                      => 'aprendizagem',
            'ciclo_aprendizagem_fase_id'=> $this->aprendizagem['fase_id'] ?: null,
            'is_rascunho'               => $isRascunho,
            'status'                    => !$isRascunho,
        ]);

        return redirect()->route('construtor.campos', ['tipo' => 'formulario', 'id' => $form->id]);
    }

    public function criarFormularioAvulsoRascunho()
    {
        $hash = substr(md5(uniqid()), 0, 6);

        $form = Formulario::create([
            'titulo'        => 'Novo Formulário em Rascunho',
            'slug'          => 'formulario-' . $hash,
            'tipo'          => 'geral',
            'is_rascunho'   => true,
            'status'        => false,
            'acesso_livre'  => true,
        ]);

        return redirect()->route('construtor.campos', ['tipo' => 'formulario', 'id' => $form->id]);
    }

    public function alternarStatusRascunho(int $id)
    {
        $form = Formulario::findOrFail($id);

        if ($form->is_rascunho && $form->tipo === 'aprendizagem' && empty($form->ciclo_aprendizagem_fase_id)) {
            $this->formVincularId = $form->id;
            $this->formVincularTipo = 'aprendizagem';
            $this->aprendizagem = ['titulo' => $form->titulo, 'ciclo_id' => '', 'fase_id' => '', 'is_rascunho' => false];
            $this->modalVincularAberto = true;
            return;
        }

        $novoEstadoRascunho = !$form->is_rascunho;
        $form->update([
            'is_rascunho' => $novoEstadoRascunho,
            'status'      => !$novoEstadoRascunho,
        ]);

        $this->dispatch('sucesso', msg: $novoEstadoRascunho ? 'Formulário movido para Rascunhos.' : 'Formulário publicado e ativado com sucesso!');
    }

    public function confirmarVinculoEPublicar()
    {
        $this->validate([
            'aprendizagem.ciclo_id' => 'required',
            'aprendizagem.fase_id'  => 'required',
        ], [
            'aprendizagem.ciclo_id.required' => 'Selecione o Ciclo de Aprendizagem.',
            'aprendizagem.fase_id.required'  => 'Selecione a Fase para vincular.',
        ]);

        $jaExiste = Formulario::where('ciclo_aprendizagem_fase_id', $this->aprendizagem['fase_id'])
            ->where('id', '!=', $this->formVincularId)
            ->exists();

        if ($jaExiste) {
            $this->dispatch('erro', msg: 'Esta fase já possui outro formulário vinculado.');
            return;
        }

        $form = Formulario::findOrFail($this->formVincularId);
        $form->update([
            'ciclo_aprendizagem_fase_id' => $this->aprendizagem['fase_id'],
            'is_rascunho'                => false,
            'status'                     => true,
        ]);

        $this->modalVincularAberto = false;
        $this->formVincularId = null;
        $this->dispatch('sucesso', msg: 'Formulário vinculado à fase e publicado com sucesso!');
    }

    public function render()
    {
        $formulariosList = Formulario::with(['faseAprendizagem.ciclo', 'unidade', 'curso'])
            ->withCount('campos')
            ->where('is_rascunho', $this->abaStatus === 'rascunhos')
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('livewire.form-builder.hub', [
            'formulariosList' => $formulariosList,
        ]);
    }
}