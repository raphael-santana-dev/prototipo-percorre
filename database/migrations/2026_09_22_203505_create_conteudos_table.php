<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conteudos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->nullable()->constrained('conteudo_categorias')->nullOnDelete();
            $table->foreignId('autor_id')->constrained('users')->cascadeOnDelete();
            
            // Dados Básicos
            $table->string('titulo');
            $table->string('slug')->unique();
            $table->enum('tipo', ['padrao', 'carrossel', 'story'])->default('padrao');
            $table->longText('corpo')->nullable(); // Para o Quill
            
            // Controle de Exibição
            $table->boolean('is_active')->default(true);
            $table->timestamp('data_inicio')->nullable();
            $table->timestamp('data_fim')->nullable();
            
            // Acesso (Array JSON com: geral, empresas, estudantes, interno)
            $table->json('publico_alvo');
            
            // Destaque na Home
            $table->boolean('is_destaque')->default(false);
            $table->integer('ordem_destaque')->nullable();
            
            // Banners Recortados (Caminhos)
            $table->string('banner_interno')->nullable();
            $table->string('banner_desktop')->nullable();
            $table->string('banner_mobile')->nullable();
            $table->string('banner_destaque')->nullable();
            
            // Textos e Metadados Visuais (Overlays, Posições e Itens do Carrossel)
            $table->string('texto_overlay')->nullable();
            $table->json('opcoes_visuais')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conteudos');
    }
};