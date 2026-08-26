<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Modules\FeatureToggle\Domain\Models\Feature;

class SystemPermissionsAndFeaturesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Limpa o cache do Spatie antes de inserir novas permissões
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Lista unificada e limpa (sem duplicidades)
        $recursos = [
            'acl.permissao.listar',
            'acl.permissao.criar',
            'acl.permissao.editar',
            'acl.permissao.excluir',
            'acl.role.listar',
            'acl.role.criar',
            'acl.role.editar',
            'acl.role.excluir',
            'acl.role.permissoes',
            'usuario.listar',
            'usuario.criar',
            'usuario.editar',
            'usuario.excluir',
            'usuario.visualizar',
            'usuario.permissoes_extras',
            'ferramenta.mock',
            'unidade.listar',
            'unidade.criar',
            'unidade.editar',
            'unidade.excluir',
            'unidade.visualizar',
            'turno.listar',
            'turno.criar',
            'turno.editar',
            'turno.excluir',
            'avaliacao.listar',
            'avaliacao.responder',
            'relatorio.acessar',
            'relatorio.exportar',
            'automacao.listar',
            'automacao.criar',
            'automacao.editar',
            'automacao.excluir',
            'automacao.visualizar',
            'comunicado.listar',
            'comunicado.criar',
            'comunicado.excluir',
            'email_log.listar',
            'template.listar',
            'template.criar',
            'template.editar',
            'template.excluir',
            'etapa.listar',
            'etapa.criar',
            'etapa.editar',
            'etapa.excluir',
            'ciclo.regras',
            'ciclo.visualizar',
            'ciclo.editar',
            'ciclo.criar',
            'ciclo.excluir',
            'ciclo.listar',
            'inscricao.listar',
            'inscricao.visualizar',
            'inscricao.editar',
            'inscricao.excluir',
            'crm.acessar',
            'status.listar',
            'status.criar',
            'status.editar',
            'status.excluir',
            'formulario.listar',
            'formulario.criar',
            'formulario.editar',
            'formulario.excluir',
            'formulario.respostas',
            'formulario.detalhes',
            'importacao.acessar',
            'importacao.exportar',
            'turma.listar',
            'turma.criar',
            'turma.editar',
            'turma.excluir',
            'matricula.listar',
            'matricula.criar',
            'matricula.editar',
            'matricula.excluir',
            'periodo_avaliacao.listar',
            'periodo_avaliacao.criar',
            'periodo_avaliacao.editar',
            'periodo_avaliacao.excluir',
            'estudante.listar',
            'estudante.criar',
            'estudante.editar',
            'estudante.excluir',
            'estudante.visualizar',
            'estudante.perfil',
            'auditoria.listar',
            'auditoria.visualizar',
            'curso.criar',
            'curso.editar',
            'curso.excluir',
            'curso.listar',
            'curso.visualizar'
        ];

        // Remover qualquer duplicidade acidental no array processado
        $recursos = array_unique($recursos);

        foreach ($recursos as $recurso) {
            // Extrai o nome do módulo dinamicamente (ex: 'usuario.listar' -> 'usuario')
            $partes = explode('.', $recurso);
            array_pop($partes); // Remove a última parte (ação)
            $modulo = implode('.', $partes); // Junta o restante para formar o módulo

            // 3. Cadastra a Feature (1:1 com a permissão)
            Feature::updateOrCreate(
                ['name' => $recurso],
                [
                    'module' => $modulo,
                    'description' => 'Controle da funcionalidade: ' . $recurso,
                    'is_active' => true,
                ]
            );

            // 4. Cadastra a Permissão (Spatie)
            Permission::updateOrCreate(
                ['name' => $recurso, 'guard_name' => 'web'],
                [
                    'module' => $modulo,
                    'description' => 'Acesso ao recurso: ' . $recurso
                ]
            );
        }

        // 5. Garantia Operacional: Criar o papel de 'dev' e atribuir todas as permissões
        $devRole = Role::firstOrCreate(['name' => 'dev', 'guard_name' => 'web']);
        $devRole->syncPermissions(Permission::all());
    }
}