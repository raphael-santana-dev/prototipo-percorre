<?php

namespace App\Modules\Corporate\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use App\Modules\Unidade\Domain\Models\Unidade;
use App\Models\Curso;
use App\Modules\Turno\Domain\Models\Turno;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Usuários - Administrativo')]
class UserManager extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $roleName = '';
    
    public ?int $userId = null;
    public bool $isEditMode = false;
    public bool $showModal = false;

    // Arrays para armazenar os vínculos multi-tenancy
    public array $unidadesSelecionadas = [];
    public array $cursosSelecionados = [];
    public array $turnosSelecionados = [];

    // Coleções para os formulários
    public $cursosFiltrados = [];
    public $turnosFiltrados = [];

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403, 'Acesso restrito.');
    }

    public function openModal()
    {
        $this->resetInputFields();
        $this->aplicarRegrasCascata();
        $this->showModal = true;
    }

    // GATILHOS (Roda automaticamente quando a interface muda)
    public function updatedRoleName() { $this->aplicarRegrasCascata(); }
    public function updatedUnidadesSelecionadas() { $this->aplicarRegrasCascata(); }
    public function updatedCursosSelecionados() { $this->aplicarRegrasCascata(); }

    public function aplicarRegrasCascata()
    {
        if (strtolower($this->roleName) === 'professor') {
            
            // 1. Filtra Cursos baseados nas Unidades selecionadas
            if (empty($this->unidadesSelecionadas)) {
                $this->cursosFiltrados = [];
            } else {
                $this->cursosFiltrados = Curso::whereHas('unidades', function($q) {
                    $q->whereIn('unidades.id', $this->unidadesSelecionadas);
                })->whereIn('status', ['Ativo', '1', true])->get();
            }

            // Remove seleções inválidas de Cursos
            $idsCursosValidos = collect($this->cursosFiltrados)->pluck('id')->map(fn($id) => (string) $id)->toArray();
            $this->cursosSelecionados = array_intersect($this->cursosSelecionados, $idsCursosValidos);

            // 2. Filtra Turnos baseados nos Cursos selecionados (Usando o método turnosVinculados)
            if (empty($this->cursosSelecionados)) {
                $this->turnosFiltrados = [];
            } else {
                $turnosDisponiveis = collect();
                $cursosValidosObj = Curso::with('turnosVinculados')->whereIn('id', $this->cursosSelecionados)->get();
                foreach($cursosValidosObj as $curso) {
                    foreach($curso->turnosVinculados as $turno) {
                        $turnosDisponiveis->put($turno->id, $turno);
                    }
                }
                $this->turnosFiltrados = $turnosDisponiveis->values()->all();
            }

            // Remove seleções inválidas de Turnos
            $idsTurnosValidos = collect($this->turnosFiltrados)->pluck('id')->map(fn($id) => (string) $id)->toArray();
            $this->turnosSelecionados = array_intersect($this->turnosSelecionados, $idsTurnosValidos);

        } else {
            // Se não for professor, libera tudo
            $this->cursosFiltrados = Curso::whereIn('status', ['Ativo', '1', true])->get();
            $this->turnosFiltrados = Turno::all();
        }
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|email|unique:users,email' . ($this->userId ? ',' . $this->userId : ''),
            'roleName' => 'required|string|exists:roles,name',
            'unidadesSelecionadas' => 'nullable|array',
            'cursosSelecionados' => 'nullable|array',
            'turnosSelecionados' => 'nullable|array',
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
        ];

        if (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->isEditMode) {
            $user = User::findOrFail($this->userId);
            
            if ($user->hasRole('dev') && !auth()->user()->hasRole('dev')) {
                $this->addError('email', 'Você não tem permissão para editar um usuário DEV.');
                return;
            }
            $user->update($data);
        } else {
            $user = User::create($data);
        }

        $user->syncRoles([$this->roleName]);
        
        // Mágica do Pivot: Sincroniza os arrays com o banco de dados
        $user->unidades()->sync($this->unidadesSelecionadas);
        $user->cursos()->sync($this->cursosSelecionados);
        $user->turnos()->sync($this->turnosSelecionados);

        $this->showModal = false;
        $this->resetInputFields();
        session()->flash('success', 'Usuário e vínculos salvos com sucesso!');
    }

    public function edit(int $id)
    {
        $user = User::with(['roles', 'unidades', 'cursos', 'turnos'])->findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = ''; 
        
        $this->roleName = $user->roles->first()?->name ?? '';
        
        // Carrega os vínculos nos arrays para os checkboxes
        $this->unidadesSelecionadas = $user->unidades->pluck('id')->map(fn($id) => (string) $id)->toArray();
        $this->cursosSelecionados = $user->cursos->pluck('id')->map(fn($id) => (string) $id)->toArray();
        $this->turnosSelecionados = $user->turnos->pluck('id')->map(fn($id) => (string) $id)->toArray();
        
        $this->aplicarRegrasCascata();

        $this->isEditMode = true;
        $this->showModal = true;
    }

    public function delete(int $id)
    {
        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) {
            session()->flash('error', 'Você não pode excluir a sua própria conta.');
            return;
        }
        if ($user->hasRole('dev')) {
            session()->flash('error', 'Usuários com perfil DEV não podem ser excluídos.');
            return;
        }
        $user->delete();
        $this->resetInputFields();
        session()->flash('success', 'Usuário excluído com sucesso!');
    }

    private function resetInputFields()
    {
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->roleName = '';
        $this->userId = null;
        $this->isEditMode = false;
        
        $this->unidadesSelecionadas = [];
        $this->cursosSelecionados = [];
        $this->turnosSelecionados = [];
        
        $this->resetErrorBag();
    }

    public function showQuickDetails(int $id)
    {
        $user = User::with(['roles', 'unidades', 'cursos'])->findOrFail($id);
        
        $unidadesStr = $user->unidades->count() > 0 ? implode(', ', $user->unidades->pluck('nome')->toArray()) : 'Acesso Global';
        
        $detalhes = [
            'Nome Completo' => $user->name,
            'E-mail' => $user->email,
            'Grupo Principal' => $user->roles->first()?->name ?? 'Sem grupo',
            'Unidades Vinculadas' => $unidadesStr,
            'Criado em' => $user->created_at->format('d/m/Y H:i'),
        ];

        $this->dispatch('load-quick-view', [
            'title' => 'Perfil do Usuário', 
            'icon' => 'ph-user-circle', 
            'data' => $detalhes,
            'subtitle' => 'Visualização rápida de dados'
        ]);
    }

    public function render()
    {
        return view('livewire.corporate.user-manager', [
            'users' => User::with(['roles', 'unidades'])->orderBy('name')->get(),
            'roles' => Role::orderBy('name')->get(),
            'todasUnidades' => Unidade::whereIn('status', ['Ativa', '1', true])->get(), 
        ]);
    }
}