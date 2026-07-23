<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\FeatureToggle\Domain\Models\Feature;
use App\Modules\ACL\Domain\Models\Permission;
use Spatie\Permission\Models\Role;

class SystemBaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---------------------------------------------------
        // 1. SEMEANDO AS FEATURE TOGGLES
        // ---------------------------------------------------
        $features = [
            ['module' => 'sistema', 'name' => 'sistema.tema', 'description' => 'Libera o botão de troca de tema Dark/Light Mode', 'is_active' => true],
            ['module' => 'alunos', 'name' => 'alunos.biblioteca', 'description' => 'Libera o acesso à biblioteca virtual para estudantes', 'is_active' => true],
            ['module' => 'turno', 'name' => 'turno.turno', 'description' => 'Módulo completo de gestão de turnos', 'is_active' => true],
            ['module' => 'unidade', 'name' => 'unidade.unidade', 'description' => 'Módulo de gestão e detalhes das unidades físicas', 'is_active' => true],
        ];

        foreach ($features as $feature) {
            Feature::firstOrCreate(
                ['name' => $feature['name']],
                [
                    'module' => $feature['module'], 
                    'description' => $feature['description'], 
                    'is_active' => $feature['is_active']
                ]
            );
        }

        // ---------------------------------------------------
        // 2. SEMEANDO AS PERMISSÕES (SPATIE)
        // ---------------------------------------------------
        $permissions = [
            // Módulo Role
            ['module' => 'role', 'name' => 'role.listar', 'description' => 'Visualiza a tabela de grupos'],
            ['module' => 'role', 'name' => 'role.criar', 'description' => 'Cria novos grupos de acesso'],
            ['module' => 'role', 'name' => 'role.editar', 'description' => 'Altera nomes de grupos existentes'],
            ['module' => 'role', 'name' => 'role.excluir', 'description' => 'Deleta grupos (exceto base)'],
            // Módulo Permissão
            ['module' => 'permissao', 'name' => 'permissao.listar', 'description' => 'Visualiza as permissões'],
            ['module' => 'permissao', 'name' => 'permissao.criar', 'description' => 'Cadastra novas permissões no sistema'],
            ['module' => 'permissao', 'name' => 'permissao.excluir', 'description' => 'Deleta permissões do sistema'],
            // Módulo Usuário
            ['module' => 'usuario', 'name' => 'usuario.listar', 'description' => 'Acesso ao painel corporativo'],
            ['module' => 'usuario', 'name' => 'usuario.criar', 'description' => 'Criação de novos usuários corporativos'],
            ['module' => 'usuario', 'name' => 'usuario.editar', 'description' => 'Edição e troca de senha de usuários'],
            ['module' => 'usuario', 'name' => 'usuario.excluir', 'description' => 'Exclusão de usuários (exceto dev e logado)'],
            // Módulo Turno
            ['module' => 'turno', 'name' => 'turno.listar', 'description' => 'Visualiza a tabela de turnos'],
            ['module' => 'turno', 'name' => 'turno.criar', 'description' => 'Abre modal para novo turno'],
            ['module' => 'turno', 'name' => 'turno.editar', 'description' => 'Abre modal de edição do turno'],
            ['module' => 'turno', 'name' => 'turno.excluir', 'description' => 'Deleta um turno do banco'],
            // Módulo Unidade
            ['module' => 'unidade', 'name' => 'unidade.listar', 'description' => 'Acessa a listagem e os detalhes da unidade'],
            ['module' => 'unidade', 'name' => 'unidade.criar', 'description' => 'Cadastra uma nova sede/unidade'],
            ['module' => 'unidade', 'name' => 'unidade.editar', 'description' => 'Edita os dados cadastrais da unidade'],
            ['module' => 'unidade', 'name' => 'unidade.excluir', 'description' => 'Inativa/exclui uma unidade'],
            ['module' => 'unidade', 'name' => 'unidade.visualizar.todas', 'description' => 'Bypass do isolamento: permite ver alunos e dados de TODAS as unidades'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'], 
                    'description' => $perm['description']
                ]
            );
        }

        // ---------------------------------------------------
        // 3. SEMEANDO ROLES BÁSICAS
        // ---------------------------------------------------
        Role::firstOrCreate(['name' => 'dev', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $professor = Role::firstOrCreate(['name' => 'professor', 'guard_name' => 'web']);
        
        // Exemplo: O Admin recebe automaticamente a permissão de ver todas as unidades ao rodar o seeder
        $admin->givePermissionTo('unidade.visualizar.todas');
    }
}