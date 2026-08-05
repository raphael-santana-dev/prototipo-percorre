<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Força o PostgreSQL a converter o texto existente em JSON nativo
        DB::statement('ALTER TABLE inscricoes ALTER COLUMN pontuacao_detalhes TYPE json USING pontuacao_detalhes::json');
    }

    public function down(): void
    {
        // Reverte para o padrão caso faça um rollback
        DB::statement('ALTER TABLE inscricoes ALTER COLUMN pontuacao_detalhes TYPE varchar(255)');
    }
};