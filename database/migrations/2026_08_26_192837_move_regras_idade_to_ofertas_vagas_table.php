<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Remove as colunas globais da tabela de cursos
        Schema::table('cursos', function (Blueprint $table) {
            $table->dropColumn(['min_idade', 'max_idade']);
        });

        // 2. Adiciona as colunas na tabela de ofertas, tornando a regra flexível
        Schema::table('ofertas_vagas', function (Blueprint $table) {
            $table->integer('idade_min')->nullable()->after('vagas')->comment('Idade mínima específica para esta oferta');
            $table->integer('idade_max')->nullable()->after('idade_min')->comment('Idade máxima específica para esta oferta');
        });
    }

    public function down(): void
    {
        Schema::table('ofertas_vagas', function (Blueprint $table) {
            $table->dropColumn(['idade_min', 'idade_max']);
        });

        Schema::table('cursos', function (Blueprint $table) {
            $table->integer('min_idade')->nullable();
            $table->integer('max_idade')->nullable();
        });
    }
};