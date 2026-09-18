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

    public function mount($slug)
    {
        // Garante que só carregue se for do tipo correto
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
        // Aqui você pode injetar o método regrasPorEtapa() do FormularioPublico para validação
        if ($this->etapaAtual < $this->totalEtapas) {
            $this->etapaAtual++;
        } else {
            // Salva o Lead no banco de dados
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
        return view('livewire.website.pre-inscricao')->title($this->formulario->titulo);
    }
}