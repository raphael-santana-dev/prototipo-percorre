<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('ciclo_status_inscricao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ciclo_id')->constrained('ciclos')->cascadeOnDelete();
            $table->foreignId('status_inscricao_id')->constrained('status_inscricoes')->cascadeOnDelete();
            $table->integer('ordem')->default(0); // Define a posição da coluna no Kanban
            $table->timestamps();

            // Evita que o mesmo status seja vinculado duas vezes ao mesmo ciclo
            $table->unique(['ciclo_id', 'status_inscricao_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ciclo_status_inscricao');
    }
};
