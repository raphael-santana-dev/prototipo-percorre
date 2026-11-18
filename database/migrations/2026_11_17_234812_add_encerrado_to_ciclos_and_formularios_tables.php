<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ciclos') && !Schema::hasColumn('ciclos', 'encerrado')) {
            Schema::table('ciclos', function (Blueprint $table) {
                $table->boolean('encerrado')->default(false)->after('bloqueado');
            });
        }

        if (Schema::hasTable('formularios') && !Schema::hasColumn('formularios', 'encerrado')) {
            Schema::table('formularios', function (Blueprint $table) {
                $table->boolean('encerrado')->default(false)->after('status');
            });
        }

        if (Schema::hasTable('inscricoes') && !Schema::hasColumn('inscricoes', 'legado')) {
            Schema::table('inscricoes', function (Blueprint $table) {
                $table->boolean('legado')->default(false)->after('origem');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ciclos') && Schema::hasColumn('ciclos', 'encerrado')) {
            Schema::table('ciclos', function (Blueprint $table) {
                $table->dropColumn('encerrado');
            });
        }

        if (Schema::hasTable('formularios') && Schema::hasColumn('formularios', 'encerrado')) {
            Schema::table('formularios', function (Blueprint $table) {
                $table->dropColumn('encerrado');
            });
        }
    }
};