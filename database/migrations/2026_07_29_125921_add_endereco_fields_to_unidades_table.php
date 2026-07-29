<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unidades', function (Blueprint $table) {
            $table->string('cep', 10)->nullable()->after('data_inauguracao');
            $table->string('estado', 2)->nullable()->after('cep');
            $table->string('cidade')->nullable()->after('estado');
            $table->string('bairro')->nullable()->after('cidade');
            $table->string('logradouro')->nullable()->after('bairro');
            $table->string('numero')->nullable()->after('logradouro');
            $table->string('complemento')->nullable()->after('numero');
            
            // Mantemos a coluna original permitindo null para retrocompatibilidade
            $table->string('endereco')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('unidades', function (Blueprint $table) {
            $table->dropColumn(['cep', 'estado', 'cidade', 'bairro', 'logradouro', 'numero', 'complemento']);
            $table->string('endereco')->nullable(false)->change();
        });
    }
};