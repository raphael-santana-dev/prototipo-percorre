<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orcamento_avaliacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orcamento_id')->constrained('orcamentos')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id'); // Usuário que avaliou
            $table->string('user_nome'); // Nome salvo como retrato
            $table->string('status_aplicado'); // Aprovado, Reprovado, Aprovado com ressalvas
            $table->text('comentario');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamento_avaliacoes');
    }
};