<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::table('turnos', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
        });

        Schema::table('status_inscricoes', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
            $table->boolean('status')->default(true);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
            $table->string('cpf', 14)->unique()->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
            $table->string('cpf', 14)->unique()->nullable();
        });

        Schema::table('features', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
        });

         Schema::table('roles', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
            $table->boolean('status')->default(true);
        });

        Schema::table('inscricoes', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
            $table->boolean('status')->default(true);
        });

        Schema::table('ciclos', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropColumn('slug');
        });

        Schema::table('status_inscricoes', function (Blueprint $table) {
            $table->dropColumn('slug');
            $table->dropColumn('status');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('slug');
            $table->dropColumn('cpf');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('slug');
            $table->dropColumn('cpf');
        });

        Schema::table('features', function (Blueprint $table) {
            $table->dropColumn('slug');
        });

         Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('slug');
            $table->dropColumn('status');
        });

        Schema::table('inscricoes', function (Blueprint $table) {
            $table->dropColumn('slug');
            $table->dropColumn('status');
        });

        Schema::table('ciclos', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
