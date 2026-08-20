<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabela Mestre: Matriz do Aluno
        Schema::create('aluno_avaliacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('periodos_avaliacao');
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete(); // Corrigido para a tabela atual de estudantes
            $table->foreignId('turma_id')->constrained('turmas')->cascadeOnDelete();
            $table->string('fase', 1);
            $table->char('status', 1)->default('1'); // 1=Gerada, 2=Respondida[cite: 38]
            $table->date('data_resposta')->nullable();
            $table->string('hora_resposta', 5)->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Impede duplicidade da mesma fase para o mesmo aluno/turma/periodo[cite: 38]
            $table->unique(['periodo_id', 'student_id', 'turma_id', 'fase']); 
        });

        // 2. Itens: Respostas dos Critérios (NPS e Metas)[cite: 38]
        Schema::create('aluno_avaliacao_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluno_avaliacao_id')->constrained('aluno_avaliacoes')->cascadeOnDelete();
            $table->foreignId('criterio_id')->constrained('criterios_avaliacao');
            $table->tinyInteger('nivel_nps')->nullable(); // Nota de 0 a 10[cite: 38]
            $table->text('aval_metas')->nullable(); // Justificativa/Metas[cite: 38]
            $table->timestamps();
        });

        // 3. Tabela de Solicitações de Alteração (Desbloqueio)
        Schema::create('avaliacao_solicitacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluno_avaliacao_id')->constrained('aluno_avaliacoes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->json('criterios_selecionados'); // Array de IDs dos critérios[cite: 43]
            $table->text('motivo');
            $table->string('status')->default('pendente'); // pendente, aprovada, reprovada[cite: 43, 55]
            $table->foreignId('avaliador_id')->nullable()->constrained('users'); // Usuário (Professor/Admin) que aprovou/reprovou[cite: 55]
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