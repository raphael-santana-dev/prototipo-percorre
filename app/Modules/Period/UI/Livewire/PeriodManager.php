<?php

namespace App\Modules\Period\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Ciclo;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
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
        $this->reset(['cicloId', 'nome', 'ano', 'semestre', 'data_inicio', 'data_fim', 'status']);

        if ($id) {
            $ciclo = Ciclo::findOrFail($id);
            $this->cicloId = $ciclo->id;
            $this->nome = $ciclo->nome;
            $this->ano = $ciclo->ano;
            $this->semestre = $ciclo->semestre;
            // Formatação necessária para o input type="datetime-local" do HTML5
            $this->data_inicio = $ciclo->data_inicio->format('Y-m-d\TH:i');
            $this->data_fim = $ciclo->data_fim->format('Y-m-d\TH:i');
            $this->status = $ciclo->status;
        } else {
            // Valores padrão para um novo ciclo
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

        // Se o status for marcado como true (Ativo), podemos desativar os outros ciclos
        // para garantir que apenas um processo seletivo fique aberto no formulário público por vez.
        if ($this->status) {
            Ciclo::where('id', '!=', $this->cicloId)->update(['status' => false]);
        }

        $nomeFinal = trim($this->nome);
        if (empty($nomeFinal)) {
            $nomeFinal = "{$this->ano} - {$this->semestre}º Semestre";
        }

        Ciclo::updateOrCreate(
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

        $this->fecharModal();
        session()->flash('sucesso', 'Ciclo salvo com sucesso!');
    }

    public function render()
    {
        $ciclos = Ciclo::orderBy('id', 'desc')->paginate(10);

        return view('livewire.period.period-manager', [
            'ciclos' => $ciclos,
        ]);
    }
}