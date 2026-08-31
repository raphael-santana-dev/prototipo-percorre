<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Torna a coluna slug opcional na tabela cursos
        Schema::table('cursos', function (Blueprint $table) {
            $table->string('slug')->nullable()->change();
        });

        // Torna os horários opcionais na tabela turnos
        Schema::table('turnos', function (Blueprint $table) {
            $table->time('horario_inicio')->nullable()->change();
            $table->time('horario_fim')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });

        Schema::table('turnos', function (Blueprint $table) {
            $table->time('horario_inicio')->nullable(false)->change();
            $table->time('horario_fim')->nullable(false)->change();
        });
    }
};