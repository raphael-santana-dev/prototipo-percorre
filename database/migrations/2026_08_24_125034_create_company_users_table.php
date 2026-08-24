<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('documento')->nullable()->comment('CPF do Gestor ou CNPJ para o acesso principal');
            
            // Relacionamento com a tabela de empresas (Supondo que você tenha ou terá uma tabela 'empresas' sincronizada do Protheus)
            $table->string('empresa_codigo')->nullable()->comment('Código de integração da empresa no Protheus');
            
            // Define o nível de acesso dentro do painel da empresa
            $table->enum('tipo_acesso', ['contato_principal', 'gestor_avaliador'])->default('gestor_avaliador');
            
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_users');
    }
};