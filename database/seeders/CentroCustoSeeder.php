<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Financeiro\Domain\Models\CentroCusto;

class CentroCustoSeeder extends Seeder
{
    public function run(): void
    {
        CentroCusto::firstOrCreate(['codigo' => '001'], ['nome' => 'Tecnologia da Informação', 'disponivel_orcamento' => true]);
        CentroCusto::firstOrCreate(['codigo' => '002'], ['nome' => 'Recursos Humanos', 'disponivel_orcamento' => true]);
        CentroCusto::firstOrCreate(['codigo' => '003'], ['nome' => 'Diretoria Executiva', 'disponivel_orcamento' => true]);
        CentroCusto::firstOrCreate(['codigo' => '004'], ['nome' => 'Marketing', 'disponivel_orcamento' => true]);
    }
}