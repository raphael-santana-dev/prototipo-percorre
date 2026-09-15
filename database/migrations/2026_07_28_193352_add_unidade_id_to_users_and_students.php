<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE inscricoes ALTER COLUMN pontuacao_detalhes TYPE json USING pontuacao_detalhes::json');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE inscricoes ALTER COLUMN pontuacao_detalhes TYPE varchar(255)');
    }
};