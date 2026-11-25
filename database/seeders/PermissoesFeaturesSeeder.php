<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Modules\ACL\Domain\Models\Permission;
use App\Modules\FeatureToggle\Domain\Models\Feature;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissoesFeaturesSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $estrutura = [
            'principal' => [
                'dashboard' => ['visualizar'],
            ],
            'financeiro' => [
                'orcamento'    => ['listar', 'visualizar', 'editar', 'aprovar', 'sincronizar'],
                'centro_custo' => ['listar', 'sincronizar'],
                'natureza'     => ['listar', 'sincronizar'],
            ],
            'inscricao' => [
                'ciclo' => [
                    'listar', 'visualizar', 'selecionar', 'editar', 'criar', 'status',
                    'bloquear', 'encerrar', 'incorporar', 'duplicar', 'excluir',
                    'regras', 'construir', 'detalhar',
                ],
                'inscritos' => [
                    'listar', 'visualizar', 'editar', 'criar', 'excluir', 'registrar_contato',
                    'visualizar_laudo', 'status', 'matricular', 'selecionar', 'visualizar_comunicados',
                    'retomar', 'enviar_docs', 'baixar_ficha', 'avancar', 'comunicar_retomada',
                    'comunicar', 'detalhar', 'legado', 'criar_acesso', 'exportar', 'validar_docs',
                ],
            ],
            'ia' => [
                'configuracao' => [null],
            ],
            'secretaria' => [
                'alunos'           => ['criar_acesso', 'status', 'matricular', 'detalhar', 'visualizar', 'editar', 'excluir', 'aprendizagem'],
                'solicitacoes'     => ['listar', 'visualizar', 'aprovar'],
                'status_inscricao' => ['listar', 'status', 'criar', 'editar', 'excluir'],
            ],
            'instituicao' => [
                'unidades'    => ['listar', 'status', 'criar', 'editar', 'excluir', 'detalhar', 'visualizar'],
                'cursos'      => ['listar', 'status', 'criar', 'editar', 'excluir', 'detalhar', 'visualizar'],
                'turnos'      => ['listar', 'status', 'criar', 'editar', 'excluir'],
                'formularios' => ['listar', 'status', 'criar', 'editar', 'excluir', 'incorporar', 'duplicar', 'respostas', 'construir', 'bloquear', 'exportar'],
                'formbuilder' => [
                    'acessar', 'criar_form_ciclo', 'criar_form_geral', 'criar_form_aprendizagem',
                    'respostas_form_ciclo', 'vincular_form_ciclo', 'publicar_form_ciclo', 'excluir_form_ciclo',
                    'editar_form_ciclo', 'editar_form_geral', 'editar_form_aprendizagem',
                    'respostas_form_geral', 'vincular_form_geral', 'publicar_form_geral', 'excluir_form_geral',
                    'respostas_form_aprendizagem', 'vincular_form_aprendizagem', 'publicar_form_aprendizagem',
                    'excluir_form_aprendizagem', 'construir_form_aprendizagem', 'construir_form_geral',
                ],
                'escolas' => ['listar', 'cadastrar', 'editar', 'excluir', 'detalhar', 'visualizar', 'status'],
            ],
            'comunicacao' => [
                'dashboard'        => ['visualizar'],
                'comunicados'      => ['listar', 'cadastrar', 'editar', 'excluir', 'detalhar', 'cancelar'],
                'esteira_disparo'  => ['listar', 'visualizar', 'detalhar', 'excluir', 'reenviar', 'adiantar_processamento', 'pausar'],
                'automacao'        => ['listar', 'status', 'detalhar', 'editar', 'criar', 'excluir'],
                'templates'        => ['criar', 'editar', 'listar', 'excluir', 'status', 'visualizar', 'inspecionar'],
                'laboratorio_suri' => ['acessar'],
                'monitor_emails'   => ['listar', 'visualizar', 'inspecionar', 'erro'],
                'webhook_suri'     => ['acessar'],
            ],
            'educacional' => [
                'matricula'    => ['acessar'],
                'turmas'       => ['acessar'],
                'avaliacoes'   => ['acessar'],
                'aprendizagem' => ['acessar'],
            ],
            'administracao' => [
                'configuracao'   => ['acessar'],
                'usuarios'       => ['listar', 'criar', 'visualizar', 'detalhar', 'primeiro_acesso', 'editar', 'excluir', 'status'],
                'perfis'         => ['listar', 'criar', 'editar', 'excluir'],
                'permissoes'     => ['listar', 'criar', 'editar', 'excluir'],
                'features'       => ['listar', 'criar', 'editar', 'excluir', 'status'],
                'auditoria'      => ['listar', 'visualizar'],
                'background'     => ['excluir'],
                'hub_integracao' => ['acessar'],
                'central_erros'  => ['acessar'],
                'teste_crm'      => ['acessar'],
                'design_system' => ['acessar'],
            ],
        ];

        $permTemSubmodule    = Schema::hasColumn('permissions', 'submodule');
        $featureTemSubmodule = Schema::hasColumn('features', 'submodule');
        $chavesCriadas       = [];

        foreach ($estrutura as $modulo => $submodulos) {
            foreach ($submodulos as $submodulo => $acoes) {
                foreach ($acoes as $acao) {
                    $mod = strtolower(trim(Str::ascii($modulo)));
                    $sub = strtolower(trim(Str::ascii($submodulo)));
                    $act = $acao !== null ? strtolower(trim(Str::ascii($acao))) : null;

                    $fullName     = implode('.', array_filter([$mod, $sub, $act]));
                    $moduleColumn = $act ? "{$mod}.{$sub}" : $mod;
                    $subColumn    = $act ? $sub : null;

                    $descricao = ucfirst(str_replace('_', ' ', $act ?: $sub))
                        . ' em ' . ucfirst(str_replace('_', ' ', $sub))
                        . ' (' . ucfirst(str_replace('_', ' ', $mod)) . ')';

                    $dadosPerm = [
                        'module'      => $moduleColumn,
                        'description' => $descricao,
                    ];
                    if ($permTemSubmodule) {
                        $dadosPerm['submodule'] = $subColumn;
                    }

                    Permission::updateOrCreate(
                        ['name' => $fullName, 'guard_name' => 'web'],
                        $dadosPerm
                    );

                    $dadosFeature = [
                        'module'      => $moduleColumn,
                        'is_active'   => true,
                        'description' => $descricao,
                    ];
                    if ($featureTemSubmodule) {
                        $dadosFeature['submodule'] = $subColumn;
                    }

                    Feature::updateOrCreate(
                        ['name' => $fullName],
                        $dadosFeature
                    );

                    Cache::forget("feature_status_{$fullName}");
                    $chavesCriadas[] = $fullName;
                }
            }
        }

        $roleDev = Role::where('name', 'dev')->where('guard_name', 'web')->first();
        if ($roleDev) {
            $roleDev->givePermissionTo($chavesCriadas);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}