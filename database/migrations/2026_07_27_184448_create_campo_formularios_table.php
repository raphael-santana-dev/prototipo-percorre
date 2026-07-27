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
            $table->integer('etapa'); // 1, 2 ou 3
            $table->string('label'); // Ex: "Qual sua Renda?"
            $table->string('name'); // Identificador interno. Ex: "renda_familiar"
            $table->string('tipo'); // text, number, select, radio, date
            $table->json('opcoes')->nullable(); // Para opções de select/radio: {"1": "Até R$ 1.500", "2": "Mais de R$ 1.500"}
            $table->boolean('obrigatorio')->default(false);
            $table->string('regras_validacao')->nullable(); // Ex: "min:3|max:255"
            $table->integer('ordem')->default(0); // Para ordenar a exibição na tela
            // Guarda o "name" do campo que vai servir de gatilho (ex: possui_nome_social)
            $table->string('depende_de')->nullable()->after('regras_validacao');
            // Guarda o valor que o campo gatilho deve ter (ex: sim)
            $table->string('depende_valor')->nullable()->after('depende_de');
            $table->integer('largura')->default(12)->after('tipo'); // 12=100%, 6=50%, 4=33%, 3=25%
            $table->string('subtipo')->default('text')->after('largura'); // email, password, date, number
            $table->integer('tamanho_min')->nullable()->after('subtipo');
            $table->integer('tamanho_max')->nullable()->after('tamanho_min');
            $table->string('regex_mascara')->nullable()->after('tamanho_max'); // Ex: 999.999.999-99
            $table->string('depende_operador')->default('=')->after('depende_de'); // =, !=, >, <, in
            $table->timestamps();
            
            // Garante que não teremos dois campos com o mesmo identificador (name) no mesmo ciclo
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
