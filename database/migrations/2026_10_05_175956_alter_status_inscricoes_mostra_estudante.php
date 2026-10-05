<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('status_inscricoes', function (Blueprint $table) {
            $table->boolean('visivel_estudante')->default(true)->after('titulo_amigavel');
        });
    }

    public function down(): void
    {
        Schema::table('status_inscricoes', function (Blueprint $table) {
            $table->dropColumn('visivel_estudante');
        });
    }
};
