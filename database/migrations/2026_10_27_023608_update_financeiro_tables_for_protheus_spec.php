<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('naturezas', function (Blueprint $table) {
            if (!Schema::hasColumn('naturezas', 'categoria')) {
                $table->string('categoria', 100)->nullable()->after('descricao');
            }
            if (!Schema::hasColumn('naturezas', 'tipo')) {
                $table->string('tipo', 30)->nullable()->default('DESPESA')->after('categoria');
            }
        });

        Schema::table('orcamento_itens', function (Blueprint $table) {
            if (!Schema::hasColumn('orcamento_itens', 'id_despesa')) {
                $table->string('id_despesa', 10)->nullable()->after('orcamento_id')->comment('ID sequencial de 3 posições (001, 002) por orçamento');
            }
            if (!Schema::hasColumn('orcamento_itens', 'cenario')) {
                $table->string('cenario', 2)->default('1')->after('natureza_codigo')->comment('1=Conservador, 2=Moderado, 3=Aspiracional');
            }
        });
    }

    public function down(): void
    {
        Schema::table('naturezas', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('naturezas', 'categoria')) $cols[] = 'categoria';
            if (Schema::hasColumn('naturezas', 'tipo')) $cols[] = 'tipo';
            if (!empty($cols)) $table->dropColumn($cols);
        });

        Schema::table('orcamento_itens', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('orcamento_itens', 'id_despesa')) $cols[] = 'id_despesa';
            if (Schema::hasColumn('orcamento_itens', 'cenario')) $cols[] = 'cenario';
            if (!empty($cols)) $table->dropColumn($cols);
        });
    }
};