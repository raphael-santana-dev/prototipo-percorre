<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Limpa o cache de permissões do Spatie para evitar bugs
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Cria a Role DEV (acesso total)
        Role::firstOrCreate(['name' => 'dev', 'guard_name' => 'web']);

        // Cria a Role ADMIN (acesso administrativo gerenciado)
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }
}