<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rd_crm_pipelines', function (Blueprint $table) {
            $table->id();
            $table->string('rd_id')->unique()->comment('ID original do RD Station');
            $table->string('nome');
            $table->integer('ordem')->default(0);
            $table->timestamps();
        });

        Schema::create('rd_crm_deal_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rd_crm_pipeline_id')->constrained('rd_crm_pipelines')->cascadeOnDelete();
            $table->string('rd_id')->unique()->comment('ID original da etapa no RD Station');
            $table->string('nome');
            $table->string('nickname')->nullable();
            $table->integer('ordem')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rd_crm_deal_stages');
        Schema::dropIfExists('rd_crm_pipelines');
    }
};