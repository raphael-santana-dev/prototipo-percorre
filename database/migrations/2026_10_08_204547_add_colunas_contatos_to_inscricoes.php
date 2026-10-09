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
        Schema::table('inscricoes', function (Blueprint $table) {
            $table->boolean('contato_whatsapp')->default(false);
            $table->boolean('contato_email')->default(false);
            $table->boolean('contato_telefone')->default(false);
            $table->boolean('usuario_criado')->default(false);
            $table->boolean('email_primeiro_acesso_enviado')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inscricoes', function (Blueprint $table) {
            $table->dropColumn('contato_whatsapp');
            $table->dropColumn('contato_email');
            $table->dropColumn('contato_telefone');
            $table->dropColumn('usuario_criado');
            $table->dropColumn('email_primeiro_acesso_enviado');
        });
    }
};
