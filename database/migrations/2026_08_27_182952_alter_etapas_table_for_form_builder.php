<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('etapas', function (Blueprint $table) {
            // Remove a trava global de "Etapa única no sistema inteiro"
            $table->dropUnique('etapas_numero_unique'); 
            
            // Adiciona o pertencimento
            $table->foreignId('ciclo_id')->nullable()->constrained('ciclos')->cascadeOnDelete()->after('id');
            $table->foreignId('formulario_id')->nullable()->constrained('formularios')->cascadeOnDelete()->after('ciclo_id');
        });
    }

    public function down(): void
    {
        Schema::table('etapas', function (Blueprint $table) {
            $table->unique('numero', 'etapas_numero_unique');
            $table->dropForeign(['ciclo_id']);
            $table->dropForeign(['formulario_id']);
            $table->dropColumn(['ciclo_id', 'formulario_id']);
        });
    }
};