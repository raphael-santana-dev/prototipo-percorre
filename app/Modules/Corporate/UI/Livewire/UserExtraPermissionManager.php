<?php

namespace App\Modules\Corporate\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\User;
use App\Modules\ACL\Domain\Models\Permission;

#[Layout('components.layouts.app')]
#[Title('Permissões Extras - Corporativo')]
class UserExtraPermissionManager extends Component
{
    public int $userId;
    public string $userName;
    
    public array $selectedPermissions = [];
    public array $expirations = [];

    public function mount(int $userId)
    {
        $user = User::findOrFail($userId);
        
        // Bloqueia se um ADMIN tentar editar as permissões extras de um DEV
        if ($user->hasRole('dev') && !auth()->user()->hasRole('dev')) {
            abort(403, 'Você não tem privilégios para alterar as permissões de um usuário DEV.');
        }

        $this->userId = $user->id;
        $this->userName = $user->name;

        // Carrega as permissões diretas do usuário lendo também a coluna pivô
        $directPermissions = $user->permissions()->withPivot('expires_at')->get();

        foreach ($directPermissions as $perm) {
            $this->selectedPermissions[] = $perm->id;
            if ($perm->pivot->expires_at) {
                // Formata a data para o input date do HTML
                $this->expirations[$perm->id] = date('Y-m-d', strtotime($perm->pivot->expires_at));
            }
        }
    }

    public function save()
    {
        $user = User::findOrFail($this->userId);
        $syncData = [];
        $currentUser = auth()->user();
        $isDev = $currentUser->hasRole('dev');

        foreach ($this->selectedPermissions as $permissionId) {
            // Ignora valores false/null do array manipulado pelo Livewire
            if (!$permissionId) continue;

            $permission = Permission::findById($permissionId);

            // Regra de Delegação: Se não for DEV, só pode conceder o que ele próprio tem
            if (!$isDev && !$currentUser->hasPermissionTo($permission->name)) {
                session()->flash('error', "Você não pode conceder a permissão '{$permission->name}' pois você não a possui.");
                return;
            }

            // Pega a data informada no array de expirations ou deixa null (permanente)
            $expiresAt = !empty($this->expirations[$permissionId]) ? $this->expirations[$permissionId] : null;

            $syncData[$permissionId] = ['expires_at' => $expiresAt];
        }

        // Atualiza a tabela pivô com as permissões marcadas e suas respectivas datas
        $user->permissions()->sync($syncData);

        session()->flash('success', 'Permissões extras atualizadas com sucesso!');
    }

    public function render()
    {
        return view('livewire.corporate.user-extra-permission-manager', [
            'permissionsByModule' => Permission::orderBy('module')->orderBy('name')->get()->groupBy('module')
        ]);
    }
}