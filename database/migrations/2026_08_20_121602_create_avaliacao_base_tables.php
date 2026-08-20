<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Critérios de Avaliação[cite: 36]
        Schema::create('criterios_avaliacao', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 6)->unique(); 
            $table->string('nome', 60); 
            $table->boolean('status')->default(true); 
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Períodos de Avaliação (Header)[cite: 37, 39]
        Schema::create('periodos_avaliacao', function (Blueprint $table) {
            $table->id();
            $table->string('ano', 4);
            $table->string('ciclo', 1);
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->char('status', 1)->default('1'); // 1=Aberto, 2=Fechado
            $table->boolean('trava_fases')->default(false); // Exige sequência[cite: 39]
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Fases do Período[cite: 37]
        Schema::create('periodo_fases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('periodos_avaliacao')->cascadeOnDelete();
            $table->string('fase', 1);
            $table->char('responsavel', 1); // 1=Aluno, 2=Professor, 3=Ambos[cite: 37]
            $table->timestamps();
        });

        // 4. Critérios vinculados ao Período[cite: 37]
        Schema::create('periodo_criterios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('periodos_avaliacao')->cascadeOnDelete();
            $table->foreignId('criterio_id')->constrained('criterios_avaliacao')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodo_criterios');
        Schema::dropIfExists('periodo_fases');
        Schema::dropIfExists('periodos_avaliacao');
        Schema::dropIfExists('criterios_avaliacao');
    }
};