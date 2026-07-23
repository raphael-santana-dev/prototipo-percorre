<?php

namespace App\Modules\Corporate\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

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

    public function mount()
    {
        // Trava de segurança provisória (apenas DEV acessa).
        // Futuramente, podemos validar: auth()->user()->can('usuario.listar')
        abort_if(!auth()->user()->hasRole('dev'), 403, 'Acesso restrito a Desenvolvedores.');
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|email|unique:users,email' . ($this->userId ? ',' . $this->userId : ''),
            'roleName' => 'required|string|exists:roles,name',
        ];

        // A senha só é obrigatória na criação. Na edição, só validamos se for preenchida.
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
            
            // Impede alteração do usuário DEV nativo por segurança
            if ($user->hasRole('dev') && !auth()->user()->hasRole('dev')) {
                $this->addError('email', 'Você não tem permissão para editar um usuário DEV.');
                return;
            }

            $user->update($data);
        } else {
            $user = User::create($data);
        }

        // Atribui a Role selecionada (sobrescrevendo qualquer outra que ele tivesse)
        $user->syncRoles([$this->roleName]);

        $this->resetInputFields();
        session()->flash('success', 'Usuário salvo com sucesso!');
    }

    public function edit(int $id)
    {
        $user = User::findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = ''; // Nunca enviamos a senha para o front-end
        
        // Pega o nome da primeira role do usuário (se existir)
        $this->roleName = $user->roles->first()?->name ?? '';
        
        $this->isEditMode = true;
    }

    public function delete(int $id)
    {
        $user = User::findOrFail($id);

        // Proteção essencial: Ninguém pode deletar o seu próprio usuário logado
        if ($user->id === auth()->id()) {
            session()->flash('error', 'Você não pode excluir a sua própria conta.');
            return;
        }

        // Impede a exclusão de qualquer usuário com a role DEV
        if ($user->hasRole('dev')) {
            session()->flash('error', 'Usuários com perfil DEV não podem ser excluídos.');
            return;
        }

        $user->delete();
        $this->resetInputFields();
        session()->flash('success', 'Usuário excluído com sucesso!');
    }

    public function cancel()
    {
        $this->resetInputFields();
    }

    private function resetInputFields()
    {
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->roleName = '';
        $this->userId = null;
        $this->isEditMode = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.corporate.user-manager', [
            // Carrega os usuários já com suas roles para evitar problemas de performance (N+1 query)
            'users' => User::with('roles')->orderBy('name')->get(),
            'roles' => Role::orderBy('name')->get()
        ]);
    }
}