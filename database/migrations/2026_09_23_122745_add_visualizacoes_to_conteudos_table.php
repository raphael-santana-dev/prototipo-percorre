<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conteudos', function (Blueprint $table) {
            $table->unsignedBigInteger('visualizacoes')->default(0)->after('opcoes_visuais');
        });
    }

    public function down(): void
    {
        Schema::table('conteudos', function (Blueprint $table) {
            $table->dropColumn('visualizacoes');
        });
    }
};