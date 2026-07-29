<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('status_inscricoes', function (Blueprint $table) {
            // Adiciona a coluna cor (HEX longo padrão: #FFFFFF)
            $table->string('cor', 10)->nullable()->after('nome');
        });
    }

    public function down(): void
    {
        Schema::table('status_inscricoes', function (Blueprint $table) {
            $table->dropColumn('cor');
        });
    }
};