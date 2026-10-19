<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comunicacao_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('comunicacao_logs', 'tracking_token')) {
                $table->uuid('tracking_token')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('comunicacao_logs', 'inscricao_id')) {
                $table->unsignedBigInteger('inscricao_id')->nullable()->index()->after('comunicado_id');
            }
            if (!Schema::hasColumn('comunicacao_logs', 'template_id')) {
                $table->unsignedBigInteger('template_id')->nullable()->index()->after('inscricao_id');
            }
            if (!Schema::hasColumn('comunicacao_logs', 'aberturas_count')) {
                $table->unsignedInteger('aberturas_count')->default(0);
                $table->timestamp('aberto_em')->nullable();
                $table->timestamp('ultima_abertura_em')->nullable();
            }
            if (!Schema::hasColumn('comunicacao_logs', 'cliques_count')) {
                $table->unsignedInteger('cliques_count')->default(0);
                $table->timestamp('clicado_em')->nullable();
                $table->timestamp('ultimo_clique_em')->nullable();
            }
        });

        Schema::table('comunicados', function (Blueprint $table) {
            if (!Schema::hasColumn('comunicados', 'aberturas_count')) {
                $table->unsignedInteger('aberturas_count')->default(0);
                $table->timestamp('aberto_em')->nullable();
                $table->timestamp('ultima_abertura_em')->nullable();
            }
            if (!Schema::hasColumn('comunicados', 'cliques_count')) {
                $table->unsignedInteger('cliques_count')->default(0);
                $table->timestamp('clicado_em')->nullable();
                $table->timestamp('ultimo_clique_em')->nullable();
            }
        });

        Schema::create('comunicacao_log_eventos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('comunicacao_log_id')->index();
            $table->unsignedBigInteger('comunicado_id')->nullable()->index();
            $table->string('tipo', 20)->index(); // 'abertura' ou 'clique'
            $table->text('url_destino')->nullable();
            $table->string('texto_link')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('dispositivo', 60)->nullable(); // Desktop, Mobile, Gmail Proxy, etc.
            $table->string('navegador', 60)->nullable();
            $table->string('sistema_operacional', 60)->nullable();
            $table->timestamps();

            $table->foreign('comunicacao_log_id')
                  ->references('id')
                  ->on('comunicacao_logs')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comunicacao_log_eventos');
    }
};