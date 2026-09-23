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

    public function handle(): void
    {
        // Varrer a tabela pivô e apagar fisicamente as permissões onde a data já expirou
        $deletados = DB::table('model_has_permissions')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now()->toDateString())
            ->delete();

        // Se o sistema encontrou e apagou alguma permissão, limpamos a cache
        // para que o acesso seja cortado imediatamente, mesmo que o utilizador esteja online.
        if ($deletados > 0) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
}