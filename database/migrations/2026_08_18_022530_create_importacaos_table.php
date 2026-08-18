<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('importacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('tipo'); // ex: cursos, estudantes, unidades, usuarios, formularios
            $table->string('operacao')->default('importacao'); // importacao ou exportacao
            $table->string('formato', 10)->default('csv'); // csv, xlsx, json, xml
            
            $table->string('arquivo_nome')->nullable(); // Nome original enviado
            $table->string('arquivo_caminho')->nullable(); // Onde está salvo o arquivo enviado
            $table->string('arquivo_gerado_caminho')->nullable(); // Onde está a planilha final gerada (exportação)
            
            $table->integer('total_linhas')->default(0);
            $table->integer('linhas_processadas')->default(0);
            
            // Status do fluxo: mapeamento -> na_fila -> processando -> concluido -> erro_parcial -> erro
            $table->string('status')->default('mapeamento'); 
            
            $table->json('mapeamento')->nullable(); // Guarda como o usuário ligou as colunas do excel com o DB
            $table->longText('erro_mensagem')->nullable(); // Guarda o log de erros em JSON
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('importacoes');
    }
};