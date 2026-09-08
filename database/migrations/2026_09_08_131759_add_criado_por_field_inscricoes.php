<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('inscricoes', function (Blueprint $table) {
        // Cria a coluna e diz que ela é uma chave estrangeira ligada à tabela 'users'
        $table->foreignId('criado_por')
              ->nullable()
              ->constrained('users')
              ->nullOnDelete(); // Se o admin for excluído, o registro não some, apenas o ID fica nulo
    });
}

public function down()
{
    Schema::table('inscricoes', function (Blueprint $table) {
        $table->dropForeign(['criado_por']);
        $table->dropColumn('criado_por');
    });
}
};
