<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Cabeçalho da Esteira e Regras de Segmentação do Público
        Schema::create('comunicacao_esteiras', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->boolean('status')->default(true);

            // Público-alvo: 'inscricoes', 'formulario_publico', 'estudantes', 'usuarios', 'lista_manual'
            $table->string('publico_alvo')->default('inscricoes');
            $table->foreignId('ciclo_id')->nullable()->constrained('ciclos')->nullOnDelete();
            $table->foreignId('formulario_id')->nullable()->constrained('formularios')->nullOnDelete();

            // Condições de entrada (status_ids, unidades_ids, cursos_ids, turnos_ids, etapa_funil, regras_dinamicas, ids_manuais)
            $table->json('condicoes')->nullable();

            // Condições de saída / conversão (ex: sair se finalizar inscrição ou atingir status X)
            $table->json('condicao_saida')->nullable();

            // Boas práticas de cadência
            $table->boolean('revalidar_condicoes_no_envio')->default(true);
            $table->boolean('apenas_horario_comercial')->default(false);

            $table->unsignedBigInteger('criado_por')->nullable();
            $table->timestamps();
        });

        // 2. Etapas Sequenciais da Esteira (Passo 1, Passo 2, Passo N...)
        Schema::create('comunicacao_esteira_etapas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('esteira_id')->constrained('comunicacao_esteiras')->cascadeOnDelete();
            $table->unsignedInteger('ordem')->default(1);
            $table->string('nome');
            $table->foreignId('template_id')->constrained('email_templates')->cascadeOnDelete();
            $table->string('tipo_acao')->default('enviar_email'); // 'enviar_email' ou 'criar_aluno_enviar_email'

            // Tipo de agendamento: 'data_fixa', 'apos_etapa_anterior', 'imediato'
            $table->string('tipo_agendamento')->default('data_fixa');
            $table->dateTime('data_disparo')->nullable();
            $table->unsignedInteger('atraso_valor')->nullable(); // Ex: 7
            $table->string('atraso_unidade')->default('dias');   // 'horas' ou 'dias'

            // Condição de engajamento em relação à etapa anterior: 'todos', 'nao_abriu', 'abriu', 'nao_clicou', 'clicou'
            $table->string('filtro_engajamento_anterior')->default('todos');

            // Filtro extra específico desta etapa (ex: mudar ou restringir status na etapa 2)
            $table->json('condicoes_etapa')->nullable();

            $table->timestamps();
        });

        // 3. Controle Individual de Execução por Destinatário e Etapa (Idempotência e Rastreio)
        Schema::create('comunicacao_esteira_disparos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('esteira_id')->constrained('comunicacao_esteiras')->cascadeOnDelete();
            $table->foreignId('etapa_id')->constrained('comunicacao_esteira_etapas')->cascadeOnDelete();

            $table->string('destinatario_email')->index();
            $table->string('destinatario_nome')->nullable();
            $table->string('entidade_tipo')->nullable(); // 'inscricao', 'resposta_formulario', 'student', 'user', 'manual'
            $table->unsignedBigInteger('entidade_id')->nullable()->index();

            // Vínculo com o log de comunicado para verificar aberturas e cliques nas etapas seguintes
            $table->unsignedBigInteger('comunicado_id')->nullable()->index();

            // 'enviado', 'ignorado_condicao', 'ignorado_saida', 'ignorado_engajamento', 'erro'
            $table->string('status')->default('enviado');
            $table->string('motivo_status')->nullable();
            $table->timestamp('enviado_em')->nullable();
            $table->timestamps();

            $table->unique(
                ['esteira_id', 'etapa_id', 'destinatario_email', 'entidade_id'],
                'uk_esteira_etapa_destinatario'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comunicacao_esteira_disparos');
        Schema::dropIfExists('comunicacao_esteira_etapas');
        Schema::dropIfExists('comunicacao_esteiras');
    }
};