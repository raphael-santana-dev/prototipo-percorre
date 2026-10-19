<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Datas de Período de Matrícula na tabela ciclos
        Schema::table('ciclos', function (Blueprint $table) {
            if (!Schema::hasColumn('ciclos', 'data_inicio_matricula')) {
                $table->dateTime('data_inicio_matricula')->nullable()->after('data_fim');
            }
            if (!Schema::hasColumn('ciclos', 'data_fim_matricula')) {
                $table->dateTime('data_fim_matricula')->nullable()->after('data_inicio_matricula');
            }
        });

        // 2. Tabela de registros de contato (E-mail, WhatsApp, Telefone) por inscrição
        Schema::create('inscricao_contatos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inscricao_id')->index();
            $table->string('canal', 20)->index(); // 'email', 'whatsapp', 'telefone'
            $table->dateTime('data_contato')->nullable();
            $table->text('conteudo')->nullable();
            $table->boolean('houve_resposta')->default(false);
            $table->text('resposta_conteudo')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_nome')->nullable();
            $table->timestamps();

            $table->foreign('inscricao_id')->references('id')->on('inscricoes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscricao_contatos');

        Schema::table('ciclos', function (Blueprint $table) {
            $table->dropColumn(['data_inicio_matricula', 'data_fim_matricula']);
        });
    }
};