<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inscricoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ciclo_id')->nullable()->constrained('ciclos')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            // Controle de Salvamento Progressivo
            $table->integer('etapa_atual')->default(1);
            
            $table->string('nome')->nullable();
            $table->string('email')->nullable();
            $table->string('celular', 20)->nullable();
            $table->string('possui_nome_social', 5)->nullable();
            $table->string('nome_social')->nullable();
            $table->date('data_nascimento')->nullable();
            $table->string('cpf', 14)->unique()->nullable();
            $table->string('cep', 10)->nullable();
            $table->string('logradouro')->nullable();
            $table->string('numero', 20)->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro', 100)->nullable();
            $table->string('cidade', 100)->nullable();
            $table->string('estado', 50)->nullable();
            $table->string('possui_deficiencia', 5)->nullable();
            $table->string('natureza_deficiencia', 50)->nullable();

            // Controle de Sistema e Termos
            $table->integer('receber_informacoes')->nullable();
            $table->integer('autorizacao_uso_infos')->nullable();
            
            

            $table->integer('pontuacao_total')->default(0);
            $table->string('pontuacao_detalhes')->nullable();
            $table->integer('posicao_ranking')->nullable();

            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscricoes');
    }
};
