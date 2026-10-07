<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Adicionar campos de Reabertura na tabela de Orçamentos
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->boolean('reabertura_solicitada')->default(false)->after('status');
            $table->text('reabertura_motivo')->nullable()->after('reabertura_solicitada');
            $table->dateTime('prazo_edicao')->nullable()->after('reabertura_motivo');
        });

        // 2. Ajustar a tabela de Avaliações (Logs)
        Schema::table('orcamento_avaliacoes', function (Blueprint $table) {
            // Volta a adicionar a ligação ao Cabeçalho do Orçamento (Log Geral)
            $table->foreignId('orcamento_id')->nullable()->after('id')->constrained('orcamentos')->cascadeOnDelete();
            
            // Permite que o log não tenha um item específico (para ser um Log Geral)
            $table->foreignId('orcamento_item_id')->nullable()->change();
            
            // O comentário passa a ser opcional conforme solicitado
            $table->text('comentario')->nullable()->change();
            
            // Adicionamos um campo para o tipo de evento (ex: 'avaliacao_item', 'solicitacao_reabertura', 'edicao_item_aprovado')
            $table->string('tipo_evento')->default('avaliacao_item')->after('status_aplicado');
        });
    }

    public function down(): void
    {
        Schema::table('orcamento_avaliacoes', function (Blueprint $table) {
            $table->dropForeign(['orcamento_id']);
            $table->dropColumn(['orcamento_id', 'tipo_evento']);
            $table->foreignId('orcamento_item_id')->nullable(false)->change();
            $table->text('comentario')->nullable(false)->change();
        });

        Schema::table('orcamentos', function (Blueprint $table) {
            $table->dropColumn(['reabertura_solicitada', 'reabertura_motivo', 'prazo_edicao']);
        });
    }
};