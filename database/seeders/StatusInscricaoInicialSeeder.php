<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StatusInscricao;

class StatusInscricaoInicialSeeder extends Seeder
{
    public function run(): void
    {
        // Garante que o status para abandonos/incompletos exista
        StatusInscricao::updateOrCreate(
            ['nome' => 'Inscrição Incompleta'],
            [
                'descricao' => 'O candidato iniciou o preenchimento, mas não concluiu a última etapa.',
                'cor' => 'gray', // ou a padronização de cores que você utiliza
            ]
        );

        // Opcional: Garante que um status de fallback de sucesso exista
        // caso o administrador crie um ciclo mas esqueça de configurar o funil
        StatusInscricao::updateOrCreate(
            ['nome' => 'Inscrição Finalizada'],
            [
                'descricao' => 'Inscrição concluída com sucesso (Status padrão de segurança).',
                'cor' => 'blue'
            ]
        );
    }
}