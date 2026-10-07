<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('centro_custo_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_custo_id')->constrained('centros_custo')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            
            // Coluna para controlar a permissão temporária
            $table->date('expires_at')->nullable()->comment('Se preenchido, o acesso expira nesta data');
            
            $table->timestamps();
            
            // Evitar duplicados da mesma combinação exata
            $table->unique(['centro_custo_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('centro_custo_user');
    }
};