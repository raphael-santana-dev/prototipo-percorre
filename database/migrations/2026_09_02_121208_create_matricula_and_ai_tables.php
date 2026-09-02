<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabela de Configuração das IAs
        Schema::create('configuracoes_ia', function (Blueprint $table) {
            $table->id();
            $table->string('provedor'); 
            $table->text('api_key')->nullable();
            $table->text('prompt_documentos')->nullable();
            $table->boolean('is_ativa')->default(false);
            $table->timestamps();
        });

        // Tabela de Documentos Exigidos pela Instituição
        Schema::create('documentos_exigidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ciclo_id')->constrained('ciclos')->cascadeOnDelete();
            $table->string('nome'); 
            $table->string('descricao')->nullable(); 
            $table->boolean('is_obrigatorio')->default(true);
            $table->timestamps();
        });

        // Tabela do Cofre de Matrícula (Arquivos enviados)
        Schema::create('documentos_matricula', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscricao_id')->constrained('inscricoes')->cascadeOnDelete();
            $table->foreignId('documento_exigido_id')->constrained('documentos_exigidos')->cascadeOnDelete();
            
            $table->string('arquivo_caminho');
            $table->string('arquivo_extensao');
            
            $table->string('status_analise')->default('pendente'); 
            $table->integer('tentativas_ia')->default(0); 
            $table->json('log_ia')->nullable(); 
            
            $table->foreignId('avaliado_por')->nullable()->constrained('users')->nullOnDelete(); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_matricula');
        Schema::dropIfExists('documentos_exigidos');
        Schema::dropIfExists('configuracoes_ia');
    }
};