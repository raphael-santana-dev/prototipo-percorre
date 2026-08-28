<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('importacao_configs', function (Blueprint $table) {
            $table->id();
            $table->string('coluna')->unique(); // ex: curso_id
            $table->string('model_class');      // ex: App\Models\Curso
            $table->string('campo_busca')->default('nome'); 
            $table->boolean('auto_cadastro')->default(false);
            $table->json('payload_padrao')->nullable(); // ex: {"status": "Ativo"}
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('importacao_configs');
    }
};
