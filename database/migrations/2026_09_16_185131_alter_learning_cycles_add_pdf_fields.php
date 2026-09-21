<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ciclos_aprendizagem', function (Blueprint $table) {
            $table->string('ano', 4)->nullable()->after('nome');
            $table->string('ciclo_mes', 2)->nullable()->after('ano')->comment('05=Maio, 11=Novembro');
            $table->integer('prazo_dias')->nullable()->after('data_fim');
            $table->date('data_fechamento')->nullable()->after('prazo_dias');
        });

        Schema::table('aluno_ciclo_aprendizagem', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('aluno_ciclo_aprendizagem', function (Blueprint $table) {
            $table->string('status', 1)->default('1')->after('fase_atual_id')
                  ->comment('1=Gerada, 2=Enviada/Pendente, 3=Respondida, 4=Atrasada, 5=Fechada');
            $table->date('data_geracao')->nullable()->after('status');
            $table->date('data_envio')->nullable()->after('data_geracao');
            $table->date('data_prazo')->nullable()->after('data_envio');
            $table->date('data_resposta')->nullable()->after('data_prazo');
        });
    }

    public function down(): void
    {
        Schema::table('aluno_ciclo_aprendizagem', function (Blueprint $table) {
            $table->dropColumn(['status', 'data_geracao', 'data_envio', 'data_prazo', 'data_resposta']);
        });

        Schema::table('aluno_ciclo_aprendizagem', function (Blueprint $table) {
            $table->enum('status', ['pendente', 'em_andamento', 'concluido'])->default('pendente');
        });

        Schema::table('ciclos_aprendizagem', function (Blueprint $table) {
            $table->dropColumn(['ano', 'ciclo_mes', 'prazo_dias', 'data_fechamento']);
        });
    }
};