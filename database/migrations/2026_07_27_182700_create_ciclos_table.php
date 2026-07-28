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
        Schema::create('ciclos', function (Blueprint $table) {
            $table->id();
            $table->string('nome'); // Ex: "Processo Seletivo 2026.1"
            $table->integer('ano'); // Ex: 2026
            $table->integer('semestre'); // Ex: 1 ou 2
            $table->dateTime('data_inicio');
            $table->dateTime('data_fim');
            $table->boolean('status')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ciclos');
    }
};
