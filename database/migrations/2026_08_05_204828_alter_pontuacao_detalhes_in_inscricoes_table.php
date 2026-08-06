<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscricoes', function (Blueprint $table) {
            // Alteramos para 'text', o Postgres converte automaticamente e resolve o limite de caracteres!
            $table->text('pontuacao_detalhes')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('inscricoes', function (Blueprint $table) {
            $table->string('pontuacao_detalhes', 255)->nullable()->change();
        });
    }
};