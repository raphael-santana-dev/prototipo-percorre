<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Garante vínculo student_id na tabela suri_contatos
        if (Schema::hasTable('suri_contatos') && !Schema::hasColumn('suri_contatos', 'student_id')) {
            Schema::table('suri_contatos', function (Blueprint $table) {
                $table->foreignId('student_id')
                    ->nullable()
                    ->after('inscricao_id')
                    ->constrained('students')
                    ->nullOnDelete();
            });
        }

        // 2. Adiciona canal_disparo ('email', 'whatsapp', 'ambos') nas Automações
        if (Schema::hasTable('automacoes') && !Schema::hasColumn('automacoes', 'canal_disparo')) {
            Schema::table('automacoes', function (Blueprint $table) {
                $table->string('canal_disparo', 20)->default('email')->after('template_id');
            });
        }

        // 3. Adiciona canal_disparo ('email', 'whatsapp', 'ambos') nas Etapas da Esteira
        if (Schema::hasTable('comunicacao_esteira_etapas') && !Schema::hasColumn('comunicacao_esteira_etapas', 'canal_disparo')) {
            Schema::table('comunicacao_esteira_etapas', function (Blueprint $table) {
                $table->string('canal_disparo', 20)->default('email')->after('template_id');
            });
        }

        // 4. Adiciona colunas de rastreamento de WhatsApp (Enviado, Entregue, Lido) em comunicacao_logs
        if (Schema::hasTable('comunicacao_logs')) {
            Schema::table('comunicacao_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('comunicacao_logs', 'canal')) {
                    $table->string('canal', 20)->default('email')->after('origem')->index();
                }
                if (!Schema::hasColumn('comunicacao_logs', 'suri_message_id')) {
                    $table->string('suri_message_id', 191)->nullable()->after('tracking_token')->index();
                }
                if (!Schema::hasColumn('comunicacao_logs', 'entregue_em')) {
                    $table->timestamp('entregue_em')->nullable()->after('data_envio');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('comunicacao_logs')) {
            Schema::table('comunicacao_logs', function (Blueprint $table) {
                $cols = array_filter(['canal', 'suri_message_id', 'entregue_em'], fn($c) => Schema::hasColumn('comunicacao_logs', $c));
                if (!empty($cols)) $table->dropColumn($cols);
            });
        }

        if (Schema::hasTable('comunicacao_esteira_etapas') && Schema::hasColumn('comunicacao_esteira_etapas', 'canal_disparo')) {
            Schema::table('comunicacao_esteira_etapas', fn(Blueprint $t) => $t->dropColumn('canal_disparo'));
        }

        if (Schema::hasTable('automacoes') && Schema::hasColumn('automacoes', 'canal_disparo')) {
            Schema::table('automacoes', fn(Blueprint $t) => $t->dropColumn('canal_disparo'));
        }

        if (Schema::hasTable('suri_contatos') && Schema::hasColumn('suri_contatos', 'student_id')) {
            Schema::table('suri_contatos', function (Blueprint $table) {
                $table->dropConstrainedForeignId('student_id');
            });
        }
    }
};