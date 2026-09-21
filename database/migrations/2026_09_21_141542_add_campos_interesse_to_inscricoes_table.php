<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscricoes', function (Blueprint $table) {
            $table->boolean('deseja_informar')->default(false);
            $table->unsignedBigInteger('unidade_interesse_id')->nullable();
            $table->unsignedBigInteger('curso_interesse_id')->nullable();
            $table->unsignedBigInteger('turno_interesse_id')->nullable();

            $table->foreign('unidade_interesse_id')->references('id')->on('unidades')->nullOnDelete();
            $table->foreign('curso_interesse_id')->references('id')->on('cursos')->nullOnDelete();
            $table->foreign('turno_interesse_id')->references('id')->on('turnos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inscricoes', function (Blueprint $table) {
            $table->dropForeign(['unidade_interesse_id']);
            $table->dropForeign(['curso_interesse_id']);
            $table->dropForeign(['turno_interesse_id']);
            $table->dropColumn(['deseja_informar', 'unidade_interesse_id', 'curso_interesse_id', 'turno_interesse_id']);
        });
    }
};