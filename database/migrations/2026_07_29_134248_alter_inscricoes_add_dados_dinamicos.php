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
        Schema::table('inscricoes', function (Blueprint $table) {
            // Coluna JSON para armazenar todas as respostas dinâmicas
            $table->json('dados_dinamicos')->nullable()->after('status_inscricao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('inscricoes', function (Blueprint $table) {
            $table->dropColumn('dados_dinamicos');
        });
    }
};
