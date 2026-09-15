<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('assunto');
            $table->longText('corpo');
            $table->timestamps();
        });

        Schema::create('comunicados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('email_templates')->restrictOnDelete();
            $table->json('destinatarios')->nullable();
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->json('anexos')->nullable();
            $table->dateTime('data_agendamento')->nullable();
            $table->string('status')->default('pendente');
            $table->timestamps();
        });

        Schema::create('automacoes', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('evento_gatilho'); 
            $table->foreignId('template_id')->constrained('email_templates')->restrictOnDelete();
            $table->boolean('status')->default(true);
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