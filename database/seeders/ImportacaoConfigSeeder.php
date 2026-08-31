<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ImportacaoConfig;

class ImportacaoConfigSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            [
                'coluna' => 'curso_id',
                'model_class' => 'App\Models\Curso',
                'campo_busca' => 'nome',
                'auto_cadastro' => true,
                'payload_padrao' => [
                    'status' => 'Ativo',
                    'permite_estado_diferente' => false
                ],
            ],
            [
                'coluna' => 'unidade_id',
                'model_class' => 'App\Modules\Unidade\Domain\Models\Unidade',
                'campo_busca' => 'nome',
                'auto_cadastro' => true,
                'payload_padrao' => [
                    'status' => 'Ativa'
                ],
            ],
            [
                'coluna' => 'turno_id',
                'model_class' => 'App\Modules\Turno\Domain\Models\Turno',
                'campo_busca' => 'nome',
                'auto_cadastro' => true,
                'payload_padrao' => null,
            ],
        ];

        foreach ($configs as $config) {
            ImportacaoConfig::updateOrCreate(
                ['coluna' => $config['coluna']], // Condição de busca (evita duplicatas)
                $config // Dados a serem atualizados ou criados
            );
        }
    }
}