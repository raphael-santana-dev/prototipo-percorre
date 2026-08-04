<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campo_formularios', function (Blueprint $table) {
            $table->json('configuracoes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('campo_formularios', function (Blueprint $table) {
            $table->json('configuracoes')->nullable();
        });
    }
};
