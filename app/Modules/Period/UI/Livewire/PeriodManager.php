<?php

namespace App\Modules\Period\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Ciclo;
use App\Models\Curso; // Importação necessária
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Ciclos - Administrativo')]
class PeriodManager extends Component
{
    use WithPagination;

    public $modalAberto = false;
    public $unicoAtivo = true;
    public $status = false;
    public $cicloId = null;

    public $nome, $ano, $semestre, $data_inicio, $data_fim;
    
    // Novo array para guardar os cursos marcados no modal
    public array $cursosSelecionados = []; 
    
    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev'), 403, 'Acesso restrito a Desenvolvedores.');
    }

    protected function rules()
    {
        return [
            'nome' => 'nullable|string|max:255',
            'ano' => 'required|integer|min:2020',
            'semestre' => 'required|integer|in:1,2',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after:data_inicio',
            'status' => 'boolean',
        ];
    }

    public function abrirModal($id = null)
    {
        $this->resetValidation();
        // Reseta também o array de cursos ao abrir o modal
        $this->reset(['cicloId', 'nome', 'ano', 'semestre', 'data_inicio', 'data_fim', 'status', 'cursosSelecionados']);

        if ($id) {
            $ciclo = Ciclo::with('cursos')->findOrFail($id);
            $this->cicloId = $ciclo->id;
            $this->nome = $ciclo->nome;
            $this->ano = $ciclo->ano;
            $this->semestre = $ciclo->semestre;
            $this->data_inicio = $ciclo->data_inicio->format('Y-m-d\TH:i');
            $this->data_fim = $ciclo->data_fim->format('Y-m-d\TH:i');
            $this->status = $ciclo->status;
            
            // Povoa os checkboxes com os IDs dos cursos já vinculados
            $this->cursosSelecionados = $ciclo->cursos->pluck('id')->toArray();
        } else {
            $this->ano = date('Y');
            $this->semestre = date('n') <= 6 ? 1 : 2;
        }

        $this->modalAberto = true;
    }

    public function fecharModal()
    {
        $this->modalAberto = false;
    }

    public function salvar()
    {
        $this->validate();

        if ($this->status) {
            Ciclo::where('id', '!=', $this->cicloId)->update(['status' => false]);
        }

        $nomeFinal = trim($this->nome);
        if (empty($nomeFinal)) {
            $nomeFinal = "{$this->ano} - {$this->semestre}º Semestre";
        }

        // Recuperamos a instância do ciclo criado/atualizado na variável $cicloSalvo
        $cicloSalvo = Ciclo::updateOrCreate(
            ['id' => $this->cicloId],
            [
                'nome' => $nomeFinal,
                'ano' => $this->ano,
                'semestre' => $this->semestre,
                'data_inicio' => $this->data_inicio,
                'data_fim' => $this->data_fim,
                'status' => $this->status,
            ]
        );

        // MÁGICA: Sincroniza a tabela pivô 'ciclo_curso' automaticamente
        $cicloSalvo->cursos()->sync($this->cursosSelecionados);

        $this->fecharModal();
        session()->flash('sucesso', 'Ciclo salvo com sucesso!');
    }

    public function render()
    {
        $ciclos = Ciclo::orderBy('id', 'desc')->paginate(10); // Ajustado para 10 itens por página
        
        // Busca os cursos ativos para exibir no modal
        $cursosDisponiveis = Curso::where('status', 'Ativo')->orderBy('nome')->get();

        return view('livewire.period.period-manager', [
            'ciclos' => $ciclos,
            'cursosDisponiveis' => $cursosDisponiveis
        ]);
    }
}