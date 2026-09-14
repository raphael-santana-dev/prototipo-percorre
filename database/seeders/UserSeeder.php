<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'dev@percorre.com'],
            [
                'name' => 'Desenvolvedor',
                'password' => Hash::make('password'), 
            ]
        );

        $user->assignRole('dev');
    }
}
