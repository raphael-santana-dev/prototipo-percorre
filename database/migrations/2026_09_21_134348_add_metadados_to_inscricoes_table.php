<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscricoes', function (Blueprint $table) {
            // Cria a coluna JSON 'metadados' logo após os dados dinâmicos do formulário
            $table->json('metadados')->nullable()->after('dados_dinamicos');
        });
    }

    public function down(): void
    {
        Schema::table('inscricoes', function (Blueprint $table) {
            $table->dropColumn('metadados');
        });
    }
};