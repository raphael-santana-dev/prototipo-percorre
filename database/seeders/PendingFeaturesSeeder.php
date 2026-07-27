<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Modules\FeatureToggle\Domain\Models\Feature; 

class PendingFeaturesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedParentModules();
        $this->syncPermissionsToFeatures();
    }

    /**
     * Cadastra os módulos principais (pai) como features.
     * Útil para ativar/desativar telas inteiras ou grupos no menu lateral.
     */
    private function seedParentModules(): void
    {
        $parentModules = [
            ['name' => 'dashboard', 'module' => 'dashboard', 'description' => 'Módulo de dashboard e estatísticas'],
            ['name' => 'estudantes', 'module' => 'estudante', 'description' => 'Módulo de gestão de estudantes'],
            ['name' => 'features', 'module' => 'feature', 'description' => 'Módulo de gestão de funcionalidades do sistema'],
            ['name' => 'usuarios', 'module' => 'usuario', 'description' => 'Módulo de gestão de usuários'],
            ['name' => 'roles', 'module' => 'role', 'description' => 'Módulo de gestão de papéis e grupos de acesso'],
            ['name' => 'permissoes', 'module' => 'permissao', 'description' => 'Módulo de gestão de permissões'],
            ['name' => 'turnos', 'module' => 'turno', 'description' => 'Módulo principal de turnos'],
            ['name' => 'unidades', 'module' => 'unidade', 'description' => 'Módulo principal de unidades'],
        ];

        foreach ($parentModules as $module) {
            Feature::updateOrCreate(
                ['name' => $module['name']],
                [
                    'module' => $module['module'],
                    'description' => $module['description'],
                    'is_active' => true, // Opcional: Define que nascem ativas
                ]
            );
        }
    }

    /**
     * Varre todas as permissões do banco e garante que exista uma feature
     * com o mesmo nome para respeitar a regra de 1:1.
     */
    private function syncPermissionsToFeatures(): void
    {
        $permissions = Permission::all();

        foreach ($permissions as $permission) {
            // Se o módulo não estiver preenchido na permissão, tenta deduzir pelo prefixo (ex: 'usuario.criar' -> 'usuario')
            $moduleName = $permission->module ?? explode('.', $permission->name)[0];

            Feature::updateOrCreate(
                ['name' => $permission->name], // Chave de busca (ex: unidade.status)
                [
                    'module' => $moduleName,
                    'description' => $permission->description ?? 'Feature referente à permissão ' . $permission->name,
                    'is_active' => true, // Por padrão, a feature nasce ativa
                ]
            );
        }
    }
}