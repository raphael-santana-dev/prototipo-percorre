<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabela: Ciclos de Aprendizagem (O Período Global)
        Schema::create('ciclos_aprendizagem', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->comment('Ex: Avaliação de Desempenho 2026.2');
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Tabela: Fases do Ciclo de Aprendizagem (O Workflow)
        Schema::create('ciclo_aprendizagem_fases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ciclo_aprendizagem_id')->constrained('ciclos_aprendizagem')->cascadeOnDelete();
            $table->string('nome')->comment('Ex: Fase 1 - Autoavaliação');
            $table->integer('ordem')->default(1);
            $table->json('respondedores_permitidos')->comment('Ex: ["student", "company", "teacher"]');
            $table->timestamps();
        });

        // 3. Tabela: Controle de em qual fase cada aluno está no Ciclo
        Schema::create('aluno_ciclo_aprendizagem', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ciclo_aprendizagem_id')->constrained('ciclos_aprendizagem')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('fase_atual_id')->constrained('ciclo_aprendizagem_fases')->cascadeOnDelete();
            $table->enum('status', ['pendente', 'em_andamento', 'concluido'])->default('pendente');
            $table->timestamps();
        });

        // 4. Atualização da tabela de Formulários
        Schema::table('formularios', function (Blueprint $table) {
            
            // Vínculos para Pré-Inscrições (Usam o ciclo seletivo normal e estrutura acadêmica)
            $table->unsignedBigInteger('ciclo_id')->nullable()->after('tipo');
            $table->unsignedBigInteger('unidade_id')->nullable()->after('ciclo_id');
            $table->unsignedBigInteger('curso_id')->nullable()->after('unidade_id');

            // Vínculo para Avaliação de Aprendizagem
            $table->foreignId('ciclo_aprendizagem_fase_id')->nullable()->after('curso_id')
                  ->constrained('ciclo_aprendizagem_fases')->nullOnDelete();

            $table->index('ciclo_id');
            $table->index('unidade_id');
            $table->index('curso_id');
        });
    }

    public function down(): void
    {
        Schema::table('formularios', function (Blueprint $table) {
            $table->dropForeign(['ciclo_aprendizagem_fase_id']);
            $table->dropIndex(['curso_id']);
            $table->dropIndex(['unidade_id']);
            $table->dropIndex(['ciclo_id']);
            
            $table->dropColumn([
                'ciclo_id', 'unidade_id', 'curso_id', 'ciclo_aprendizagem_fase_id'
            ]);
        });

        Schema::dropIfExists('aluno_ciclo_aprendizagem');
        Schema::dropIfExists('ciclo_aprendizagem_fases');
        Schema::dropIfExists('ciclos_aprendizagem');
    }
};