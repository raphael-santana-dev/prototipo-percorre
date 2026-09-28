<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Renomeia as tabelas antigas para a nova arquitetura
        Schema::rename('importacoes', 'system_tasks');
        Schema::rename('importacao_configs', 'system_task_configs');
    }

    public function down(): void
    {
        // Permite reverter caso algo dê errado
        Schema::rename('system_tasks', 'importacoes');
        Schema::rename('system_task_configs', 'importacao_configs');
    }
};