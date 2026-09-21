<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CicloAprendizagem;
use App\Models\AlunoCicloAprendizagem;
use App\Modules\Student\Domain\Models\Student;
use App\Models\ConfiguracaoGeral;
use App\Modules\Comunicacao\Services\AutomacaoService;

class GerarAvaliacoesAprendizagem extends Command
{
    protected $signature = 'aprendizagem:gerar-avaliacoes {ciclo_id}';
    protected $description = 'Gera e distribui as avaliações para aprendizes e gestores baseado no ciclo ativo.';

    public function handle()
    {
        $ciclo = CicloAprendizagem::with('fases.formularios')->findOrFail($this->argument('ciclo_id'));
        $this->info("Iniciando geração para o Ciclo: {$ciclo->nome}");

        $query = Student::with(['matriculas.turmas', 'gestor'])
            ->where('is_active', true)
            ->where('is_aprendiz', true);
        
        if ($ciclo->ciclo_mes === '05') {
            $query->whereDate('data_inicio_contrato', '<=', $ciclo->ano . '-03-31');
        } elseif ($ciclo->ciclo_mes === '11') {
            $query->whereDate('data_inicio_contrato', '<=', $ciclo->ano . '-09-30');
        }

        $aprendizes = $query->get();
        $countGerados = 0;

        $faseInicial = $ciclo->fases->where('ordem', 1)->first();
        
        if (!$faseInicial) {
            $this->error("Nenhuma fase configurada para este ciclo.");
            return;
        }

        $emailEquipePedagogica = ConfiguracaoGeral::where('chave', 'ios_emaprz')->value('valor') ?? 'aprendizagem_aluno@ios.org.br';

        foreach ($aprendizes as $aluno) {
            $existe = AlunoCicloAprendizagem::where('ciclo_aprendizagem_id', $ciclo->id)
                ->where('student_id', $aluno->id)
                ->exists();

            if (!$existe) {
                
                AlunoCicloAprendizagem::create([
                    'ciclo_aprendizagem_id' => $ciclo->id,
                    'student_id' => $aluno->id,
                    'fase_atual_id' => $faseInicial->id,
                    'status' => '2', // Enviada/Pendente
                    'data_geracao' => now(),
                    'data_envio' => now(),
                    'data_prazo' => now()->addDays($ciclo->prazo_dias ?? 10),
                ]);

                $matricula = $aluno->matriculas->first();
                $turma = $matricula ? $matricula->turmas->first() : null;
                $formSlug = $faseInicial->formularios->first()->slug ?? '';

                $cpfFormatado = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $aluno->cpf);

                $dadosExtras = [
                    'ciclo_mes_ano' => $ciclo->ciclo_mes . '/' . $ciclo->ano,
                    'turma_codigo_nome' => $turma ? ($turma->codigo . ' - ' . $turma->nome) : 'Não vinculada',
                    'aluno_ra' => $matricula ? $matricula->numero_matricula : 'S/N',
                    'aluno_nome' => $aluno->name,
                    'aluno_cpf' => $cpfFormatado,
                    'link_avaliacao' => route('formulario-aprendizagem.responder', ['slug' => $formSlug, 'aluno_id' => $aluno->id])
                ];

                
                AutomacaoService::disparar('aprendizagem.nova_avaliacao_equipe', $emailEquipePedagogica, $dadosExtras);

                if ($aluno->email) {
                    AutomacaoService::disparar('aprendizagem.nova_avaliacao_aluno', $aluno->email, $dadosExtras);
                }

                if ($aluno->gestor && $aluno->gestor->email) {
                    AutomacaoService::disparar('aprendizagem.nova_avaliacao_empresa', $aluno->gestor->email, $dadosExtras);
                }

                $countGerados++;
            }
        }

        $this->info("Geração concluída. {$countGerados} novas avaliações iniciadas no fluxo.");
    }
}