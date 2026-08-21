<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ofertas_vagas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ciclo_id')->constrained('ciclos')->cascadeOnDelete();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->foreignId('unidade_id')->constrained('unidades')->cascadeOnDelete();
            $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
            
            // Quantidade limite de vagas para essa combinação específica
            $table->integer('vagas')->default(0);
            
            $table->timestamps();
            
            // Garante que não teremos linhas duplicadas para a mesma oferta no mesmo ciclo
            $table->unique(['ciclo_id', 'curso_id', 'unidade_id', 'turno_id'], 'oferta_unica_vaga_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ofertas_vagas');
    }
};