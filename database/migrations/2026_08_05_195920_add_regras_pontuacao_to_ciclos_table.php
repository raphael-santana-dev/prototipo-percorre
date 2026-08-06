<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ciclos', function (Blueprint $table) {
            $table->json('regras_pontuacao')->nullable()->after('status');
        });
    }

    public function down()
    {
        Schema::table('ciclos', function (Blueprint $table) {
            $table->dropColumn('regras_pontuacao');
        });
    }
};
