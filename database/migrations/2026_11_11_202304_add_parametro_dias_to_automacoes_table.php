<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('automacoes') && !Schema::hasColumn('automacoes', 'parametro_dias')) {
            Schema::table('automacoes', function (Blueprint $table) {
                $table->integer('parametro_dias')->nullable()->default(null)->after('evento_gatilho');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('automacoes') && Schema::hasColumn('automacoes', 'parametro_dias')) {
            Schema::table('automacoes', function (Blueprint $table) {
                $table->dropColumn('parametro_dias');
            });
        }
    }
};