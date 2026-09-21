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

        $query = Student::with('gestor')
            ->where('empresa_id', $usuario->empresa_id)
            ->where('is_aprendiz', true) 
            ->where(function($q) {
                $q->where('name', 'ilike', '%' . $this->busca . '%')
                  ->orWhere('cpf', 'like', '%' . preg_replace('/\D/', '', $this->busca) . '%');
            });

        if ($usuario->tipo_acesso === 'gestor_avaliador') {
            $query->where('gestor_id', $usuario->id);
        }

        $aprendizes = $query->orderBy('name')->paginate(10);

        $aprendizesIds = $aprendizes->pluck('id')->toArray();
        $gestores = [];
        if ($usuario->tipo_acesso === 'contato_principal') {
            $gestores = CompanyUser::where('empresa_id', $usuario->empresa_id)
                ->where('tipo_acesso', 'gestor_avaliador')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }
        
        $avaliacoes = \App\Models\AlunoCicloAprendizagem::with(['faseAtual.formularios', 'ciclo'])
            ->whereIn('student_id', $aprendizesIds)
            ->where('status', '2')
            ->get();
            
        $avaliacoesPorAluno = [];
        foreach($avaliacoes as $av) {
            $respondedores = $av->faseAtual->respondedores_permitidos ?? [];
            if (in_array('company', $respondedores)) {
                $avaliacoesPorAluno[$av->student_id][] = $av;
            }
        }

        

        return view('livewire.company.aprendizes-manager', [
            'aprendizes' => $aprendizes,
            'avaliacoesPorAluno' => $avaliacoesPorAluno,
            'gestores' => $gestores,
            'usuario' => $usuario
        ]);

    }
}