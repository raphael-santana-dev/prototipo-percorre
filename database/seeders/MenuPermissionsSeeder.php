<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\ACL\Domain\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class MenuPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissoes = [
            [
                'module'      => 'dashboard',
                'submodule'   => null,
                'action'      => 'acessar',
                'description' => 'Acessar o painel principal',
            ],
            [
                'module'      => 'inscricao',
                'submodule'   => 'crm',
                'action'      => 'acessar',
                'description' => 'Acessar o quadro Kanban (CRM) de inscrições',
            ],
            [
                'module'      => 'matricula',
                'submodule'   => 'acompanhamento',
                'action'      => 'listar',
                'description' => 'Acompanhar validações de documentos (Portal IA)',
            ],
            [
                'module'      => 'matricula',
                'submodule'   => 'ia',
                'action'      => 'configurar',
                'description' => 'Configurar regras de IA para validação de matrículas',
            ],
            [
                'module'      => 'solicitacao',
                'submodule'   => null,
                'action'      => 'listar',
                'description' => 'Listar e gerenciar solicitações',
            ],
            [
                'module'      => 'comunicacao',
                'submodule'   => 'dashboard',
                'action'      => 'acessar',
                'description' => 'Acessar o dashboard de comunicação e automação',
            ],
            [
                'module'      => 'comunicacao',
                'submodule'   => 'esteira',
                'action'      => 'listar',
                'description' => 'Listar e gerenciar esteiras de disparo',
            ],
            [
                'module'      => 'comunicacao',
                'submodule'   => 'suri_monitor',
                'action'      => 'acessar',
                'description' => 'Acessar o monitor de webhooks da Suri',
            ],
            [
                'module'      => 'comunicacao',
                'submodule'   => 'suri_lab',
                'action'      => 'acessar',
                'description' => 'Acessar o laboratório de testes da Suri',
            ],
            [
                'module'      => 'avaliacao',
                'submodule'   => null,
                'action'      => 'listar',
                'description' => 'Listar avaliações educacionais',
            ],
            [
                'module'      => 'sistema',
                'submodule'   => 'erros',
                'action'      => 'listar',
                'description' => 'Visualizar a central de erros do sistema',
            ],
            [
                'module'      => 'dev',
                'submodule'   => 'rd_crm',
                'action'      => 'acessar',
                'description' => 'Acessar a tela de testes do RD CRM',
            ],
            [
                'module'      => 'dev',
                'submodule'   => 'design_system',
                'action'      => 'acessar',
                'description' => 'Acessar o visualizador do Design System',
            ],
            ['module' => 'escola', 'submodule' => null, 'action' => 'listar',  'description' => 'Listar escolas cadastradas'],
            ['module' => 'escola', 'submodule' => null, 'action' => 'criar',   'description' => 'Cadastrar novas escolas'],
            ['module' => 'escola', 'submodule' => null, 'action' => 'editar',  'description' => 'Editar escolas cadastradas'],
            ['module' => 'escola', 'submodule' => null, 'action' => 'excluir', 'description' => 'Excluir escolas cadastradas'],
        ];

        $nomesCriados = [];

        foreach ($permissoes as $item) {
            $modulo    = strtolower(trim($item['module']));
            $submodulo = $item['submodule'] ? strtolower(trim($item['submodule'])) : null;
            $acao      = strtolower(trim($item['action']));

            $fullName     = implode('.', array_filter([$modulo, $submodulo, $acao]));
            $moduleColumn = implode('.', array_filter([$modulo, $submodulo]));

            Permission::updateOrCreate(
                [
                    'name'       => $fullName,
                    'guard_name' => 'web',
                ],
                [
                    'module'      => $moduleColumn,
                    'submodule'   => $submodulo,
                    'description' => $item['description'],
                ]
            );

            $nomesCriados[] = $fullName;
        }

        // Vincula automaticamente as novas permissões ao perfil dev (se existir)
        $roleDev = Role::where('name', 'dev')->where('guard_name', 'web')->first();
        if ($roleDev) {
            $roleDev->givePermissionTo($nomesCriados);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}