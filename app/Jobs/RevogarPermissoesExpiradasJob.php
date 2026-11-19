<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class RevogarPermissoesExpiradasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): array
    {
        $hoje = now()->toDateString();

        // 1. Revoga permissões extras temporárias vencidas
        $permissoesDeletadas = DB::table('model_has_permissions')
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<', $hoje)
            ->delete();

        if ($permissoesDeletadas > 0) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        // 2. Revoga combinações de vínculos acadêmicos (Unidade + Curso + Turno) vencidas
        $vinculosAcademicosDeletados = 0;
        if (\Illuminate\Support\Facades\Schema::hasTable('user_vinculos_academicos')) {
            $usuariosAfetadosIds = DB::table('user_vinculos_academicos')
                ->whereNotNull('expires_at')
                ->whereDate('expires_at', '<', $hoje)
                ->pluck('user_id')
                ->unique()
                ->toArray();

            if (!empty($usuariosAfetadosIds)) {
                $vinculosAcademicosDeletados = DB::table('user_vinculos_academicos')
                    ->whereNotNull('expires_at')
                    ->whereDate('expires_at', '<', $hoje)
                    ->delete();

                // Ressincroniza as tabelas auxiliares (unidade_user, curso_user, turno_user) para cada usuário afetado
                foreach ($usuariosAfetadosIds as $uId) {
                    $user = \App\Models\User::find($uId);
                    if (!$user) {
                        continue;
                    }

                    $restantes = DB::table('user_vinculos_academicos')
                        ->where('user_id', $user->id)
                        ->where(function ($q) use ($hoje) {
                            $q->whereNull('expires_at')
                              ->orWhereDate('expires_at', '>=', $hoje);
                        })
                        ->get(['unidade_id', 'curso_id', 'turno_id']);

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
            }
        }

        // 3. Revoga vínculos temporários de Centros de Custo (Edição / Aprovação) vencidos
        $centrosCustoDeletados = 0;
        if (\Illuminate\Support\Facades\Schema::hasTable('centro_custo_user')) {
            $centrosCustoDeletados = DB::table('centro_custo_user')
                ->whereNotNull('expires_at')
                ->whereDate('expires_at', '<', $hoje)
                ->delete();
        }

        return [
            'permissoes_revogadas'          => $permissoesDeletadas,
            'vinculos_academicos_revogados' => $vinculosAcademicosDeletados,
            'centros_custo_revogados'       => $centrosCustoDeletados,
        ];
    }
}