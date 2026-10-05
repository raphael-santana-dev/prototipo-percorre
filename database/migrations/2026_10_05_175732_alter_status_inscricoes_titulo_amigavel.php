<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('status_inscricoes', function (Blueprint $table) {
            $table->string('titulo_amigavel')->nullable()->after('nome');
        });
    }

    public function down(): void
    {
        Schema::table('status_inscricoes', function (Blueprint $table) {
            $table->dropColumn('titulo_amigavel');
        });
    }
};
