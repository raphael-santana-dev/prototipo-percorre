<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('comunicados', function (Blueprint $table) {
            $table->foreignId('inscricao_id')->nullable()->constrained('inscricoes')->nullOnDelete()->after('template_id');
        });
    }

    public function down()
    {
        Schema::table('comunicados', function (Blueprint $table) {
            $table->dropForeign(['inscricao_id']);
            $table->dropColumn('inscricao_id');
        });
    }
};
