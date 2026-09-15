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
            $table->string('tipo'); 
            $table->string('operacao')->default('importacao'); 
            $table->string('formato', 10)->default('csv');
            
            $table->string('arquivo_nome')->nullable(); 
            $table->string('arquivo_caminho')->nullable(); 
            $table->string('arquivo_gerado_caminho')->nullable(); 

            $table->integer('total_linhas')->default(0);
            $table->integer('linhas_processadas')->default(0);
            
            $table->string('status')->default('mapeamento'); 
            
            $table->json('mapeamento')->nullable();
            $table->longText('erro_mensagem')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('importacoes');
    }
};