<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Importacao;
use App\Models\Inscricao;
use App\Models\StatusInscricao;
use App\Modules\Student\Domain\Models\Student;
use App\Modules\Comunicacao\Services\AutomacaoService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProcessarStatusEmLoteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // Limite de 1 hora
    protected $trackingId;
    protected $inscricaoIds;
    protected $statusId;

    public function __construct($trackingId, array $inscricaoIds, $statusId)
    {
        $this->trackingId = $trackingId;
        $this->inscricaoIds = $inscricaoIds;
        $this->statusId = $statusId;
    }

    public function handle(): void
    {
        $tracking = Importacao::find($this->trackingId);
        if ($tracking) $tracking->update(['status' => 'processando']);

        $statusNovo = StatusInscricao::find($this->statusId);
        if (!$statusNovo) {
            if ($tracking) $tracking->update(['status' => 'erro']);
            return;
        }

        $nomeStatus = strtolower(trim($statusNovo->nome));
        $isAprovacao = in_array($nomeStatus, ['aprovado', 'selecionado', 'aprovada']);

        $processados = 0;
        $erros = [];

        try {
            // Divide o array gigante de IDs em pedaços de 50 para não sobrecarregar a memória
            $chunks = array_chunk($this->inscricaoIds, 50);

            foreach ($chunks as $chunk) {
                // Evita "MySQL/Postgres server has gone away" em jobs longos:
                // reconecta antes de cada chunk para garantir uma conexão viva.
                \Illuminate\Support\Facades\DB::reconnect();

                $inscricoes = Inscricao::whereIn('id', $chunk)->get();

                foreach ($inscricoes as $inscricao) {
                    try {
                        // REGRA DE NEGÓCIO: Criação do Estudante (pesada)
                        if ($isAprovacao && !$inscricao->student_id) {
                            $estudante = Student::firstOrCreate(
                                ['email' => $inscricao->email],
                                [
                                    'name' => $inscricao->nome,
                                    'password' => Hash::make(Str::random(12)),
                                    'is_active' => true,
                                    'unidade_id' => $inscricao->unidade_id,
                                    'cpf' => $inscricao->cpf,
                                    'slug' => Str::slug($inscricao->nome)
                                ]
                            );
                            $inscricao->student_id = $estudante->id;
                        }
                        
                        // Atualiza o status
                        $inscricao->status_inscricao_id = $this->statusId;
                        $inscricao->save();

                        // LÓGICA DINÂMICA DE GATILHOS PARA E-MAIL
                        $eventoGatilho = 'inscricao.pendente';
                        if ($isAprovacao) {
                            $eventoGatilho = 'inscricao.aprovada';
                        } elseif (in_array($nomeStatus, ['reprovado', 'reprovada', 'cancelado', 'cancelada'])) {
                            $eventoGatilho = 'inscricao.reprovada';
                        }

                        // Dispara as automações do Blip/E-mail
                        AutomacaoService::disparar($eventoGatilho, $inscricao);
                        
                        $processados++;

                    } catch (\Throwable $e) {
                        $erros[] = [
                            'linha' => $inscricao->id,
                            'tipo' => 'Erro de Processamento',
                            'mensagem' => $e->getMessage()
                        ];
                    }
                }

                // Atualiza o progresso visualmente no Painel de Integrações
                if ($tracking) {
                    $tracking->update(['linhas_processadas' => $processados]);
                }
            }
        } catch (\Throwable $e) {
            // Qualquer falha fatal (queda de conexão, erro fora do try por item, etc.)
            // agora marca o tracking como erro em vez de deixá-lo preso em "processando".
            if ($tracking) {
                $tracking->update([
                    'status' => 'erro',
                    'linhas_processadas' => $processados,
                    'erro_mensagem' => json_encode(array_merge($erros, [[
                        'linha' => null,
                        'tipo' => 'Falha Fatal do Job',
                        'mensagem' => $e->getMessage()
                    ]]), JSON_UNESCAPED_UNICODE)
                ]);
            }

            throw $e; // Deixa a exceção seguir para o Laravel registrar/tentar novamente conforme configurado
        }

        // Finaliza o tracking
        if ($tracking) {
            $tracking->update([
                'status' => count($erros) > 0 ? 'erro_parcial' : 'concluido',
                'linhas_processadas' => $processados,
                'erro_mensagem' => count($erros) > 0 ? json_encode($erros, JSON_UNESCAPED_UNICODE) : null
            ]);
        }
    }

    /**
     * Chamado automaticamente pelo Laravel quando o job esgota todas as
     * tentativas e é movido para failed_jobs. Sem isso, um job que morre
     * de vez (ex: timeout) deixa o registro preso em "processando" para sempre.
     */
    public function failed(\Throwable $exception): void
    {
        $tracking = Importacao::find($this->trackingId);
        if ($tracking && $tracking->status !== 'concluido') {
            $tracking->update([
                'status' => 'erro',
                'erro_mensagem' => json_encode([[
                    'linha' => null,
                    'tipo' => 'Job Falhou Definitivamente',
                    'mensagem' => $exception->getMessage()
                ]], JSON_UNESCAPED_UNICODE)
            ]);
        }
    }
}