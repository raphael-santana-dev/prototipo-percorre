<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscricoes', function (Blueprint $table) {
            // Remove o índice único da coluna cpf. 
            // O Laravel infere o nome padrão 'inscricoes_cpf_unique'
            $table->dropUnique(['cpf']);
        });
    }

    public function down(): void
    {
        Schema::table('inscricoes', function (Blueprint $table) {
            // Caso precise reverter, devolve a trava de CPF único global
            $table->unique('cpf');
        });
    }
};