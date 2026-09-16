<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CicloAprendizagem;
use App\Models\AlunoCicloAprendizagem;

class ProcessarFechamentoCiclos extends Command
{
    protected $signature = 'aprendizagem:processar-fechamentos';
    protected $description = 'Verifica prazos e encerra avaliações atrasadas ou ciclos vencidos.';

    public function handle()
    {
        $hoje = now()->format('Y-m-d');
        
        // 1. Marca como "Atrasada" (4) quem passou do prazo mas o ciclo ainda está aberto
        $atrasadas = AlunoCicloAprendizagem::where('status', '2')
            ->whereDate('data_prazo', '<', $hoje)
            ->update(['status' => '4']);
            
        $this->info("Atualizadas {$atrasadas} avaliações para o status ATRASADA.");

        // 2. Trava e finaliza os ciclos cujo a Data de Fechamento já passou
        $ciclosVencidos = CicloAprendizagem::where('status', true)
            ->whereDate('data_fechamento', '<', $hoje)
            ->get();

        foreach ($ciclosVencidos as $ciclo) {
            $ciclo->update(['status' => false]);
            
            // Força todas as pendências e atrasos desse ciclo para "Fechada" (5)
            AlunoCicloAprendizagem::where('ciclo_aprendizagem_id', $ciclo->id)
                ->whereIn('status', ['2', '4'])
                ->update(['status' => '5']);
                
            $this->info("O Ciclo {$ciclo->nome} foi encerrado definitivamente.");
        }
    }
}