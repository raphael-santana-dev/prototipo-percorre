<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('razao_social');
            $table->string('nome_fantasia')->nullable();
            $table->string('cnpj')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('company_users', function (Blueprint $table) {
            $table->dropColumn('empresa_codigo');
            $table->foreignId('empresa_id')->nullable()->after('documento')->constrained('empresas')->nullOnDelete();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('empresa_codigo');
            $table->foreignId('empresa_id')->nullable()->after('unidade_id')->constrained('empresas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->dropColumn('empresa_id');
            $table->string('empresa_codigo')->nullable();
        });

        Schema::table('company_users', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->dropColumn('empresa_id');
            $table->string('empresa_codigo')->nullable();
        });

        Schema::dropIfExists('empresas');
    }
};