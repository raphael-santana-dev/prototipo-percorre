<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orcamentos_historicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orcamento_id')->constrained('orcamentos')->cascadeOnDelete();
            $table->integer('mes_referencia'); // Ex: 1 para Janeiro, 2 para Fevereiro
            $table->string('ano');
            $table->string('status_no_momento');
            
            // Valores fotografados naquele mês
            $table->decimal('valor_jan', 15, 2)->default(0);
            $table->decimal('valor_fev', 15, 2)->default(0);
            $table->decimal('valor_mar', 15, 2)->default(0);
            $table->decimal('valor_abr', 15, 2)->default(0);
            $table->decimal('valor_mai', 15, 2)->default(0);
            $table->decimal('valor_jun', 15, 2)->default(0);
            $table->decimal('valor_jul', 15, 2)->default(0);
            $table->decimal('valor_ago', 15, 2)->default(0);
            $table->decimal('valor_set', 15, 2)->default(0);
            $table->decimal('valor_out', 15, 2)->default(0);
            $table->decimal('valor_nov', 15, 2)->default(0);
            $table->decimal('valor_dez', 15, 2)->default(0);
            
            $table->decimal('valor_total_historico', 15, 2)->default(0);
            $table->unsignedBigInteger('registrado_por'); // Quem clicou em "Avançar Mês"
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamentos_historicos');
    }
};