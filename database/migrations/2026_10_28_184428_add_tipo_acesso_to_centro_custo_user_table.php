<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('centro_custo_user', function (Blueprint $table) {
            if (!Schema::hasColumn('centro_custo_user', 'tipo_acesso')) {
                $table->string('tipo_acesso', 20)
                    ->default('edicao')
                    ->after('user_id')
                    ->comment('Define se o vínculo com o CC é para edicao ou aprovacao');
            }
        });

        // Remove a constraint antiga (centro_custo_id, user_id) e cria a nova permitindo que o mesmo usuário
        // tenha vínculos independentes por tipo_acesso se necessário
        try {
            Schema::table('centro_custo_user', function (Blueprint $table) {
                $table->dropUnique(['centro_custo_id', 'user_id']);
            });
        } catch (\Throwable $e) {
            // Ignora caso o índice tenha outro nome ou já tenha sido removido
        }

        Schema::table('centro_custo_user', function (Blueprint $table) {
            $table->unique(['centro_custo_id', 'user_id', 'tipo_acesso'], 'cc_user_tipo_acesso_unique');
        });
    }

    public function down(): void
    {
        Schema::table('centro_custo_user', function (Blueprint $table) {
            $table->dropUnique('cc_user_tipo_acesso_unique');
            if (Schema::hasColumn('centro_custo_user', 'tipo_acesso')) {
                $table->dropColumn('tipo_acesso');
            }
            $table->unique(['centro_custo_id', 'user_id']);
        });
    }
};