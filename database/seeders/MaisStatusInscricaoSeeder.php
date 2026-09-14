<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StatusInscricao;
use App\Models\Ciclo;
use Illuminate\Support\Facades\DB;

class MaisStatusInscricaoSeeder extends Seeder
{
    public function run(): void
    {
        $listaStatus = [
            'Contato realizado, aguardando retorno',
            'Inscrição Completa',
            'Documentação',
            'Assinatura Termo de Matrícula',
            'Aprovado', 
            'Desistente',
            'Aprovado e não matriculado',
            'Reprovado', 
            'Documentação Incompleta',
            'Envio Documentação Pendente',
            'Lista de espera',
            'Candidatos PCDs'
        ];

        $ciclos = Ciclo::all();

        if ($ciclos->isEmpty()) {
            $this->command->info('Nenhum ciclo encontrado para vincular os status. Execute o seeder de Ciclos primeiro.');
            return;
        }

        foreach ($ciclos as $ciclo) {
            foreach ($listaStatus as $index => $nomeStatus) {
                
                $status = StatusInscricao::firstOrCreate(
                    ['nome' => $nomeStatus],
                    ['descricao' => 'Status estrutural do Funil (CRM).']
                );

                DB::table('ciclo_status_inscricao')->updateOrInsert(
                    [
                        'ciclo_id' => $ciclo->id,
                        'status_inscricao_id' => $status->id
                    ],
                    [
                        'ordem' => $index + 1, 
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }
        }

        $this->command->info('Os status do funil foram criados e ordenados com sucesso no(s) ciclo(s)!');
    }
}
