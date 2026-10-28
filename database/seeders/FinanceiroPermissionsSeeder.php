<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinanceiroPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Remove a permissão e feature 'financeiro.orcamentos.global' que causava confusão
        Permission::where('name', 'financeiro.orcamentos.global')->delete();
        if (Schema::hasTable('features')) {
            $colKey = Schema::hasColumn('features', 'key') ? 'key' : (Schema::hasColumn('features', 'nome') ? 'nome' : 'name');
            DB::table('features')->where($colKey, 'financeiro.orcamentos.global')->delete();
        }

        $permissoes = [
            'financeiro.orcamentos.listar'        => 'Listar orçamentos financeiros',
            'financeiro.orcamentos.editar'        => 'Elaborar e revisar orçamentos dos Centros de Custo vinculados',
            'financeiro.orcamentos.aprovar'       => 'Aprovar orçamentos dos Centros de Custo vinculados',
            'financeiro.orcamentos.avaliacao'     => 'Avaliar e aprovar orçamentos dos Centros de Custo vinculados',
            'financeiro.orcamentos.integrar'      => 'Integrar orçamento aprovado ao Protheus',
            'financeiro.orcamentos.sincronizar'   => 'Importar/sincronizar orçamentos do Protheus',
            'financeiro.centro_custo.listar'      => 'Listar centros de custo',
            'financeiro.centro_custo.sincronizar' => 'Sincronizar centros de custo com o Protheus',
            'financeiro.natureza.listar'          => 'Listar naturezas financeiras',
            'financeiro.natureza.sincronizar'     => 'Sincronizar naturezas financeiras com o Protheus',
        ];

        foreach ($permissoes as $nome => $descricao) {
            $dados = ['guard_name' => 'web'];
            if (Schema::hasColumn('permissions', 'description')) {
                $dados['description'] = $descricao;
            } elseif (Schema::hasColumn('permissions', 'descricao')) {
                $dados['descricao'] = $descricao;
            }

            Permission::firstOrCreate(['name' => $nome, 'guard_name' => 'web'], $dados);

            if (Schema::hasTable('features')) {
                $colKey = Schema::hasColumn('features', 'key') ? 'key' : (Schema::hasColumn('features', 'nome') ? 'nome' : 'name');
                $colAtivo = Schema::hasColumn('features', 'is_active') ? 'is_active' : (Schema::hasColumn('features', 'ativo') ? 'ativo' : 'status');

                DB::table('features')->updateOrInsert(
                    [$colKey => $nome],
                    [$colAtivo => true, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }

        foreach (['dev', 'admin'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo(array_keys($permissoes));
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}