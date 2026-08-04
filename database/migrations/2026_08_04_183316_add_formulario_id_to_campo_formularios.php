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
        Schema::table('campo_formularios', function (Blueprint $table) {
            // Torna o ciclo_id opcional
            $table->foreignId('ciclo_id')->nullable()->change(); 
            // Adiciona a nova chave estrangeira
            $table->foreignId('formulario_id')->nullable()->after('ciclo_id')->constrained('formularios')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campo_formularios', function (Blueprint $table) {
            //
        });
    }
};
