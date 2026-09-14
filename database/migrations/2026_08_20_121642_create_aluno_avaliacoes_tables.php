<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aluno_avaliacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('periodos_avaliacao');
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete(); 
            $table->foreignId('turma_id')->constrained('turmas')->cascadeOnDelete();
            $table->string('fase', 1);
            $table->char('status', 1)->default('1'); 
            $table->date('data_resposta')->nullable();
            $table->string('hora_resposta', 5)->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['periodo_id', 'student_id', 'turma_id', 'fase']); 
        });

        Schema::create('aluno_avaliacao_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluno_avaliacao_id')->constrained('aluno_avaliacoes')->cascadeOnDelete();
            $table->foreignId('criterio_id')->constrained('criterios_avaliacao');
            $table->tinyInteger('nivel_nps')->nullable(); 
            $table->text('aval_metas')->nullable(); 
            $table->timestamps();
        });

        Schema::create('avaliacao_solicitacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluno_avaliacao_id')->constrained('aluno_avaliacoes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->json('criterios_selecionados'); 
            $table->text('motivo');
            $table->string('status')->default('pendente');
            $table->foreignId('avaliador_id')->nullable()->constrained('users'); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avaliacao_solicitacoes');
        Schema::dropIfExists('aluno_avaliacao_itens');
        Schema::dropIfExists('aluno_avaliacoes');
    }
};