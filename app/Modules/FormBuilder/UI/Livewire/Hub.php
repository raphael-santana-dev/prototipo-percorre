<?php

namespace App\Modules\FormBuilder\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Str;
use App\Models\Ciclo;
use App\Models\Formulario;
use App\Models\CampoFormulario;
use App\Models\Etapa;
use App\Models\FormularioFase;
use App\Models\CicloAprendizagem;

#[Layout('components.layouts.app')]
#[Title('Central de Formulários')]
class Hub extends Component
{
    public $modalCicloAberto = false;
    public $modalAprendizagemAberto = false;
    public $modalVincularAberto = false;

    public $ciclos, $unidades, $cursos, $ciclosAprendizagem;

    public $aprendizagem = ['titulo' => '', 'ciclo_id' => '', 'fase_id' => '', 'is_rascunho' => false];
    public $fasesAprendizagem = [];

    public string $abaStatus = 'ativos';

    // Propriedades do Modal Universal de Vínculo
    public ?int $formVincularId = null;
    public string $formVincularTitulo = '';
    public string $destinoVinculo = 'geral'; // 'ciclo_inscricao', 'aprendizagem', 'geral'
    public $vinculoCicloInscricaoId = '';
    public bool $substituirCamposCiclo = false;
    public $vinculoAprendizagemCicloId = '';
    public $vinculoAprendizagemFaseId = '';
    public array $fasesVinculoDisponiveis = [];
    public bool $publicarAoVincular = true;

    public function mount()
    {
        $this->ciclos = Ciclo::orderBy('id', 'desc')->get();
        $this->unidades = \App\Modules\Unidade\Domain\Models\Unidade::whereIn('status', ['Ativa', '1', true])->orderBy('nome')->get();
        $this->cursos = \App\Models\Curso::whereIn('status', ['Ativo', 'ativo', '1', true])->orderBy('nome')->get();
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

    public function updatedVinculoAprendizagemCicloId($cicloId)
    {
        $cicloSelecionado = $this->ciclosAprendizagem->firstWhere('id', $cicloId);
        $this->fasesVinculoDisponiveis = $cicloSelecionado ? $cicloSelecionado->fases->toArray() : [];
        $this->vinculoAprendizagemFaseId = '';
    }

    public function criarFormularioAprendizagem()
    {
        $isRascunho = (bool) ($this->aprendizagem['is_rascunho'] ?? false);

        if (empty($this->aprendizagem['fase_id'])) {
            $isRascunho = true;
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
            'titulo'                     => $titulo,
            'slug'                       => Str::slug($titulo) . '-' . $hash,
            'tipo'                       => 'aprendizagem',
            'ciclo_aprendizagem_fase_id' => $this->aprendizagem['fase_id'] ?: null,
            'is_rascunho'                => $isRascunho,
            'status'                     => !$isRascunho,
        ]);

        return redirect()->route('construtor.campos', ['tipo' => 'formulario', 'id' => $form->id]);
    }

    public function criarFormularioAvulsoRascunho()
    {
        $hash = substr(md5(uniqid()), 0, 6);

        $form = Formulario::create([
            'titulo'       => 'Novo Formulário em Rascunho',
            'slug'         => 'formulario-' . $hash,
            'tipo'         => 'geral',
            'is_rascunho'  => true,
            'status'       => false,
            'acesso_livre' => true,
        ]);

        return redirect()->route('construtor.campos', ['tipo' => 'formulario', 'id' => $form->id]);
    }

    public function abrirModalVincular(int $id)
    {
        $form = Formulario::with('faseAprendizagem')->findOrFail($id);

        $this->formVincularId = $form->id;
        $this->formVincularTitulo = $form->titulo;
        $this->destinoVinculo = in_array($form->tipo, ['aprendizagem', 'geral']) ? $form->tipo : 'geral';
        $this->vinculoCicloInscricaoId = $form->ciclo_id ?? '';
        $this->substituirCamposCiclo = false;
        $this->publicarAoVincular = true;

        if ($form->faseAprendizagem) {
            $this->vinculoAprendizagemCicloId = $form->faseAprendizagem->ciclo_aprendizagem_id ?? '';
            $this->updatedVinculoAprendizagemCicloId($this->vinculoAprendizagemCicloId);
            $this->vinculoAprendizagemFaseId = $form->ciclo_aprendizagem_fase_id;
        } else {
            $this->vinculoAprendizagemCicloId = '';
            $this->vinculoAprendizagemFaseId = '';
            $this->fasesVinculoDisponiveis = [];
        }

        $this->resetValidation();
        $this->modalVincularAberto = true;
    }

    public function salvarVinculoFormulario()
    {
        $form = Formulario::with('campos')->findOrFail($this->formVincularId);

        if ($this->destinoVinculo === 'ciclo_inscricao') {
            $this->validate([
                'vinculoCicloInscricaoId' => 'required|exists:ciclos,id',
            ], [
                'vinculoCicloInscricaoId.required' => 'Selecione para qual Ciclo de Inscrição deseja aplicar este formulário.',
            ]);

            $cicloAlvoId = (int) $this->vinculoCicloInscricaoId;

            if ($this->substituirCamposCiclo) {
                CampoFormulario::where('ciclo_id', $cicloAlvoId)->delete();
                Etapa::where('ciclo_id', $cicloAlvoId)->delete();
                FormularioFase::where('ciclo_id', $cicloAlvoId)->delete();
            }

            foreach (Etapa::where('formulario_id', $form->id)->get() as $etapaForm) {
                Etapa::firstOrCreate(
                    ['ciclo_id' => $cicloAlvoId, 'numero' => $etapaForm->numero],
                    ['nome' => $etapaForm->nome]
                );
            }

            foreach (FormularioFase::where('formulario_id', $form->id)->get() as $faseForm) {
                $novaFase = $faseForm->replicate();
                $novaFase->formulario_id = null;
                $novaFase->ciclo_id = $cicloAlvoId;
                $novaFase->save();
            }

            $maxOrdemExistente = CampoFormulario::where('ciclo_id', $cicloAlvoId)->max('ordem') ?? 0;
            foreach ($form->campos as $campoOrig) {
                if ($campoOrig->name === '_form_config') {
                    CampoFormulario::updateOrCreate(
                        ['ciclo_id' => $cicloAlvoId, 'name' => '_form_config'],
                        [
                            'formulario_id' => null,
                            'etapa'         => 0,
                            'ordem'         => 0,
                            'label'         => 'Configurações Globais',
                            'tipo'          => 'config',
                            'largura'       => 12,
                            'configuracoes' => $campoOrig->configuracoes,
                        ]
                    );
                    continue;
                }

                $jaTemNome = CampoFormulario::where('ciclo_id', $cicloAlvoId)->where('name', $campoOrig->name)->exists();
                $novoCampo = $campoOrig->replicate();
                $novoCampo->formulario_id = null;
                $novoCampo->ciclo_id = $cicloAlvoId;
                if ($jaTemNome) {
                    $novoCampo->name = $campoOrig->name . '_' . rand(10, 99);
                }
                if (!$this->substituirCamposCiclo) {
                    $maxOrdemExistente++;
                    $novoCampo->ordem = $maxOrdemExistente;
                }
                $novoCampo->save();
            }

            $form->update([
                'titulo'   => $this->formVincularTitulo ?: $form->titulo,
                'ciclo_id' => $cicloAlvoId,
            ]);

            $this->modalVincularAberto = false;
            $this->dispatch('sucesso', msg: 'Campos e estrutura aplicados ao Ciclo de Inscrição com sucesso!');
            return;
        }

        if ($this->destinoVinculo === 'aprendizagem') {
            $this->validate([
                'vinculoAprendizagemCicloId' => 'required',
                'vinculoAprendizagemFaseId'  => 'required',
            ], [
                'vinculoAprendizagemCicloId.required' => 'Selecione o Ciclo de Aprendizagem.',
                'vinculoAprendizagemFaseId.required'  => 'Selecione a Fase do Ciclo de Aprendizagem.',
            ]);

            $form->update([
                'titulo'                     => $this->formVincularTitulo ?: $form->titulo,
                'tipo'                       => 'aprendizagem',
                'ciclo_aprendizagem_fase_id' => $this->vinculoAprendizagemFaseId,
                'is_rascunho'                => !$this->publicarAoVincular,
                'status'                     => $this->publicarAoVincular,
            ]);

            $this->modalVincularAberto = false;
            $this->abaStatus = $this->publicarAoVincular ? 'ativos' : 'rascunhos';
            $this->dispatch('sucesso', msg: 'Formulário vinculado à Fase de Aprendizagem com sucesso!');
            return;
        }

        $form->update([
            'titulo'                     => $this->formVincularTitulo ?: $form->titulo,
            'tipo'                       => 'geral',
            'ciclo_id'                   => $this->vinculoCicloInscricaoId ?: null,
            'unidade_id'                 => null,
            'curso_id'                   => null,
            'ciclo_aprendizagem_fase_id' => null,
            'is_rascunho'                => !$this->publicarAoVincular,
            'status'                     => $this->publicarAoVincular,
            'acesso_livre'               => $form->acesso_livre ?? true,
        ]);

        $this->modalVincularAberto = false;
        $this->abaStatus = $this->publicarAoVincular ? 'ativos' : 'rascunhos';
        $this->dispatch('sucesso', msg: 'Formulário Geral (/f/' . $form->slug . ') vinculado e atualizado com sucesso!');
    }

    public function alternarStatusRascunho(int $id)
    {
        $form = Formulario::findOrFail($id);

        if ($form->is_rascunho && $form->tipo === 'aprendizagem' && empty($form->ciclo_aprendizagem_fase_id)) {
            $this->abrirModalVincular($form->id);
            return;
        }

        $novoEstadoRascunho = !$form->is_rascunho;
        $form->update([
            'is_rascunho' => $novoEstadoRascunho,
            'status'      => !$novoEstadoRascunho,
        ]);

        $this->dispatch('sucesso', msg: $novoEstadoRascunho ? 'Formulário movido para Rascunhos.' : 'Formulário publicado e ativado com sucesso!');
    }

    public function render()
    {
        $formulariosList = Formulario::with(['faseAprendizagem.ciclo', 'cicloSeletivo'])
            ->where('tipo', '!=', 'pre_inscricao')
            ->withCount('campos')
            ->where('is_rascunho', $this->abaStatus === 'rascunhos')
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('livewire.form-builder.hub', [
            'formulariosList' => $formulariosList,
        ]);
    }
}