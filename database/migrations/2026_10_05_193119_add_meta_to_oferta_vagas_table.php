<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ofertas_vagas', function (Blueprint $table) {
            // Adiciona a coluna meta logo após a coluna vagas
            $table->integer('meta')->nullable()->after('vagas');
        });
    }

    public function down(): void
    {
        Schema::table('ofertas_vagas', function (Blueprint $table) {
            $table->dropColumn('meta');
        });
    }
};