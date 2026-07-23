<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Student\Domain\Models\Student;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        Student::updateOrCreate(
            ['email' => 'aluno@percorre.com'],
            [
                'name' => 'Aluno de Teste',
                'password' => Hash::make('123'),
                'is_active' => true,
            ]
        );
    }
}