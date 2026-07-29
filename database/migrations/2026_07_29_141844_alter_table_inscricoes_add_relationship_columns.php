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
        Schema::table('inscricoes', function (Blueprint $table) {
            // Adiciona as chaves estrangeiras vinculando a inscrição a uma unidade e a um curso
            $table->foreignId('unidade_id')->nullable()->constrained('unidades')->nullOnDelete();
            $table->foreignId('curso_id')->nullable()->constrained('cursos')->nullOnDelete();
            $table->foreignId('turno_id')->nullable()->constrained('turnos')->nullOnDelete();
            $table->foreignId('status_inscricao_id')->nullable()->constrained('status_inscricoes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inscricoes', function (Blueprint $table) {
            $table->dropForeign(['unidade_id']);
            $table->dropForeign(['curso_id']);
            $table->dropForeign(['turno_id']);
            $table->dropForeign(['status_inscricao_id']);
            $table->dropColumn(['unidade_id', 'curso_id', 'turno_id', 'status_inscricao_id']);
        });
    }
};
