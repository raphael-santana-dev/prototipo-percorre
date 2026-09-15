<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Tabela de Provedores (OpenAI, Google, xAI)
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('driver')->default('openai_compatible'); // gemini, openai_compatible
            $table->string('api_url')->nullable();
            $table->text('api_key')->nullable();
            $table->timestamps();
        });

        // 2. Tabela de Modelos (GPT-4o, Gemini 1.5, Grok Vision)
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_provider_id')->constrained('ai_providers')->cascadeOnDelete();
            $table->string('nome');
            $table->string('codigo');
            $table->timestamps();
        });

        // 3. Cria a tabela principal de configuração (garantindo que ela exista)
        if (!Schema::hasTable('configuracao_ias')) {
            Schema::create('configuracao_ias', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ai_model_id')->nullable()->constrained('ai_models')->nullOnDelete();
                $table->text('prompt_documentos')->nullable();
                $table->boolean('is_ativa')->default(false);
                $table->timestamps();
            });
        } else {
            // Se ela já existia (do código anterior), apenas adicionamos a chave estrangeira
            Schema::table('configuracao_ias', function (Blueprint $table) {
                if (!Schema::hasColumn('configuracao_ias', 'ai_model_id')) {
                    $table->foreignId('ai_model_id')->nullable()->constrained('ai_models')->nullOnDelete();
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('configuracao_ias') && Schema::hasColumn('configuracao_ias', 'ai_model_id')) {
            Schema::table('configuracao_ias', function (Blueprint $table) {
                $table->dropForeign(['ai_model_id']);
                $table->dropColumn('ai_model_id');
            });
        }
        
        Schema::dropIfExists('ai_models');
        Schema::dropIfExists('ai_providers');
    }
};