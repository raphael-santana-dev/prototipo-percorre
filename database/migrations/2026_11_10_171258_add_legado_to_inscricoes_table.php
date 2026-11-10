<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inscricoes') && !Schema::hasColumn('inscricoes', 'legado')) {
            Schema::table('inscricoes', function (Blueprint $table) {
                $table->boolean('legado')->default(false)->after('origem')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inscricoes') && Schema::hasColumn('inscricoes', 'legado')) {
            Schema::table('inscricoes', function (Blueprint $table) {
                $table->dropColumn('legado');
            });
        }
    }
};