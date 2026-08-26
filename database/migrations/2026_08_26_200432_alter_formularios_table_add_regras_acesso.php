<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formularios', function (Blueprint $table) {
            // Define o tipo de formulário (inscricao, geral, aprendizagem)
            $table->string('tipo')->default('geral')->after('slug');
            
            // Travas de tempo
            $table->dateTime('data_inicio')->nullable()->after('descricao');
            $table->dateTime('data_fim')->nullable()->after('data_inicio');
            
            // Travas de Acesso
            $table->boolean('acesso_livre')->default(true)->after('status')->comment('Se true, qualquer pessoa com o link acessa');
            $table->boolean('apenas_estudantes')->default(false)->after('acesso_livre')->comment('Se true, exige login de aluno');
            $table->json('roles_permitidas')->nullable()->after('apenas_estudantes')->comment('IDs ou nomes das roles (users web)');
            $table->json('users_permitidos')->nullable()->after('roles_permitidas')->comment('IDs específicos de usuários que podem responder');
        });
    }

    public function down(): void
    {
        Schema::table('formularios', function (Blueprint $table) {
            $table->dropColumn([
                'tipo', 'data_inicio', 'data_fim', 'acesso_livre', 
                'apenas_estudantes', 'roles_permitidas', 'users_permitidos'
            ]);
        });
    }
};