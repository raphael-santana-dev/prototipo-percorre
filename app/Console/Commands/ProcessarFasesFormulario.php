<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FormularioFase;
use App\Models\Inscricao;
use App\Modules\Comunicacao\Jobs\DispararAutomacaoJob;
use Carbon\Carbon;

class ProcessarFasesFormulario extends Command
{
    protected $signature = 'fases:processar';
    protected $description = 'Verifica as fases que atingiram a data de liberação e notifica os candidatos retidos.';

    public function handle()
    {
        // 1. Busca fases que acabaram de atingir a data de liberação e que ainda não notificaram
        $fasesParaLiberar = FormularioFase::with('templateEmail')
            ->where('data_liberacao', '<=', Carbon::now())
            ->where('notificacao_enviada', false)
            ->get();

        if ($fasesParaLiberar->isEmpty()) {
            $this->info("Nenhuma nova fase para liberar no momento.");
            return;
        }

        foreach ($fasesParaLiberar as $fase) {
            $paginas = $fase->paginas ?? [];
            
            // 2. Se a fase tiver um template de e-mail e páginas vinculadas
            if ($fase->templateEmail && !empty($paginas)) {
                sort($paginas);
                $primeiraPaginaDaFase = $paginas[0];
                $paginaAnterior = $primeiraPaginaDaFase - 1;

                if ($fase->ciclo_id) {
                    // 3. Busca todos os candidatos que estão "presos/aguardando" na etapa anterior
                    $inscricoesRetidas = Inscricao::where('ciclo_id', $fase->ciclo_id)
                        ->where('etapa_atual', $paginaAnterior)
                        ->whereNotNull('email')
                        ->get();

                    foreach ($inscricoesRetidas as $inscricao) {
                        // Dispara o e-mail diretamente via Job usando o template vinculado na fase
                        dispatch(new DispararAutomacaoJob(
                            $fase->templateEmail,
                            $inscricao->email,
                            $inscricao,
                            ['nome_fase' => $fase->nome],
                            "liberacao.fase.{$fase->id}"
                        ));
                    }
                    
                    $this->info("Foram notificados {$inscricoesRetidas->count()} candidatos para a fase '{$fase->nome}'.");
                }
            }

            // 4. Marca a notificação como enviada para não processar novamente
            $fase->update(['notificacao_enviada' => true]);
            $this->info("Fase '{$fase->nome}' (ID: {$fase->id}) liberada com sucesso.");
        }
    }
}