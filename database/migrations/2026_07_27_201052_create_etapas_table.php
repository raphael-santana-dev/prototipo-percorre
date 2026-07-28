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
        Schema::create('etapas', function (Blueprint $table) {
            $table->id();
            $table->integer('numero')->unique(); // O número da etapa (Ex: 1, 2, 3...)
            $table->string('nome'); // O título da etapa (Ex: 'Dados Básicos', 'Perfil Social')
            $table->string('descricao')->nullable(); // Um campo opcional para anotações internas
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etapas');
    }
};
