<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Log bruto de eventos recebidos (Tolerância a falhas e Idempotência)
        Schema::create('suri_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id', 191)->unique(); // Formato {chatbotId}:{guid}
            $table->string('chatbot_id', 100)->nullable()->index();
            $table->string('event_type', 60)->index();
            $table->timestampTz('event_timestamp')->nullable();
            $table->jsonb('payload');
            $table->string('status', 30)->default('pending')->index(); // pending, processed, failed
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        // 2. Contatos sincronizados da Suri e vinculados às Inscrições do sistema
        Schema::create('suri_contatos', function (Blueprint $table) {
            $table->id();
            $table->string('suri_user_id', 120)->unique(); // user.Id
            $table->foreignId('inscricao_id')->nullable()->constrained('inscricoes')->nullOnDelete();
            $table->string('nome')->nullable();
            $table->string('telefone', 40)->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('documento', 40)->nullable()->index();
            $table->string('channel_id', 120)->nullable();
            $table->integer('channel_type')->nullable();
            $table->string('queue_status', 40)->default('None')->index(); // None, Requested, InAttendance
            $table->string('attendant_id', 120)->nullable();
            $table->string('attendant_name')->nullable();
            $table->string('attendant_email')->nullable();
            $table->string('department_id', 120)->nullable();
            $table->jsonb('tags')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('ultima_interacao_em')->nullable();
            $table->timestamps();
        });

        // 3. Mensagens (Recebidas, Enviadas, Entregues, Lidas e Erros de Envio)
        Schema::create('suri_mensagens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('suri_contato_id')->nullable()->constrained('suri_contatos')->cascadeOnDelete();
            $table->foreignId('inscricao_id')->nullable()->constrained('inscricoes')->nullOnDelete();
            $table->string('external_id', 191)->index(); // mid (message-received) ou id (message-sent / messageIds)
            $table->string('direcao', 10)->default('out')->index(); // 'in' (recebida) ou 'out' (enviada)
            $table->integer('message_type')->nullable(); // Enum de tipo na Suri (0 = texto, etc.)
            $table->text('texto')->nullable();
            $table->boolean('is_echo')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->string('status', 30)->default('sent')->index(); // received, sent, delivered, read, error
            $table->smallInteger('status_weight')->default(1); // 1=sent/received, 2=delivered, 3=read, -1=error
            $table->unsignedBigInteger('sent_timestamp_ms')->nullable()->index();
            $table->string('attendant_id', 120)->nullable();
            $table->string('attendant_name')->nullable();
            $table->string('channel_id', 120)->nullable();
            $table->integer('error_code')->nullable();
            $table->text('error_reason')->nullable();
            $table->jsonb('attachments')->nullable();
            $table->jsonb('extra_payload')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->unique(['external_id', 'direcao']);
        });

        // 4. Histórico de Atendimentos Humanos Finalizados (finish-attendance)
        Schema::create('suri_atendimentos', function (Blueprint $table) {
            $table->id();
            $table->string('event_id', 191)->unique();
            $table->foreignId('suri_contato_id')->constrained('suri_contatos')->cascadeOnDelete();
            $table->foreignId('inscricao_id')->nullable()->constrained('inscricoes')->nullOnDelete();
            $table->string('department_id', 120)->nullable();
            $table->string('attendant_id', 120)->nullable();
            $table->string('attendant_name')->nullable();
            $table->string('attendant_email')->nullable();
            $table->string('attendance_time_raw', 60)->nullable(); // Ex: 00:16:23.1200000
            $table->integer('duracao_segundos')->default(0);
            $table->jsonb('user_tags')->nullable();
            $table->jsonb('messages')->nullable(); // Todas as ChatMessage[] trocadas no atendimento
            $table->timestamp('finalizado_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suri_atendimentos');
        Schema::dropIfExists('suri_mensagens');
        Schema::dropIfExists('suri_contatos');
        Schema::dropIfExists('suri_webhook_events');
    }
};