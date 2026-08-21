<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ciclo_unidade', function (Blueprint $table) {
            $table->foreignId('ciclo_id')->constrained('ciclos')->cascadeOnDelete();
            $table->foreignId('unidade_id')->constrained('unidades')->cascadeOnDelete();
            $table->primary(['ciclo_id', 'unidade_id']);
        });

        Schema::create('ciclo_turno', function (Blueprint $table) {
            $table->foreignId('ciclo_id')->constrained('ciclos')->cascadeOnDelete();
            $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
            $table->primary(['ciclo_id', 'turno_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ciclo_turno');
        Schema::dropIfExists('ciclo_unidade');
    }
};