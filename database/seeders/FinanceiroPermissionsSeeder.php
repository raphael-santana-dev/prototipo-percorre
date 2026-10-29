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

        // Limpeza de permissões e features legadas ou duplicadas que causavam confusão
        $permissoesLegadas = [
            'financeiro.orcamentos.global',
            'financeiro.orcamentos.avaliacao',
        ];

        Permission::whereIn('name', $permissoesLegadas)->delete();

        if (Schema::hasTable('features')) {
            $colKey = Schema::hasColumn('features', 'key') 
                ? 'key' 
                : (Schema::hasColumn('features', 'nome') ? 'nome' : 'name');

            DB::table('features')->whereIn($colKey, $permissoesLegadas)->delete();
        }

        // Mapeamento completo de permissões e features necessárias
        $permissoes = [
            // Orçamentos - Fluxo e Ações
            'financeiro.orcamentos.listar'          => ['modulo' => 'financeiro', 'descricao' => 'Listar orçamentos financeiros'],
            'financeiro.orcamentos.editar'          => ['modulo' => 'financeiro', 'descricao' => 'Elaborar e revisar orçamentos dos Centros de Custo vinculados'],
            'financeiro.orcamentos.aprovar'         => ['modulo' => 'financeiro', 'descricao' => 'Aprovar orçamentos dos Centros de Custo vinculados'],
            'financeiro.orcamentos.integrar'        => ['modulo' => 'financeiro', 'descricao' => 'Integrar orçamento aprovado ao Protheus via PUT'],
            'financeiro.orcamentos.sincronizar'     => ['modulo' => 'financeiro', 'descricao' => 'Importar e sincronizar orçamentos do Protheus'],

            // Visão Global - Orçamentos e Centros de Custo (Financeiro)
            'financeiro.visao_global'               => ['modulo' => 'financeiro', 'descricao' => 'Acesso global total a todos os Centros de Custo e Orçamentos'],
            'financeiro.visao_global.edicao'        => ['modulo' => 'financeiro', 'descricao' => 'Acesso global a todos os centros de custo para EDIÇÃO de orçamentos'],
            'financeiro.visao_global.aprovacao'     => ['modulo' => 'financeiro', 'descricao' => 'Acesso global a todos os centros de custo para APROVAÇÃO de orçamentos'],

            // Visão Global - Compatibilidade com Centros de Custo (Módulo Centros de Custo)
            'centros_custo.visao_global'            => ['modulo' => 'centros_custo', 'descricao' => 'Permissão Global de Centros de Custo (Todos os Centros de Custo)'],
            'centros_custo.visao_global.edicao'     => ['modulo' => 'centros_custo', 'descricao' => 'Permissão Global para Edição (Todos os Centros de Custo)'],
            'centros_custo.visao_global.aprovacao'  => ['modulo' => 'centros_custo', 'descricao' => 'Permissão Global para Aprovação (Todos os Centros de Custo)'],

            // Cadastros Base - Centros de Custo
            'financeiro.centro_custo.listar'        => ['modulo' => 'financeiro', 'descricao' => 'Listar centros de custo'],
            'financeiro.centro_custo.sincronizar'   => ['modulo' => 'financeiro', 'descricao' => 'Sincronizar centros de custo com o Protheus'],

            // Cadastros Base - Naturezas Financeiras
            'financeiro.natureza.listar'            => ['modulo' => 'financeiro', 'descricao' => 'Listar naturezas financeiras'],
            'financeiro.natureza.sincronizar'       => ['modulo' => 'financeiro', 'descricao' => 'Sincronizar naturezas financeiras com o Protheus'],
        ];

        foreach ($permissoes as $nome => $meta) {
            $dadosPermissao = [
                'guard_name' => 'web',
            ];

            if (Schema::hasColumn('permissions', 'module')) {
                $dadosPermissao['module'] = $meta['modulo'];
            }

            if (Schema::hasColumn('permissions', 'description')) {
                $dadosPermissao['description'] = $meta['descricao'];
            } elseif (Schema::hasColumn('permissions', 'descricao')) {
                $dadosPermissao['descricao'] = $meta['descricao'];
            }

            Permission::updateOrCreate(
                ['name' => $nome, 'guard_name' => 'web'],
                $dadosPermissao
            );

            // Registro na tabela de Features para o helper feature(...)
            if (Schema::hasTable('features')) {
                $colKey = Schema::hasColumn('features', 'key') 
                    ? 'key' 
                    : (Schema::hasColumn('features', 'nome') ? 'nome' : 'name');

                $colAtivo = Schema::hasColumn('features', 'is_active') 
                    ? 'is_active' 
                    : (Schema::hasColumn('features', 'ativo') ? 'ativo' : 'status');

                $dadosFeature = [
                    $colAtivo    => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ];

                if (Schema::hasColumn('features', 'module')) {
                    $dadosFeature['module'] = $meta['modulo'];
                }

                if (Schema::hasColumn('features', 'description')) {
                    $dadosFeature['description'] = $meta['descricao'];
                } elseif (Schema::hasColumn('features', 'descricao')) {
                    $dadosFeature['descricao'] = $meta['descricao'];
                }

                DB::table('features')->updateOrInsert(
                    [$colKey => $nome],
                    $dadosFeature
                );
            }
        }

        // Atribui todas as permissões às roles administrativas 'dev' e 'admin'
        foreach (['dev', 'admin'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo(array_keys($permissoes));
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}