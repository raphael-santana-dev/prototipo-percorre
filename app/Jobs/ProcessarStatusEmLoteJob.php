<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Inscricao;
use App\Models\StatusInscricao;
use App\Models\Importacao;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Modules\Comunicacao\Domain\Models\Automacao;
use App\Modules\Student\Domain\Models\Student;

class ProcessarStatusEmLoteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $trackingId;
    public $inscricoesIds;
    public $novoStatusId;

    public function __construct($trackingId, $inscricoesIds, $novoStatusId)
    {
        $this->trackingId = $trackingId;
        $this->inscricoesIds = $inscricoesIds;
        $this->novoStatusId = $novoStatusId;
    }

    public function handle(): void
    {
        $tracking = Importacao::find($this->trackingId);
        if ($tracking) $tracking->update(['status' => 'processando']);

        $statusNovo = StatusInscricao::find($this->novoStatusId);
        if (!$statusNovo) return;

        $inscricoes = Inscricao::whereIn('id', $this->inscricoesIds)->get();
        $linhasProcessadas = 0;
        
        // Busca se existe automação ativa para este exato status
        $eventoGatilho = 'inscricao.status.' . Str::slug($statusNovo->nome, '_');
        $automacao = Automacao::where('evento_gatilho', $eventoGatilho)->where('status', true)->first();

        foreach ($inscricoes as $inscricao) {
            // REGRA EXCLUSIVA PARA APROVADOS: Gera Token de Matrícula e cria o Acesso do Aluno
            if (strtolower($statusNovo->nome) === 'aprovado') {
                if (empty($inscricao->token_matricula)) {
                    $inscricao->token_matricula = Str::random(60);
                }
                
                if (!$inscricao->student_id) {
                    $estudante = Student::firstOrCreate(
                        ['email' => $inscricao->email],
                        [
                            'name' => $inscricao->nome,
                            'password' => Hash::make(Str::random(12)),
                            'is_active' => true,
                        ]
                    );
                    $inscricao->student_id = $estudante->id;
                }
            }

            $inscricao->status_inscricao_id = $statusNovo->id;
            $inscricao->save();

            // Dispara e-mail automaticamente se houver regra configurada no sistema
            if ($automacao) {
                dispatch(new ProcessarDisparoAutomacaoJob($automacao, $inscricao));
            }

            $linhasProcessadas++;
            if ($linhasProcessadas % 10 === 0 && $tracking) {
                $tracking->update(['linhas_processadas' => $linhasProcessadas]);
            }
        }

        if ($tracking) {
            $tracking->update([
                'status' => 'concluido',
                'linhas_processadas' => $linhasProcessadas
            ]);
        }
    }
}