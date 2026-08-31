<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formularios', function (Blueprint $table) {
            $table->json('unidades_permitidas')->nullable()->after('users_permitidos');
            $table->json('cursos_permitidos')->nullable()->after('unidades_permitidas');
            $table->json('turnos_permitidas')->nullable()->after('cursos_permitidos');
            $table->boolean('exigir_email')->default(false)->after('turnos_permitidas');
        });
    }

    public function down(): void
    {
        Schema::table('formularios', function (Blueprint $table) {
            $table->dropColumn(['unidades_permitidas', 'cursos_permitidos', 'turnos_permitidas', 'exigir_email']);
        });
    }
};