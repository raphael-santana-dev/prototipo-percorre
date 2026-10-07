<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Remove a natureza do Cabeçalho
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->dropColumn('natureza');
        });

        // 2. Adiciona o código da natureza aos Itens da planilha
        Schema::table('orcamento_itens', function (Blueprint $table) {
            $table->string('natureza_codigo')->nullable()->after('orcamento_id');
        });
    }

    public function down(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->string('natureza')->nullable();
        });

        Schema::table('orcamento_itens', function (Blueprint $table) {
            $table->dropColumn('natureza_codigo');
        });
    }
};