<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('inscricoes', function (Blueprint $table) {
        $table->foreignId('criado_por')
              ->nullable()
              ->constrained('users')
              ->nullOnDelete(); 
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
