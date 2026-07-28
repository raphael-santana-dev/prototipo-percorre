<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Curso x Unidade
        Schema::create('curso_unidade', function (Blueprint $table) {
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->foreignId('unidade_id')->constrained('unidades')->cascadeOnDelete();
            $table->primary(['curso_id', 'unidade_id']);
        });

        // Curso x Turno
        Schema::create('curso_turno', function (Blueprint $table) {
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
            $table->primary(['curso_id', 'turno_id']);
        });

        // Ciclo x Curso
        Schema::create('ciclo_curso', function (Blueprint $table) {
            $table->foreignId('ciclo_id')->constrained('ciclos')->cascadeOnDelete();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->primary(['ciclo_id', 'curso_id']); // Chave composta previne duplicidade
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ciclo_curso');
        Schema::dropIfExists('curso_turno');
        Schema::dropIfExists('curso_unidade');
    }
};