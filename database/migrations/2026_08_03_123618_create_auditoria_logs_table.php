<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('auditoria_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tabela_alterada');
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->string('acao'); 
            
            $table->json('informacao_anterior')->nullable();
            $table->json('nova_informacao')->nullable();
            
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('usuario_nome')->nullable();
            $table->string('usuario_role')->nullable();
            $table->string('usuario_login')->nullable();
            $table->string('ip')->nullable();
            $table->text('navegador')->nullable();
            
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditoria_logs');
    }
};
