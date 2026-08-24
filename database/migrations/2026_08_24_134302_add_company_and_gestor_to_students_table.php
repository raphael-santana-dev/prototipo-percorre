<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Código da empresa que virá da integração com o Protheus
            $table->string('empresa_codigo')->nullable()->after('unidade_id');
            
            // Relacionamento com o Gestor Avaliador da empresa
            $table->foreignId('gestor_id')
                  ->nullable()
                  ->constrained('company_users')
                  ->nullOnDelete()
                  ->after('empresa_codigo');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['gestor_id']);
            $table->dropColumn(['empresa_codigo', 'gestor_id']);
        });
    }
};