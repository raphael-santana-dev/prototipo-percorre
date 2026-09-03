<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('solicitacoes', function (Blueprint $table) {
            $table->id();
            $table->string('tema'); // Ex: 'avaliacao_reabertura', 'avaliacao_aluno_fase'
            $table->morphs('solicitante'); // Pode ser App\Models\User ou App\Models\Student
            $table->foreignId('responsavel_id')->nullable()->constrained('users'); // Se houver um avaliador específico
            $table->text('justificativa');
            $table->text('resposta_admin')->nullable();
            $table->string('status')->default('pendente'); // pendente, aprovada, rejeitada, auto_aprovada
            $table->json('payload')->nullable(); // Dados extras flexíveis (Ex: IDs das avaliações)
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void {
        Schema::dropIfExists('solicitacoes');
    }
};