<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('email_templates', 'configuracoes_suri')) {
                $table->json('configuracoes_suri')->nullable()->after('botoes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            if (Schema::hasColumn('email_templates', 'configuracoes_suri')) {
                $table->dropColumn('configuracoes_suri');
            }
        });
    }
};