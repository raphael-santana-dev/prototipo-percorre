<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscricoes', function (Blueprint $table) {
            $table->integer('posicao_ranking_unidade')->nullable()->after('posicao_ranking_geral');
            $table->integer('posicao_ranking_curso')->nullable()->after('posicao_ranking_unidade');
        });
    }

    public function down(): void
    {
        Schema::table('inscricoes', function (Blueprint $table) {
            $table->dropColumn(['posicao_ranking_unidade', 'posicao_ranking_curso']);
        });
    }
};