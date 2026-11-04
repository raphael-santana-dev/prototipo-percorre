<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formulario_fases', function (Blueprint $table) {
            if (!Schema::hasColumn('formulario_fases', 'notificacao_enviada_em')) {
                $table->timestamp('notificacao_enviada_em')->nullable()->after('email_template_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('formulario_fases', function (Blueprint $table) {
            if (Schema::hasColumn('formulario_fases', 'notificacao_enviada_em')) {
                $table->dropColumn('notificacao_enviada_em');
            }
        });
    }
};