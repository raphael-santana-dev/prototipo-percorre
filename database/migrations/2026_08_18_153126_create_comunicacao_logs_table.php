<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comunicacao_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comunicado_id')->nullable()->constrained('comunicados')->cascadeOnDelete();
            $table->string('origem')->default('comunicado'); 
            $table->string('destinatario');
            $table->string('assunto');
            $table->longText('corpo');
            $table->json('anexos')->nullable();
            $table->dateTime('data_agendamento')->nullable();
            $table->dateTime('data_envio')->nullable();
            $table->string('status')->default('pendente'); 
            $table->text('erro_mensagem')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comunicacao_logs');
    }
};