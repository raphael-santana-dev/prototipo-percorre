<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Adiciona na tabela de Administradores/Funcionários
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(true)->after('password');
        });

        // Adiciona na tabela de Estudantes
        Schema::table('students', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(true)->after('password');
        });

        // Adiciona na tabela de Usuários de Empresas
        Schema::table('company_users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(true)->after('password');
        });

        /* 
         * IMPORTANTE: Como você já está testando o sistema, vamos definir os seus usuários 
         * atuais como FALSE para você não ser bloqueado de imediato enquanto desenvolvemos.
         * Os próximos usuários criados pelo painel já nascerão com TRUE por conta do default acima!
         */
        DB::table('users')->update(['must_change_password' => false]);
        DB::table('students')->update(['must_change_password' => false]);
        DB::table('company_users')->update(['must_change_password' => false]);
    }

    public function down(): void
    {
        Schema::table('company_users', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });
    }
};