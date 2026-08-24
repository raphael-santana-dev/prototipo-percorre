<?php

namespace App\Modules\Company\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use App\Modules\Student\Domain\Models\Student;
use App\Modules\Company\Domain\Models\CompanyUser;

#[Layout('components.layouts.company')]
#[Title('Meus Aprendizes')]
class AprendizesManager extends Component
{
    use WithPagination;

    public $busca = '';
    public $modalAberto = false;
    public $studentId = null;
    public $gestorSelecionadoId = '';

    public function updatingBusca()
    {
        $this->resetPage();
    }

    public function abrirModalVinculo($id)
    {
        $student = Student::findOrFail($id);
        
        // Proteção IDOR: Garante que a empresa só edita alunos dela mesma
        $usuario = Auth::guard('company')->user();
        abort_if($student->empresa_id !== $usuario->empresa_id, 403);

        $this->studentId = $student->id;
        $this->gestorSelecionadoId = $student->gestor_id ?? '';
        $this->modalAberto = true;
    }

    public function vincularGestor()
    {
        $student = Student::findOrFail($this->studentId);
        
        $student->update([
            'gestor_id' => $this->gestorSelecionadoId ?: null
        ]);

        $this->modalAberto = false;
        session()->flash('sucesso', 'Gestor vinculado ao aprendiz com sucesso!');
    }

    public function render()
    {
        $usuario = Auth::guard('company')->user();

        // Traz apenas alunos da empresa atual
        $query = Student::with('gestor')
            ->where('empresa_id', $usuario->empresa_id)
            ->where('is_aprendiz', true) // <- TRAVA DE SEGURANÇA ADICIONADA AQUI
            ->where(function($q) {
                $q->where('name', 'ilike', '%' . $this->busca . '%')
                  ->orWhere('cpf', 'like', '%' . preg_replace('/\D/', '', $this->busca) . '%');
            });

        // Se for um Gestor comum, ele só enxerga os aprendizes atrelados a ele
        if ($usuario->tipo_acesso === 'gestor_avaliador') {
            $query->where('gestor_id', $usuario->id);
        }

        $aprendizes = $query->orderBy('name')->paginate(10);

        // Lista de gestores ativos para o modal (Só o Contato Principal precisa dessa lista)
        $gestores = [];
        if ($usuario->tipo_acesso === 'contato_principal') {
            $gestores = CompanyUser::where('empresa_id', $usuario->empresa_id)
                ->where('tipo_acesso', 'gestor_avaliador')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        return view('livewire.company.aprendizes-manager', [
            'aprendizes' => $aprendizes,
            'gestores' => $gestores,
            'usuario' => $usuario
        ]);
    }
}