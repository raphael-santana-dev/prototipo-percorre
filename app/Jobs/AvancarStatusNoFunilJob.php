<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\Inscricao;
use App\Models\StatusInscricao;
use App\Models\Importacao;
use App\Modules\Comunicacao\Domain\Models\Automacao;
use App\Modules\Student\Domain\Models\Student;

class AvancarStatusNoFunilJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $trackingId;
    public $inscricoesIds;

    public function __construct($trackingId, $inscricoesIds)
    {
        $this->trackingId = $trackingId;
        $this->inscricoesIds = $inscricoesIds;
    }

    public function handle(): void
    {
        $tracking = Importacao::find($this->trackingId);
        if ($tracking) $tracking->update(['status' => 'processando']);

        $inscricoes = Inscricao::whereIn('id', $this->inscricoesIds)->get();
        $linhasProcessadas = 0;

        // Cache das automações ativas para evitar lentidão no banco
        $automacoesCache = Automacao::where('status', true)->get()->keyBy('evento_gatilho');

        foreach ($inscricoes as $inscricao) {
            
            // 1. Descobrir a ordem atual do candidato no ciclo[cite: 14]
            $pivotAtual = DB::table('ciclo_status_inscricao')
                ->where('ciclo_id', $inscricao->ciclo_id)
                ->where('status_inscricao_id', $inscricao->status_inscricao_id)
                ->first();

            $ordemAtual = $pivotAtual ? $pivotAtual->ordem : -1;

            // 2. Descobrir o PRÓXIMO status baseado na ordem[cite: 14]
            $proximoPivot = DB::table('ciclo_status_inscricao')
                ->where('ciclo_id', $inscricao->ciclo_id)
                ->where('ordem', '>', $ordemAtual)
                ->orderBy('ordem', 'asc')
                ->first();

            if ($proximoPivot) {
                $statusNovo = StatusInscricao::find($proximoPivot->status_inscricao_id);

                if ($statusNovo) {
                    
                    if (strtolower($statusNovo->nome) === 'aprovado') {
                        if (empty($inscricao->token_matricula)) $inscricao->token_matricula = Str::random(60);
                        
                        if (!$inscricao->student_id) {
                            $estudante = Student::firstOrCreate(
                                ['email' => $inscricao->email],
                                ['name' => $inscricao->nome, 'password' => Hash::make(Str::random(12)), 'is_active' => true]
                            );
                            $inscricao->student_id = $estudante->id;
                        }
                    }

                    $inscricao->status_inscricao_id = $statusNovo->id;
                    $inscricao->save();

                    // Dispara E-mail automaticamente se houver regra
                    $eventoGatilho = 'inscricao.status.' . Str::slug($statusNovo->nome, '_');
                    if ($automacoesCache->has($eventoGatilho)) {
                        dispatch(new ProcessarDisparoAutomacaoJob($automacoesCache->get($eventoGatilho), $inscricao));
                    }
                }
            }

            $linhasProcessadas++;
            if ($linhasProcessadas % 10 === 0 && $tracking) $tracking->update(['linhas_processadas' => $linhasProcessadas]);
        }

        if ($tracking) $tracking->update(['status' => 'concluido', 'linhas_processadas' => $linhasProcessadas]);
    }
}