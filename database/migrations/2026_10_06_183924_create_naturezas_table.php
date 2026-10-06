<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('naturezas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique()->comment('Código da Natureza no Protheus');
            $table->string('descricao')->comment('Descrição da Natureza');
            $table->boolean('disponivel_orcamento')->default(true)->comment('Flag para exibir na plataforma web');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('naturezas');
    }
};