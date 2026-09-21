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
        
        $atrasadas = AlunoCicloAprendizagem::where('status', '2')
            ->whereDate('data_prazo', '<', $hoje)
            ->update(['status' => '4']);
            
        $this->info("Atualizadas {$atrasadas} avaliações para o status ATRASADA.");

        $ciclosVencidos = CicloAprendizagem::where('status', true)
            ->whereDate('data_fechamento', '<', $hoje)
            ->get();

        foreach ($ciclosVencidos as $ciclo) {
            $ciclo->update(['status' => false]);
            
            AlunoCicloAprendizagem::where('ciclo_aprendizagem_id', $ciclo->id)
                ->whereIn('status', ['2', '4'])
                ->update(['status' => '5']);
                
            $this->info("O Ciclo {$ciclo->nome} foi encerrado definitivamente.");
        }
    }
}