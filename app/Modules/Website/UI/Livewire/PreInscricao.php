<?php

namespace App\Modules\Website\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Formulario;
use App\Models\RespostaFormulario;

#[Layout('components.layouts.public')]
class PreInscricao extends Component
{
    public Formulario $formulario;
    public $camposDinamicos = [];
    public $respostas = [];
    
    public int $etapaAtual = 1;
    public int $totalEtapas = 1;
    public bool $finalizado = false;
    public array $formSettings = [];

    // NOVAS VARIÁVEIS PARA CAPTAÇÃO DE INTERESSE
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
                $this->respostas[$campo->name] = in_array($campo->tipo, ['check', 'matriz']) ? [] : '';
            }
        }
    }

    public function avancarEtapa()
    {
        // Se desejar informar, exigir seleção:
        if ($this->etapaAtual == 1 && $this->deseja_informar) {
            $this->validate([
                'unidade_interesse' => 'required',
                'curso_interesse' => 'required',
                'turno_interesse' => 'required',
            ], [
                'unidade_interesse.required' => 'Obrigatório.',
                'curso_interesse.required' => 'Obrigatório.',
                'turno_interesse.required' => 'Obrigatório.',
            ]);
        }

        if ($this->etapaAtual < $this->totalEtapas) {
            $this->etapaAtual++;
        } else {
            // Em formulários avulsos (Pre-inscrição), podemos gravar os campos de interesse
            // dentro do json das "respostas", já que a tabela RespostaFormulario não 
            // tem as colunas unidade_interesse_id estruturadas como na tabela de Inscrições.
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
        $unidadesInteresseDb = \App\Modules\Unidade\Domain\Models\Unidade::whereIn('status', ['Ativa', '1', true])->get();
        $cursosInteresseDb = \App\Models\Curso::whereIn('status', ['Ativo', 'ativo', '1', 1, true])->get();
        $turnosInteresseDb = \App\Modules\Turno\Domain\Models\Turno::all(); 

        return view('livewire.website.pre-inscricao', [
            'unidadesInteresseDb' => $unidadesInteresseDb,
            'cursosInteresseDb' => $cursosInteresseDb,
            'turnosInteresseDb' => $turnosInteresseDb,
        ])->title($this->formulario->titulo);
    }
}