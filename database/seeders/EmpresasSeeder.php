<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmpresasSeeder extends Seeder
{
    public function run(): void
    {
        $empresas = [
            ['razao_social' => 'Citibank', 'cnpj' => '33.479.023/0001-80'],
            ['razao_social' => 'Dell', 'cnpj' => '72.381.189/0001-10'],
            ['razao_social' => 'TOTVS', 'cnpj' => '53.113.791/0001-22'],
            ['razao_social' => 'Microsoft', 'cnpj' => '04.712.500/0001-07'],
            ['razao_social' => 'Localiza', 'cnpj' => '16.670.085/0001-55'],
            ['razao_social' => 'Google', 'cnpj' => '06.990.590/0001-23'],
        ];

        foreach ($empresas as $empresa) {
            DB::table('empresas')->updateOrInsert(
                ['razao_social' => $empresa['razao_social']],
                [
                    'cnpj' => $empresa['cnpj'],
                    'nome_fantasia' => $empresa['razao_social'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}