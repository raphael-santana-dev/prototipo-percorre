<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Modules\FeatureToggle\Domain\Models\Feature;
use Spatie\Permission\PermissionRegistrar;

class TarefasAclSeeder extends Seeder
{
    public function run(): void
    {
        // Limpa o cache do Spatie para evitar erros
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $replaces = [
            'tarefas.acessar' => ['novo_nome' => 'tarefas.acessar', 'modulo' => 'tarefas'],
            'tarefas.exportar' => ['novo_nome' => 'tarefas.exportar', 'modulo' => 'tarefas'],
        ];

        foreach ($replaces as $antigo => $novo) {
            // Atualiza a Feature
            $feature = Feature::where('name', $antigo)->first();
            if ($feature) {
                $feature->update([
                    'name' => $novo['novo_nome'],
                    'module' => $novo['modulo'],
                    'description' => str_replace(['Importação', 'importação', 'Importacao', 'importacao'], 'Tarefas (Background)', $feature->description)
                ]);
            }

            // Atualiza a Permissão (Spatie)
            $permission = Permission::where('name', $antigo)->where('guard_name', 'web')->first();
            if ($permission) {
                $permission->update([
                    'name' => $novo['novo_nome'],
                    'module' => $novo['modulo']
                ]);
            }
        }
    }
}