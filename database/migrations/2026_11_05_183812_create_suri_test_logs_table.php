<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suri_test_logs', function (Blueprint $table) {
            $table->id();
            $table->string('acao'); // Ex: 'Importar Contato', 'Template Simples', etc.
            $table->string('metodo', 10)->default('POST'); // 'GET' ou 'POST'
            $table->text('url');
            $table->integer('http_status')->default(0);
            $table->boolean('sucesso')->default(false);
            $table->json('payload')->nullable();
            $table->json('resposta')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_nome')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suri_test_logs');
    }
};