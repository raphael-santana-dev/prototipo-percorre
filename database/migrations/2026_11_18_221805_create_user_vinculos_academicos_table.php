<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'travar_por_vinculo')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('travar_por_vinculo')->default(false)->after('codigo_protheus');
            });
        }

        if (!Schema::hasTable('user_vinculos_academicos')) {
            Schema::create('user_vinculos_academicos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('unidade_id')->constrained('unidades')->cascadeOnDelete();
                $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
                $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
                $table->date('expires_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'unidade_id', 'curso_id', 'turno_id'], 'uk_user_vinculo_uct');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_vinculos_academicos');

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'travar_por_vinculo')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('travar_por_vinculo');
            });
        }
    }
};