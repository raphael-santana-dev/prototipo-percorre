<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Adiciona a coluna nas duas tabelas
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('submodule')->nullable()->after('module');
        });

        Schema::table('features', function (Blueprint $table) {
            $table->string('submodule')->nullable()->after('module');
        });

        // 2. Separa os dados existentes (Ex: transforma 'acl.permissao' em module='acl' e submodule='permissao')
        $permissions = DB::table('permissions')->get();
        foreach($permissions as $p) {
            if(str_contains($p->module, '.')) {
                $parts = explode('.', $p->module, 2);
                DB::table('permissions')->where('id', $p->id)->update([
                    'module' => $parts[0], 
                    'submodule' => $parts[1]
                ]);
            }
        }

        $features = DB::table('features')->get();
        foreach($features as $f) {
            if(str_contains($f->module, '.')) {
                $parts = explode('.', $f->module, 2);
                DB::table('features')->where('id', $f->id)->update([
                    'module' => $parts[0], 
                    'submodule' => $parts[1]
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('submodule');
        });

        Schema::table('features', function (Blueprint $table) {
            $table->dropColumn('submodule');
        });
    }
};