<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Financeiro\Domain\Models\Natureza;

class NaturezaSeeder extends Seeder
{
    public function run(): void
    {
        Natureza::firstOrCreate(['codigo' => 'D01022'], ['descricao' => 'Despesas com Licenças e Softwares', 'disponivel_orcamento' => true]);
        Natureza::firstOrCreate(['codigo' => 'D01001'], ['descricao' => 'Salários e Ordenados', 'disponivel_orcamento' => true]);
        Natureza::firstOrCreate(['codigo' => 'D01013'], ['descricao' => 'Encargos Trabalhistas', 'disponivel_orcamento' => true]);
        Natureza::firstOrCreate(['codigo' => 'D01016'], ['descricao' => 'Benefícios (VR/VA/VT)', 'disponivel_orcamento' => true]);
    }
}