<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formulario_fases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formulario_id')->nullable()->constrained('formularios')->cascadeOnDelete();
            $table->foreignId('ciclo_id')->nullable()->constrained('ciclos')->cascadeOnDelete();
            
            $table->string('nome'); 
            $table->json('paginas'); // Arrays das páginas vinculadas ex: [1, 2]
            
            $table->dateTime('data_liberacao')->nullable();
            $table->dateTime('data_encerramento')->nullable();
            
            $table->unsignedBigInteger('email_template_id')->nullable(); // Disparo automático
            $table->boolean('notificacao_enviada')->default(false); // Flag para o Cron
            
            $table->timestamps();

            $table->foreign('email_template_id')->references('id')->on('email_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formulario_fases');
    }
};