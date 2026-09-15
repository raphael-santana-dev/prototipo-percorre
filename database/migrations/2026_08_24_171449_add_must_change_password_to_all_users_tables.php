<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(true)->after('password');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(true)->after('password');
        });

        Schema::table('company_users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(true)->after('password');
        });

        DB::table('users')->update(['must_change_password' => false]);
        DB::table('students')->update(['must_change_password' => false]);
        DB::table('company_users')->update(['must_change_password' => false]);
    }

    public function down(): void
    {
        Schema::table('company_users', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });
    }
};