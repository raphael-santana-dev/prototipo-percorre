<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('campo_formularios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ciclo_id')->constrained('ciclos')->cascadeOnDelete();
            $table->integer('etapa'); 
            $table->string('label'); 
            $table->string('name'); 
            $table->string('tipo'); 
            $table->json('opcoes')->nullable(); 
            $table->boolean('obrigatorio')->default(false);
            $table->string('regras_validacao')->nullable();
            $table->integer('ordem')->default(0); 
            $table->string('depende_de')->nullable()->after('regras_validacao');
            $table->string('depende_valor')->nullable()->after('depende_de');
            $table->integer('largura')->default(12)->after('tipo');
            $table->string('subtipo')->default('text')->after('largura'); 
            $table->integer('tamanho_min')->nullable()->after('subtipo');
            $table->integer('tamanho_max')->nullable()->after('tamanho_min');
            $table->string('regex_mascara')->nullable()->after('tamanho_max');
            $table->string('depende_operador')->default('=')->after('depende_de');
            $table->timestamps();
            
            $table->unique(['ciclo_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campo_formularios');
    }
};
