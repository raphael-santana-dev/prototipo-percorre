<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Etapa;

class EtapaSeeder extends Seeder
{
    public function run(): void
    {
        Etapa::firstOrCreate(
            ['numero' => 1], // Condição de busca
            [
                'nome' => 'Dados Pessoais',
                'descricao' => 'Etapa padrão e obrigatória do sistema.',
            ]
        );
    }
}