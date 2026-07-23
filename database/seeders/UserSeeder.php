<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'dev@percorre.com'], // Mude para o seu e-mail se quiser
            [
                'name' => 'Desenvolvedor',
                'password' => Hash::make('123'), // Senha padrão
            ]
        );

        // Atribui a role 'dev' ao usuário
        $user->assignRole('dev');
    }
}
