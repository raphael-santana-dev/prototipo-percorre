<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use App\Modules\FeatureToggle\Domain\Models\Feature; 


class PendingPermissionsAndFeaturesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpa o cache do Spatie antes de inserir novas permissões
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->seedFeatures();
        $this->seedPermissions();
    }

    private function seedFeatures(): void
    {
        $features = [
            ['name' => 'dashboard', 'module' => 'dashboard', 'description' => 'Módulo de dashboard e estatísticas'],
            ['name' => 'estudantes', 'module' => 'estudante', 'description' => 'Módulo de gestão de estudantes'],
            ['name' => 'features', 'module' => 'feature', 'description' => 'Módulo de gestão de funcionalidades do sistema'],
            ['name' => 'usuarios', 'module' => 'usuario', 'description' => 'Módulo de gestão de usuários'],
            ['name' => 'roles', 'module' => 'role', 'description' => 'Módulo de gestão de papéis e grupos de acesso'],
            ['name' => 'permissoes', 'module' => 'permissao', 'description' => 'Módulo de gestão de permissões'],
        ];

        foreach ($features as $feature) {
            // Caso não tenha um Model App\Models\Feature configurado, você pode usar DB::table('features')->updateOrInsert(...)
            Feature::updateOrCreate(
                ['name' => $feature['name']],
                [
                    'module' => $feature['module'],
                    'description' => $feature['description'],
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedPermissions(): void
    {
        $permissions = [
            // Dashboard
            ['name' => 'dashboard.visualizar', 'module' => 'dashboard', 'description' => 'Visualizar cards e estatísticas do dashboard'],
            
            // Estudantes
            ['name' => 'estudante.listar', 'module' => 'estudante', 'description' => 'Listagem de estudantes'],
            ['name' => 'estudante.criar', 'module' => 'estudante', 'description' => 'Adição de estudantes'],
            ['name' => 'estudante.editar', 'module' => 'estudante', 'description' => 'Edição de estudantes'],
            ['name' => 'estudante.excluir', 'module' => 'estudante', 'description' => 'Exclusão de estudantes'],
            ['name' => 'estudante.visualizar', 'module' => 'estudante', 'description' => 'Visualizar detalhes e quick view de estudantes'],
            ['name' => 'estudante.status', 'module' => 'estudante', 'description' => 'Alterar status de estudantes'],
            
            // Features
            ['name' => 'feature.listar', 'module' => 'feature', 'description' => 'Listagem de features'],
            ['name' => 'feature.criar', 'module' => 'feature', 'description' => 'Adição de features'],
            ['name' => 'feature.editar', 'module' => 'feature', 'description' => 'Edição de features'],
            ['name' => 'feature.excluir', 'module' => 'feature', 'description' => 'Exclusão de features'],
            ['name' => 'feature.visualizar', 'module' => 'feature', 'description' => 'Visualizar detalhes de features'],
            ['name' => 'feature.status', 'module' => 'feature', 'description' => 'Alterar status de features'],
            
            // Turnos (Complementos)
            ['name' => 'turno.visualizar', 'module' => 'turno', 'description' => 'Visualizar detalhes com cursos no turno'],
            ['name' => 'turno.status', 'module' => 'turno', 'description' => 'Alterar status do turno'],
            
            // Unidades (Complementos)
            ['name' => 'unidade.visualizar', 'module' => 'unidade', 'description' => 'Quick view e detalhes de unidades'],
            ['name' => 'unidade.status', 'module' => 'unidade', 'description' => 'Alterar status da unidade'],
            
            // Usuários (Complementos)
            ['name' => 'usuario.visualizar', 'module' => 'usuario', 'description' => 'Quick view e detalhes de usuários'],
            ['name' => 'usuario.permissoes', 'module' => 'usuario', 'description' => 'Gerenciar permissões específicas e diretas do usuário'],
            ['name' => 'usuario.status', 'module' => 'usuario', 'description' => 'Alterar status do usuário'],
            
            // Roles (Complementos)
            ['name' => 'role.permissoes', 'module' => 'role', 'description' => 'Gerenciar permissões vinculadas ao grupo/role'],
            ['name' => 'role.status', 'module' => 'role', 'description' => 'Alterar status do grupo/role'],
            
            // Permissões (Complementos)
            ['name' => 'permissao.editar', 'module' => 'permissao', 'description' => 'Edição de permissões'],
            ['name' => 'permissao.visualizar', 'module' => 'permissao', 'description' => 'Visualizar detalhes com usuários e roles que possuem a permissão'],
            ['name' => 'permissao.status', 'module' => 'permissao', 'description' => 'Alterar status da permissão'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                [
                    'module' => $permission['module'],
                    'description' => $permission['description']
                ]
            );
        }
    }
}