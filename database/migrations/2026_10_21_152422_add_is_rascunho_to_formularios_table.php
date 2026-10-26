<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formularios', function (Blueprint $table) {
            // Adiciona a flag de rascunho (padrão: sim, é rascunho ao criar)
            $table->boolean('is_rascunho')->default(true)->after('titulo');
            
            // Garante que o ciclo_id seja opcional (nullable)
            if (Schema::hasColumn('formularios', 'ciclo_id')) {
                $table->unsignedBigInteger('ciclo_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('formularios', function (Blueprint $table) {
            $table->dropColumn('is_rascunho');
            
            if (Schema::hasColumn('formularios', 'ciclo_id')) {
                $table->unsignedBigInteger('ciclo_id')->nullable(false)->change();
            }
        });
    }
};