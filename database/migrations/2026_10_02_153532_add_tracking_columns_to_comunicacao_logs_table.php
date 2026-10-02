<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('comunicacao_logs', function (Blueprint $table) {
            $table->string('template_nome')->nullable()->after('assunto');
            $table->string('gatilho')->nullable()->after('origem');
            $table->string('usuario_nome')->nullable()->after('gatilho');
        });
    }

    public function down(): void
    {
        Schema::table('comunicacao_logs', function (Blueprint $table) {
            $table->dropColumn(['template_nome', 'gatilho', 'usuario_nome']);
        });
    }
};