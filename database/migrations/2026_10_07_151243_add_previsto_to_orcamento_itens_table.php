<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orcamento_itens', function (Blueprint $table) {
            $table->decimal('previsto_jan', 15, 2)->default(0)->after('descricao');
            $table->decimal('previsto_fev', 15, 2)->default(0)->after('valor_jan');
            $table->decimal('previsto_mar', 15, 2)->default(0)->after('valor_fev');
            $table->decimal('previsto_abr', 15, 2)->default(0)->after('valor_mar');
            $table->decimal('previsto_mai', 15, 2)->default(0)->after('valor_abr');
            $table->decimal('previsto_jun', 15, 2)->default(0)->after('valor_mai');
            $table->decimal('previsto_jul', 15, 2)->default(0)->after('valor_jun');
            $table->decimal('previsto_ago', 15, 2)->default(0)->after('valor_jul');
            $table->decimal('previsto_set', 15, 2)->default(0)->after('valor_ago');
            $table->decimal('previsto_out', 15, 2)->default(0)->after('valor_set');
            $table->decimal('previsto_nov', 15, 2)->default(0)->after('valor_out');
            $table->decimal('previsto_dez', 15, 2)->default(0)->after('valor_nov');
        });
    }

    public function down(): void
    {
        Schema::table('orcamento_itens', function (Blueprint $table) {
            $table->dropColumn([
                'previsto_jan', 'previsto_fev', 'previsto_mar', 'previsto_abr', 
                'previsto_mai', 'previsto_jun', 'previsto_jul', 'previsto_ago', 
                'previsto_set', 'previsto_out', 'previsto_nov', 'previsto_dez'
            ]);
        });
    }
};