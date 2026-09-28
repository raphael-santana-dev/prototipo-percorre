<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemTaskConfig; // <-- Alterado para o novo Model

class SystemTaskConfigSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            [
                'coluna' => 'unidade_id',
                'model_class' => '\App\Modules\Unidade\Domain\Models\Unidade',
                'campo_busca' => 'nome',
                'auto_cadastro' => true,
                'payload_padrao' => ['status' => 'Ativa']
            ],
            [
                'coluna' => 'curso_id',
                'model_class' => '\App\Models\Curso',
                'campo_busca' => 'nome',
                'auto_cadastro' => true,
                'payload_padrao' => ['status' => 'Ativo', 'permite_estado_diferente' => false]
            ],
            [
                'coluna' => 'turno_id',
                'model_class' => '\App\Modules\Turno\Domain\Models\Turno',
                'campo_busca' => 'nome',
                'auto_cadastro' => true,
                'payload_padrao' => ['horario_inicio' => '00:00:00']
            ],
        ];

        foreach ($configs as $config) {
            SystemTaskConfig::updateOrCreate( // <-- Alterado para o novo Model
                ['coluna' => $config['coluna']],
                $config 
            );
        }
    }
}