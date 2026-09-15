<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscricoes', function (Blueprint $table) {
            $table->text('pontuacao_detalhes')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('inscricoes', function (Blueprint $table) {
            $table->string('pontuacao_detalhes', 255)->nullable()->change();
        });
    }
};