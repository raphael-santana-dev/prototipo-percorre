<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('email_templates', 'canal')) {
                $table->string('canal', 30)->default('email')->after('nome'); // 'email', 'whatsapp', 'ambos'
            }
            if (!Schema::hasColumn('email_templates', 'botoes')) {
                $table->json('botoes')->nullable()->after('corpo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('email_templates', 'canal')) $cols[] = 'canal';
            if (Schema::hasColumn('email_templates', 'botoes')) $cols[] = 'botoes';
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};