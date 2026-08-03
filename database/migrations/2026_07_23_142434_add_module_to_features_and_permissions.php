<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adiciona a coluna module na tabela features
        Schema::table('features', function (Blueprint $table) {
            $table->string('module')->after('id')->default('geral')->comment('Agrupador lógico, ex: curso, turma, role');
        });

        // Busca o nome exato da tabela de permissões mapeada pelo Spatie
        $tableNames = config('permission.table_names');
        $tableName = $tableNames['permissions'] ?? 'permissions';

        // Adiciona as colunas module e description na tabela dinâmica do Spatie
        Schema::table($tableName, function (Blueprint $table) {
            $table->string('module')->after('id')->default('geral');
            $table->string('description')->after('name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->dropColumn('module');
        });

        $tableNames = config('permission.table_names');
        $tableName = $tableNames['permissions'] ?? 'permissions';

        Schema::table($tableName, function (Blueprint $table) {
            $table->dropColumn(['module', 'description']);
        });
    }
};