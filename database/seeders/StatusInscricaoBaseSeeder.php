<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\StatusInscricao;

class StatusInscricaoBaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StatusInscricao::firstOrCreate(
            [
                'nome' => 'Pendente',
                'descricao' => 'Etapa padrão e obrigatória do sistema.',
            ]
        );

        StatusInscricao::firstOrCreate(
            [
                'nome' => 'Em Análise',
                'descricao' => 'Etapa padrão e obrigatória do sistema.',
            ]
        );

        StatusInscricao::firstOrCreate(
            [
                'nome' => 'Aprovado',
                'descricao' => 'Etapa padrão e obrigatória do sistema.',
            ]
        );

        StatusInscricao::firstOrCreate(
            [
                'nome' => 'Reprovado',
                'descricao' => 'Etapa padrão e obrigatória do sistema.',
            ]
        );
    }
}
