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
            $table->string('origem')->default('comunicado'); // 'comunicado', 'automacao', 'sistema'
            $table->string('destinatario');
            $table->string('assunto');
            $table->longText('corpo'); // O HTML já processado com as variáveis
            $table->json('anexos')->nullable();
            $table->dateTime('data_agendamento')->nullable();
            $table->dateTime('data_envio')->nullable();
            $table->string('status')->default('pendente'); // pendente, enviado, erro
            $table->text('erro_mensagem')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comunicacao_logs');
    }
};