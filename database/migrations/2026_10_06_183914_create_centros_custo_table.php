<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('centros_custo', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique()->comment('Código do Centro de Custo no Protheus');
            $table->string('nome')->comment('Nome/Descrição do Centro de Custo');
            $table->boolean('disponivel_orcamento')->default(true)->comment('Flag para exibir na plataforma web');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('centros_custo');
    }
};