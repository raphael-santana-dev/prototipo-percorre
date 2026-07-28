<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Unidades
        Schema::create('unidades', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('status')->default('Ativa');
            $table->date('data_inauguracao')->nullable();
            $table->string('endereco');
            $table->string('email')->nullable();
            $table->string('telefone')->nullable();
            $table->string('foto_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Cursos
        Schema::create('cursos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('slug')->unique();
            $table->string('status')->default('Ativo');
            $table->json('turnos')->nullable(); // Mantido por retrocompatibilidade
            $table->integer('min_idade')->nullable();
            $table->integer('max_idade')->nullable();
            $table->boolean('permite_estado_diferente')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Status de Inscrição
        Schema::create('status_inscricoes', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('descricao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_inscricoes');
        Schema::dropIfExists('cursos');
        Schema::dropIfExists('unidades');
    }
};