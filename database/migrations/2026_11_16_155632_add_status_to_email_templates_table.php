<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('email_templates') && !Schema::hasColumn('email_templates', 'status')) {
            Schema::table('email_templates', function (Blueprint $table) {
                $table->boolean('status')->default(true)->after('canal');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('email_templates') && Schema::hasColumn('email_templates', 'status')) {
            Schema::table('email_templates', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};