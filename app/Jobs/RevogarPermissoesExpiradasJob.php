<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;
use App\Models\AuditoriaLog;

class RevogarPermissoesExpiradasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): array
    {
        $hoje = now()->toDateString();

        // =====================================================================
        // 1. PERMISSÕES EXTRAS TEMPORÁRIAS EXPIRADAS (model_has_permissions)
        // =====================================================================
        $permissoesExpiradas = DB::table('model_has_permissions as mhp')
            ->join('permissions as p', 'p.id', '=', 'mhp.permission_id')
            ->leftJoin('users as u', 'u.id', '=', 'mhp.model_id')
            ->whereNotNull('mhp.expires_at')
            ->whereDate('mhp.expires_at', '<', $hoje)
            ->select(
                'mhp.model_id as user_id',
                'u.name as user_name',
                'u.email as user_email',
                'p.id as permission_id',
                'p.name as permission_name',
                'mhp.expires_at'
            )
            ->get();

        $permissoesDeletadas = 0;

        if ($permissoesExpiradas->isNotEmpty()) {
            foreach ($permissoesExpiradas->groupBy('user_id') as $userId => $itens) {
                $primeiro = $itens->first();
                $this->registrarAuditoria(
                    tabela: 'model_has_permissions',
                    registroId: (int) $userId,
                    acao: 'revogacao_permissao_expirada',
                    anterior: [
                        'usuario_afetado'       => $primeiro->user_name ?? "ID #{$userId}",
                        'email_afetado'         => $primeiro->user_email ?? null,
                        'permissoes_expiradas'  => $itens->map(fn($i) => [
                            'id'         => $i->permission_id,
                            'permissao'  => $i->permission_name,
                            'expirada_em'=> $i->expires_at,
                        ])->values()->toArray(),
                    ],
                    novo: [
                        'status'     => 'Permissões avulsas revogadas automaticamente por expiração de prazo.',
                        'data_corte' => $hoje,
                    ]
                );
            }

            $permissoesDeletadas = DB::table('model_has_permissions')
                ->whereNotNull('expires_at')
                ->whereDate('expires_at', '<', $hoje)
                ->delete();

            if ($permissoesDeletadas > 0) {
                app(PermissionRegistrar::class)->forgetCachedPermissions();
            }
        }

        // =====================================================================
        // 2. VÍNCULOS ACADÊMICOS EXPIRADOS (user_vinculos_academicos)
        // =====================================================================
        $vinculosAcademicosDeletados = 0;

        if (Schema::hasTable('user_vinculos_academicos')) {
            $vinculosExpirados = DB::table('user_vinculos_academicos as uv')
                ->join('users as u', 'u.id', '=', 'uv.user_id')
                ->leftJoin('unidades as uni', 'uni.id', '=', 'uv.unidade_id')
                ->leftJoin('cursos as cur', 'cur.id', '=', 'uv.curso_id')
                ->leftJoin('turnos as tur', 'tur.id', '=', 'uv.turno_id')
                ->whereNotNull('uv.expires_at')
                ->whereDate('uv.expires_at', '<', $hoje)
                ->select(
                    'uv.id',
                    'uv.user_id',
                    'u.name as user_name',
                    'u.email as user_email',
                    'uv.unidade_id',
                    'uni.nome as unidade_nome',
                    'uv.curso_id',
                    'cur.nome as curso_nome',
                    'uv.turno_id',
                    'tur.nome as turno_nome',
                    'uv.expires_at'
                )
                ->get();

            if ($vinculosExpirados->isNotEmpty()) {
                $usuariosAfetadosIds = $vinculosExpirados->pluck('user_id')->unique()->toArray();

                $vinculosAcademicosDeletados = DB::table('user_vinculos_academicos')
                    ->whereNotNull('expires_at')
                    ->whereDate('expires_at', '<', $hoje)
                    ->delete();

                foreach ($vinculosExpirados->groupBy('user_id') as $userId => $itens) {
                    $user = User::find($userId);
                    $restantes = collect();

                    if ($user) {
                        $restantes = DB::table('user_vinculos_academicos')
                            ->where('user_id', $user->id)
                            ->where(function ($q) use ($hoje) {
                                $q->whereNull('expires_at')
                                  ->orWhereDate('expires_at', '>=', $hoje);
                            })
                            ->get(['unidade_id', 'curso_id', 'turno_id', 'expires_at']);

                        if ($restantes->isNotEmpty()) {
                            $user->unidades()->sync($restantes->pluck('unidade_id')->unique()->values()->toArray());
                            $user->cursos()->sync($restantes->pluck('curso_id')->unique()->values()->toArray());
                            $user->turnos()->sync($restantes->pluck('turno_id')->unique()->values()->toArray());
                        } else {
                            $user->unidades()->detach();
                            $user->cursos()->detach();
                            $user->turnos()->detach();
                        }
                    }

                    $primeiro = $itens->first();
                    $this->registrarAuditoria(
                        tabela: 'user_vinculos_academicos',
                        registroId: (int) $userId,
                        acao: 'revogacao_vinculo_academico_expirado',
                        anterior: [
                            'usuario_afetado'    => $primeiro->user_name ?? "ID #{$userId}",
                            'email_afetado'      => $primeiro->user_email ?? null,
                            'vinculos_removidos' => $itens->map(fn($i) => [
                                'unidade'     => $i->unidade_nome ?? "ID #{$i->unidade_id}",
                                'curso'       => $i->curso_nome ?? "ID #{$i->curso_id}",
                                'turno'       => $i->turno_nome ?? "ID #{$i->turno_id}",
                                'expirado_em' => $i->expires_at,
                            ])->values()->toArray(),
                        ],
                        novo: [
                            'status'                   => 'Vínculo(s) acadêmico(s) desfeito(s) automaticamente por vencimento do prazo.',
                            'vinculos_ativos_restantes'=> $restantes->count(),
                            'data_corte'               => $hoje,
                        ]
                    );
                }
            }
        }

        // =====================================================================
        // 3. CENTROS DE CUSTO EXPIRADOS (centro_custo_user)
        // =====================================================================
        $centrosCustoDeletados = 0;

        if (Schema::hasTable('centro_custo_user')) {
            $ccsExpirados = DB::table('centro_custo_user as ccu')
                ->join('users as u', 'u.id', '=', 'ccu.user_id')
                ->leftJoin('centros_custo as cc', 'cc.id', '=', 'ccu.centro_custo_id')
                ->whereNotNull('ccu.expires_at')
                ->whereDate('ccu.expires_at', '<', $hoje)
                ->select(
                    'ccu.user_id',
                    'u.name as user_name',
                    'u.email as user_email',
                    'cc.codigo as cc_codigo',
                    'cc.nome as cc_nome',
                    'ccu.tipo_acesso',
                    'ccu.expires_at'
                )
                ->get();

            if ($ccsExpirados->isNotEmpty()) {
                foreach ($ccsExpirados->groupBy('user_id') as $userId => $itens) {
                    $primeiro = $itens->first();
                    $this->registrarAuditoria(
                        tabela: 'centro_custo_user',
                        registroId: (int) $userId,
                        acao: 'revogacao_centro_custo_expirado',
                        anterior: [
                            'usuario_afetado'         => $primeiro->user_name ?? "ID #{$userId}",
                            'email_afetado'           => $primeiro->user_email ?? null,
                            'centros_custo_removidos' => $itens->map(fn($i) => [
                                'codigo'      => $i->cc_codigo,
                                'nome'        => $i->cc_nome,
                                'tipo_acesso' => $i->tipo_acesso,
                                'expirado_em' => $i->expires_at,
                            ])->values()->toArray(),
                        ],
                        novo: [
                            'status'     => 'Vínculo(s) de Centro de Custo revogado(s) automaticamente por expiração.',
                            'data_corte' => $hoje,
                        ]
                    );
                }

                $centrosCustoDeletados = DB::table('centro_custo_user')
                    ->whereNotNull('expires_at')
                    ->whereDate('expires_at', '<', $hoje)
                    ->delete();
            }
        }

        return [
            'permissoes_revogadas'          => $permissoesDeletadas,
            'vinculos_academicos_revogados' => $vinculosAcademicosDeletados,
            'centros_custo_revogados'       => $centrosCustoDeletados,
        ];
    }

    private function registrarAuditoria(string $tabela, ?int $registroId, string $acao, array $anterior, array $novo): void
    {
        if (!class_exists(AuditoriaLog::class)) {
            return;
        }

        try {
            $usuarioLogado = auth()->user();

            AuditoriaLog::create([
                'tabela_alterada'     => $tabela,
                'registro_id'         => $registroId,
                'acao'                => $acao,
                'informacao_anterior' => $anterior,
                'nova_informacao'     => $novo,
                'usuario_id'          => $usuarioLogado?->id,
                'usuario_nome'        => $usuarioLogado?->name ?? 'Rotina Automática (Scheduler)',
                'usuario_role'        => ($usuarioLogado && method_exists($usuarioLogado, 'getRoleNames'))
                    ? ($usuarioLogado->getRoleNames()->first() ?? 'sistema')
                    : 'sistema',
                'usuario_login'       => $usuarioLogado?->email ?? 'scheduler@percorre.com',
                'ip'                  => request()?->ip() ?? '127.0.0.1',
                'navegador'           => request()?->userAgent() ?? 'Laravel Artisan Scheduler',
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Falha ao gravar AuditoriaLog na revogação de expirados: ' . $e->getMessage());
        }
    }
}