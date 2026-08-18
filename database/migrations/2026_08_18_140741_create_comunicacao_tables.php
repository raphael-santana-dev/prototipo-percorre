<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Tabela de Templates
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('nome'); // Ex: "Boas-vindas Aluno"
            $table->string('assunto');
            $table->longText('corpo');
            $table->timestamps();
        });

        // 2. Tabela de Comunicados (Disparos Manuais/Agendados)
        Schema::create('comunicados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('email_templates')->restrictOnDelete();
            $table->json('destinatarios')->nullable(); // Arrays de emails ['To']
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->json('anexos')->nullable(); // Caminhos no storage
            $table->dateTime('data_agendamento')->nullable();
            $table->string('status')->default('pendente'); // pendente, enviando, concluido, erro
            $table->timestamps();
        });

        // 3. Tabela de Automações (Gatilhos)
        Schema::create('automacoes', function (Blueprint $table) {
            $table->id();
            $table->string('nome'); // Ex: "Aviso de Inscrição Aprovada"
            $table->string('evento_gatilho'); // Ex: "inscricao.status.aprovado"
            $table->foreignId('template_id')->constrained('email_templates')->restrictOnDelete();
            $table->boolean('status')->default(true); // Ativo/Inativo (WithToggleStatus)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automacoes');
        Schema::dropIfExists('comunicados');
        Schema::dropIfExists('email_templates');
    }
};