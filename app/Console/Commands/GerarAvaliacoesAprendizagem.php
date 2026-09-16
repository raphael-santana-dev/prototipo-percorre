<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CicloAprendizagem;
use App\Models\AlunoCicloAprendizagem;
use App\Modules\Student\Domain\Models\Student;

class GerarAvaliacoesAprendizagem extends Command
{
    protected $signature = 'aprendizagem:gerar-avaliacoes {ciclo_id}';
    protected $description = 'Gera e distribui as avaliações para aprendizes e gestores baseado no ciclo ativo.';

    public function handle()
    {
        $ciclo = CicloAprendizagem::with('fases')->findOrFail($this->argument('ciclo_id'));
        $this->info("Iniciando geração para o Ciclo: {$ciclo->nome}");

        // Filtro de Elegibilidade: Aprendizes ativos
        $query = Student::where('is_active', true)->where('vinculo_empregaticio', 2);
        
        // Regra do Mês: Até 31/03 (Ciclo 05) ou Até 30/09 (Ciclo 11)
        if ($ciclo->ciclo_mes === '05') {
            $query->whereDate('data_inicio_contrato', '<=', $ciclo->ano . '-03-31');
        } elseif ($ciclo->ciclo_mes === '11') {
            $query->whereDate('data_inicio_contrato', '<=', $ciclo->ano . '-09-30');
        }

        $aprendizes = $query->get();
        $countGerados = 0;

        foreach ($aprendizes as $aluno) {
            // Verifica se a avaliação já foi gerada para este aluno neste ciclo
            $existe = AlunoCicloAprendizagem::where('ciclo_aprendizagem_id', $ciclo->id)
                ->where('student_id', $aluno->id)
                ->exists();

            if (!$existe) {
                // Insere o Aluno na Fase 1 do Workflow
                $faseInicial = $ciclo->fases->where('ordem', 1)->first();
                
                AlunoCicloAprendizagem::create([
                    'ciclo_aprendizagem_id' => $ciclo->id,
                    'student_id' => $aluno->id,
                    'fase_atual_id' => $faseInicial->id,
                    'status' => '2', // Enviada/Pendente
                    'data_geracao' => now(),
                    'data_envio' => now(),
                    'data_prazo' => now()->addDays($ciclo->prazo_dias ?? 10),
                ]);

                // AQUI: Inserir a chamada para a sua classe de Template de E-mails
                // Exemplo: MailTemplateService::enviar($aluno->email, 'abertura_avaliacao', [...dados]);

                $countGerados++;
            }
        }

        $this->info("Geração concluída. {$countGerados} novas avaliações injetadas no fluxo.");
    }
}