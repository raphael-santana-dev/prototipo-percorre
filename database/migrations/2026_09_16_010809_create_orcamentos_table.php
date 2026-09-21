<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orcamentos', function (Blueprint $table) {
            $table->id();
            
            $table->string('filial', 10)->index()->nullable();
            $table->string('ano', 4)->index()->nullable();
            $table->string('natureza')->index()->nullable();
            $table->integer('moeda')->nullable();
            $table->string('cmoeda', 10)->nullable();
            
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
            
            $table->string('ccusto')->nullable();
            $table->string('xcat')->nullable();
            
            $table->string('chave_composta')->unique()->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamentos');
    }
};