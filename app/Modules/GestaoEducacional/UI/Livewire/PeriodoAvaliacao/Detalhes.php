<?php

namespace App\Modules\GestaoEducacional\UI\Livewire\PeriodoAvaliacao;

use Livewire\Component;
use App\Modules\GestaoEducacional\Domain\Models\PeriodoAvaliacao;
use App\Modules\GestaoEducacional\Domain\Models\CriterioAvaliacao;
use App\Modules\GestaoEducacional\Domain\Models\AlunoAvaliacao;

class Detalhes extends Component
{
    public $periodoId;
    public $ano, $ciclo, $data_inicio, $data_fim, $status = '1';
    public $trava_fases = false;
    
    public array $criterios_selecionados = [];
    public array $fases = []; 

    public $avaliacoesGeradas = false;

    public function mount($id = null)
    {
        if ($id) {
            $periodo = PeriodoAvaliacao::with('fases', 'criterios')->findOrFail($id);
            
            $this->periodoId = $periodo->id;
            $this->ano = $periodo->ano;
            $this->ciclo = $periodo->ciclo;
            $this->data_inicio = $periodo->data_inicio;
            $this->data_fim = $periodo->data_fim;
            $this->status = $periodo->status;
            $this->trava_fases = (bool) $periodo->trava_fases;
            
            $this->criterios_selecionados = $periodo->criterios->pluck('id')->toArray();
            foreach ($periodo->fases as $f) {
                $this->fases[] = ['fase' => $f->fase, 'responsavel' => $f->responsavel];
            }

            // Verifica se já existem avaliações. Se sim, bloqueia alterar critérios e fases.
            $this->avaliacoesGeradas = AlunoAvaliacao::where('periodo_id', $this->periodoId)->exists();

        } else {
            $this->calcularProximoCiclo();
            $this->fases = [['fase' => '1', 'responsavel' => '1']]; 
        }
    }

    private function calcularProximoCiclo()
    {
        $ultimo = PeriodoAvaliacao::orderBy('ano', 'desc')->orderBy('ciclo', 'desc')->first();
        if (!$ultimo) {
            $this->ano = date('Y');
            $this->ciclo = '1';
        } else {
            if ($ultimo->ciclo == '1') {
                $this->ano = $ultimo->ano;
                $this->ciclo = '2';
            } else {
                $this->ano = (string)($ultimo->ano + 1);
                $this->ciclo = '1';
            }
        }
    }

    public function adicionarFase()
    {
        if ($this->avaliacoesGeradas) {
            $this->dispatch('erro', msg: 'Não é possível alterar as fases, pois as avaliações já foram geradas.');
            return;
        }
        $novaFaseNum = count($this->fases) + 1;
        $this->fases[] = ['fase' => (string)$novaFaseNum, 'responsavel' => '2'];
    }

    public function removerFase($index)
    {
        if ($this->avaliacoesGeradas) {
            $this->dispatch('erro', msg: 'Não é possível alterar as fases, pois as avaliações já foram geradas.');
            return;
        }
        unset($this->fases[$index]);
        $this->fases = array_values($this->fases); 
        foreach ($this->fases as $k => $v) {
            $this->fases[$k]['fase'] = (string)($k + 1);
        }
    }

    public function salvar()
    {
        $this->validate([
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after:data_inicio',
            'status' => 'required|in:1,2',
            'criterios_selecionados' => 'required|array|min:1',
            'fases' => 'required|array|min:1',
            'trava_fases' => 'boolean',
        ]);

        if ($this->status === '1') {
            $abertos = PeriodoAvaliacao::where('status', '1')->where('id', '!=', $this->periodoId)->count();
            if ($abertos > 0) {
                $this->addError('status', 'Já existe um período de avaliação em aberto.');
                return;
            }
        }

        if ($this->avaliacoesGeradas) {
            $periodoOriginal = PeriodoAvaliacao::with('fases', 'criterios')->find($this->periodoId);
            $criteriosOriginais = $periodoOriginal->criterios->pluck('id')->toArray();
            
            sort($criteriosOriginais);
            $criteriosAtuais = $this->criterios_selecionados;
            sort($criteriosAtuais);

            if ($criteriosOriginais !== $criteriosAtuais || count($periodoOriginal->fases) !== count($this->fases)) {
                $this->dispatch('erro', msg: 'Não é possível alterar a estrutura de critérios e fases com avaliações em andamento.');
                return;
            }
        }

        $periodo = PeriodoAvaliacao::updateOrCreate(
            ['id' => $this->periodoId],
            [
                'ano' => $this->ano,
                'ciclo' => $this->ciclo,
                'data_inicio' => $this->data_inicio,
                'data_fim' => $this->data_fim,
                'status' => $this->status,
                'trava_fases' => $this->trava_fases,
            ]
        );

        if (!$this->avaliacoesGeradas) {
            $periodo->criterios()->sync($this->criterios_selecionados);
            $periodo->fases()->delete(); 
            foreach ($this->fases as $faseData) {
                $periodo->fases()->create($faseData);
            }
        }

        session()->flash('sucesso', 'Período de avaliação salvo com sucesso!');
        return redirect()->route('avaliacoes.periodos.index'); 
    }

    public function render()
    {
        return view('livewire.gestao-educacional.periodo-avaliacao.detalhes', [
            'criteriosDisponiveis' => CriterioAvaliacao::where('status', true)->orderBy('codigo')->get()
        ])->layout('components.layouts.app', ['title' => 'Configurar Período']);
    }
}