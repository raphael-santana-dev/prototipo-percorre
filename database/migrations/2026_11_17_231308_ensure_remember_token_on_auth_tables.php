<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tabelasAuth = ['users', 'students', 'companies', 'empresas', 'empresa_contatos'];

        foreach ($tabelasAuth as $tabela) {
            if (Schema::hasTable($tabela) && !Schema::hasColumn($tabela, 'remember_token')) {
                Schema::table($tabela, function (Blueprint $table) {
                    $table->rememberToken();
                });
            }
        }
    }

    public function down(): void
    {
        // Mantém a coluna por segurança
    }
};