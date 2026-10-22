<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ciclos', function (Blueprint $table) {
            // 'regular' (padrão /inscricao) ou 'customizado' (usa /inscricao/{slug_projeto})
            $table->string('tipo_ciclo', 30)->default('regular')->after('nome');
            $table->string('slug_projeto')->nullable()->unique()->after('tipo_ciclo');
        });
    }

    public function down(): void
    {
        Schema::table('ciclos', function (Blueprint $table) {
            $table->dropColumn(['tipo_ciclo', 'slug_projeto']);
        });
    }
};