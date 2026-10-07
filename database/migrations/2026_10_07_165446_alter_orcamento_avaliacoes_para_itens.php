<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Limpar avaliações antigas (pois elas apontam para o cabeçalho)
        \DB::table('orcamento_avaliacoes')->delete();

        // 2. Adicionar o status individual a cada linha (Item)
        Schema::table('orcamento_itens', function (Blueprint $table) {
            // O status do item pode ser: Criado, Aprovado, Reprovado, Aprovado com ressalvas
            $table->string('status')->default('Criado')->after('descricao');
        });

        // 3. Mudar a referência da tabela de avaliações (logs) do Orcamento para o Item
        Schema::table('orcamento_avaliacoes', function (Blueprint $table) {
            $table->dropForeign(['orcamento_id']);
            $table->dropColumn('orcamento_id');
            
            $table->foreignId('orcamento_item_id')->after('id')->constrained('orcamento_itens')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        // ... (Reversão)
    }
};