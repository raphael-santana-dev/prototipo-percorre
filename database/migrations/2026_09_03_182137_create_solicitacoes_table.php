<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('solicitacoes', function (Blueprint $table) {
            $table->id();
            $table->string('tema');
            $table->morphs('solicitante'); 
            $table->foreignId('responsavel_id')->nullable()->constrained('users'); 
            $table->text('justificativa');
            $table->text('resposta_admin')->nullable();
            $table->string('status')->default('pendente');
            $table->json('payload')->nullable();  
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void {
        Schema::dropIfExists('solicitacoes');
    }
};