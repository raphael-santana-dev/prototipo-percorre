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
        // A lista exata na ordem solicitada
        $listaStatus = [
            'Contato realizado, aguardando retorno',
            'Inscrição Completa',
            'Documentação',
            'Assinatura Termo de Matrícula',
            'Aprovado', // Já existe no BaseSeeder, será apenas recuperado
            'Desistente',
            'Aprovado e não matriculado',
            'Reprovado', // Já existe no BaseSeeder, será apenas recuperado
            'Documentação Incompleta',
            'Envio Documentação Pendente',
            'Lista de espera',
            'Candidatos PCDs'
        ];

        // Busca todos os ciclos cadastrados no sistema
        $ciclos = Ciclo::all();

        if ($ciclos->isEmpty()) {
            $this->command->info('Nenhum ciclo encontrado para vincular os status. Execute o seeder de Ciclos primeiro.');
            return;
        }

        foreach ($ciclos as $ciclo) {
            foreach ($listaStatus as $index => $nomeStatus) {
                
                // 1. Cria o Status se não existir, ou pega o ID se já existir (Ignorando os do BaseSeeder)
                $status = StatusInscricao::firstOrCreate(
                    ['nome' => $nomeStatus],
                    ['descricao' => 'Status estrutural do Funil (CRM).']
                );

                // 2. Relaciona o Status com o Ciclo atual respeitando a Ordem do Array
                DB::table('ciclo_status_inscricao')->updateOrInsert(
                    [
                        'ciclo_id' => $ciclo->id,
                        'status_inscricao_id' => $status->id
                    ],
                    [
                        'ordem' => $index + 1, // +1 para a ordem começar no 1 e não no 0
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }
        }

        $this->command->info('Os status do funil foram criados e ordenados com sucesso no(s) ciclo(s)!');
    }
}
