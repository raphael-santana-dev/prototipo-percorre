<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('automacoes', function (Blueprint $table) {
            $table->string('tipo_acao')->default('enviar_email')->after('evento_gatilho');
        });
    }

    public function down()
    {
        Schema::table('automacoes', function (Blueprint $table) {
            $table->dropColumn('tipo_acao');
        });
    }
};
