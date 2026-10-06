<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->string('status')->default('Criado')->after('ccusto'); // Criado, Em elaboração, Finalizado
            $table->text('descricao_despesa')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->dropColumn(['status', 'descricao_despesa']);
        });
    }
};