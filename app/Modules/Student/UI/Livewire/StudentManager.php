<?php

namespace App\Modules\Student\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\Student\Domain\Models\Student;
use App\Modules\Unidade\Domain\Models\Unidade;
use Illuminate\Support\Facades\Hash;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Estudantes - Administrativo')]
class StudentManager extends Component
{
    public bool $showModal = false;
    public bool $isEditMode = false;
    public ?int $studentId = null;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public bool $is_active = true;
    public ?int $unidade_id = null;

    public function mount()
    {
        // Garante que apenas quem tem a permissão pode acessar a tela
        abort_if(!auth()->user()->can('estudante.listar'), 403, 'Você não tem permissão para listar alunos.');
    }

    public function openModal()
    {
        abort_if(!auth()->user()->can('estudante.criar'), 403);
        $this->resetInputFields();
        $this->showModal = true;
    }

    public function edit(int $id)
    {
        abort_if(!auth()->user()->can('estudante.editar'), 403);
        $this->resetInputFields();
        
        $student = Student::findOrFail($id);
        $this->studentId = $student->id;
        $this->name = $student->name;
        $this->email = $student->email;
        $this->is_active = $student->is_active;
        $this->unidade_id = $student->unidade_id;
        
        $this->isEditMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        if ($this->isEditMode) {
            abort_if(!auth()->user()->can('estudante.editar'), 403);
        } else {
            abort_if(!auth()->user()->can('estudante.criar'), 403);
        }

        $rules = [
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|email|unique:students,email' . ($this->studentId ? ',' . $this->studentId : ''),
            'unidade_id' => 'required|exists:unidades,id', // Para aluno, a unidade é obrigatória
        ];

        if (!$this->isEditMode) {
            $rules['password'] = 'required|string|min:6';
        } elseif (!empty($this->password)) {
            $rules['password'] = 'string|min:6';
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'email' => strtolower($this->email),
            'is_active' => $this->is_active,
            'unidade_id' => $this->unidade_id,
        ];

        if (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->isEditMode) {
            Student::findOrFail($this->studentId)->update($data);
        } else {
            Student::create($data);
        }

        $this->showModal = false;
        $this->resetInputFields();
        session()->flash('success', 'Estudante salvo com sucesso!');
    }

    public function delete(int $id)
    {
        abort_if(!auth()->user()->can('estudante.excluir'), 403);
        Student::findOrFail($id)->delete();
        session()->flash('success', 'Estudante excluído com sucesso!');
    }

    // A MÁGICA DO DRAWER REAPROVEITADO
    public function showQuickDetails(int $id)
    {
        $student = Student::with('unidade')->findOrFail($id);
        
        $statusHtml = $student->is_active 
            ? '<span class="text-green-600 font-bold">Matrícula Ativa</span>' 
            : '<span class="text-red-600 font-bold">Matrícula Inativa</span>';

        $detalhes = [
            'Nome do Aluno' => $student->name,
            'E-mail de Acesso' => $student->email,
            'Unidade Sede' => $student->unidade?->nome ?? 'Não alocado',
            'Status' => $statusHtml,
            'Data da Matrícula' => $student->created_at->format('d/m/Y H:i'),
        ];

        $this->dispatch('load-quick-view', [
            'title' => 'Ficha Rápida do Aluno', 
            'icon' => 'ph-graduation-cap', 
            'data' => $detalhes,
            'subtitle' => 'Resumo cadastral'
        ]);
    }

    private function resetInputFields()
    {
        $this->studentId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->is_active = true;
        // Se o usuário logado for de uma unidade, já preenche o campo para ele
        $this->unidade_id = auth()->user()->unidades->first()->id ?? null;
        $this->isEditMode = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.student.student-manager', [
            'students' => Student::with('unidade')->apenasVinculosPermitidos()->orderBy('name')->get(),
            'unidades' => Unidade::orderBy('nome')->get(),
        ]);
    }
}