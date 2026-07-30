<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Relacionamento Usuário <-> Múltiplas Unidades
        Schema::create('unidade_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('unidade_id')->constrained('unidades')->cascadeOnDelete();
            $table->primary(['user_id', 'unidade_id']); // Garante que não haverá vínculos duplicados
        });

        // 2. Relacionamento Usuário <-> Múltiplos Cursos
        Schema::create('curso_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->primary(['user_id', 'curso_id']);
        });

        // 3. Relacionamento Usuário <-> Múltiplos Turnos
        Schema::create('turno_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
            $table->primary(['user_id', 'turno_id']);
        });
        
        // Opcional: Removemos a coluna antiga de unidade única da tabela users 
        // para evitar confusão de arquitetura no futuro.
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'unidade_id')) {
                $table->dropForeign(['unidade_id']);
                $table->dropColumn('unidade_id');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turno_user');
        Schema::dropIfExists('curso_user');
        Schema::dropIfExists('unidade_user');
        
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('unidade_id')->nullable()->constrained('unidades')->nullOnDelete();
        });
    }
};