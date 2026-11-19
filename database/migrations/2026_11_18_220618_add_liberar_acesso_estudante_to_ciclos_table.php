<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ciclos') && !Schema::hasColumn('ciclos', 'liberar_acesso_estudante')) {
            Schema::table('ciclos', function (Blueprint $table) {
                $table->boolean('liberar_acesso_estudante')->default(true)->after('status_documentacao_id');
            });
        }

        if (Schema::hasTable('permissions')) {
            Permission::firstOrCreate(
                ['name' => 'inscricao.edicao.solicitacao_troca', 'guard_name' => 'web']
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ciclos') && Schema::hasColumn('ciclos', 'liberar_acesso_estudante')) {
            Schema::table('ciclos', function (Blueprint $table) {
                $table->dropColumn('liberar_acesso_estudante');
            });
        }
    }
};