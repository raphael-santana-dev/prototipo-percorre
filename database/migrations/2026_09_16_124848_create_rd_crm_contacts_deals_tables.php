<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabela de Contatos Locais
        Schema::create('rd_crm_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('rd_id')->nullable()->comment('Preenchido automaticamente após enviar pro RD');
            $table->string('nome');
            $table->string('email')->nullable();
            $table->string('telefone')->nullable();
            $table->date('data_nascimento')->nullable();
            $table->timestamps();
        });

        // Tabela de Negociações (Deals) vinculadas ao contato
        Schema::create('rd_crm_deals', function (Blueprint $table) {
            $table->id();
            $table->string('rd_id')->nullable()->comment('Preenchido após o POST dar sucesso');
            $table->foreignId('rd_crm_contact_id')->constrained('rd_crm_contacts')->cascadeOnDelete();
            
            $table->string('nome');
            $table->integer('rating')->default(1);
            $table->string('deal_stage_id')->nullable()->comment('O ID hexadecimal da etapa no RD');
            $table->string('campaign_id')->nullable()->comment('Opcional: ID da campanha no RD');
            
            $table->json('campos_customizados')->nullable()->comment('Guarda o array de perguntas e respostas extras');
            
            $table->boolean('sincronizado')->default(false)->comment('Controle para o Cron Job saber se já enviou');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rd_crm_deals');
        Schema::dropIfExists('rd_crm_contacts');
    }
};