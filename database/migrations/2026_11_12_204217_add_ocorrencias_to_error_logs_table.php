<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('error_logs')) {
            Schema::table('error_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('error_logs', 'ocorrencias_count')) {
                    $table->unsignedInteger('ocorrencias_count')->default(1)->after('resolvido');
                }
                if (!Schema::hasColumn('error_logs', 'reaberto')) {
                    $table->boolean('reaberto')->default(false)->after('ocorrencias_count');
                }
                if (!Schema::hasColumn('error_logs', 'ultima_ocorrencia_em')) {
                    $table->timestamp('ultima_ocorrencia_em')->nullable()->after('reaberto');
                }
                if (!Schema::hasColumn('error_logs', 'ocorrencias_historico')) {
                    $table->json('ocorrencias_historico')->nullable()->after('ultima_ocorrencia_em');
                }
            });

            // Inicializa ultima_ocorrencia_em nos registros já existentes
            DB::table('error_logs')
                ->whereNull('ultima_ocorrencia_em')
                ->update(['ultima_ocorrencia_em' => DB::raw('created_at')]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('error_logs')) {
            Schema::table('error_logs', function (Blueprint $table) {
                $cols = array_filter(
                    ['ocorrencias_count', 'reaberto', 'ultima_ocorrencia_em', 'ocorrencias_historico'],
                    fn($c) => Schema::hasColumn('error_logs', $c)
                );
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};