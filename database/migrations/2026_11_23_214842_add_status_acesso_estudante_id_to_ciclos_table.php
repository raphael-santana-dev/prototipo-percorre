<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ciclos')) {
            Schema::table('ciclos', function (Blueprint $table) {
                if (!Schema::hasColumn('ciclos', 'liberar_acesso_estudante')) {
                    $table->boolean('liberar_acesso_estudante')->default(false)->after('status_documentacao_id');
                }
                if (!Schema::hasColumn('ciclos', 'status_acesso_estudante_id')) {
                    $table->unsignedBigInteger('status_acesso_estudante_id')->nullable()->after('liberar_acesso_estudante');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ciclos') && Schema::hasColumn('ciclos', 'status_acesso_estudante_id')) {
            Schema::table('ciclos', function (Blueprint $table) {
                $table->dropColumn('status_acesso_estudante_id');
            });
        }
    }
};