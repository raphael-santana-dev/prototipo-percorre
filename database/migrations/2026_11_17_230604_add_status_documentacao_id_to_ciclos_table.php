<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ciclos') && !Schema::hasColumn('ciclos', 'status_documentacao_id')) {
            Schema::table('ciclos', function (Blueprint $table) {
                $table->foreignId('status_documentacao_id')
                    ->nullable()
                    ->after('status')
                    ->constrained('status_inscricoes')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ciclos') && Schema::hasColumn('ciclos', 'status_documentacao_id')) {
            Schema::table('ciclos', function (Blueprint $table) {
                $table->dropConstrainedForeignId('status_documentacao_id');
            });
        }
    }
};