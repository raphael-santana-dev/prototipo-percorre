<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Ciclo;
use App\Models\Importacao;

class RecalcularPontuacoesGlobaisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // Limite de 1 hora
    protected $trackingId;

    public function __construct($trackingId)
    {
        $this->trackingId = $trackingId;
    }

    public function handle(): void
    {
        $tracking = Importacao::find($this->trackingId);
        if ($tracking) $tracking->update(['status' => 'processando']);

        $ciclos = Ciclo::where('status', true)->whereNotNull('regras_pontuacao')->get();
        
        // Conta quantas inscrições válidas existem para calibrar a barra de progresso (100%)
        $totalInscricoes = 0;
        foreach ($ciclos as $ciclo) {
            if (!empty(is_string($ciclo->regras_pontuacao) ? json_decode($ciclo->regras_pontuacao, true) : $ciclo->regras_pontuacao)) {
                $totalInscricoes += $ciclo->inscricoes()->count();
            }
        }

        if ($tracking) $tracking->update(['total_linhas' => $totalInscricoes]);

        $atualizados = 0;

        try {
            foreach ($ciclos as $ciclo) {
                $regras = is_string($ciclo->regras_pontuacao) ? json_decode($ciclo->regras_pontuacao, true) : $ciclo->regras_pontuacao;
                if (empty($regras)) continue;

                $ciclo->inscricoes()->chunk(100, function ($inscricoes) use ($regras, &$atualizados, $tracking) {
                    foreach ($inscricoes as $inscricao) {
                        
                        $scoreBase = 0;
                        $scoreBonus = 0;
                        $acertosPadrao = 0;
                        $detalhes = ['auditoria_detalhada' => []];

                        $respostas = is_string($inscricao->dados_dinamicos) ? json_decode($inscricao->dados_dinamicos, true) : ($inscricao->dados_dinamicos ?? []);

                        $avaliarCondicao = function($regra) use ($inscricao, $respostas) {
                            if (($regra['escopo'] ?? 'especifico') === 'todos' && ($regra['tipo_regra'] ?? 'padrao') !== 'padrao') return true; 

                            $campo = trim($regra['campo'] ?? '');
                            $operador = trim($regra['operador'] ?? '=');
                            $valorResposta = null;

                            if ($campo === 'idade' && $inscricao->data_nascimento) $valorResposta = \Carbon\Carbon::parse($inscricao->data_nascimento)->age;
                            elseif (in_array($campo, ['estado', 'cidade', 'curso_id', 'turno_id', 'possui_deficiencia'])) $valorResposta = $inscricao->$campo;
                            elseif (isset($respostas[$campo])) $valorResposta = $respostas[$campo];

                            if ($valorResposta === null || $valorResposta === '') return false;

                            $valorAlvoStr = trim((string)($regra['valor'] ?? ''));
                            $valoresEsperados = in_array($operador, ['between', 'in']) ? array_map('trim', explode(',', $valorAlvoStr)) : [$valorAlvoStr];
                            $valorAlvo = $valoresEsperados[0] ?? null;

                            switch ($operador) {
                                case '=': return (strtolower(trim((string)$valorResposta)) === strtolower(trim((string)$valorAlvo)));
                                case '!=': return (strtolower(trim((string)$valorResposta)) !== strtolower(trim((string)$valorAlvo)));
                                case '>=': return ((float)$valorResposta >= (float)$valorAlvo);
                                case '<=': return ((float)$valorResposta <= (float)$valorAlvo);
                                case '>': return ((float)$valorResposta > (float)$valorAlvo);
                                case '<': return ((float)$valorResposta < (float)$valorAlvo);
                                case 'between': return ((float)$valorResposta >= (float)($valoresEsperados[0] ?? 0) && (float)$valorResposta <= (float)($valoresEsperados[1] ?? 0));
                                case 'in': return in_array(strtolower(trim((string)$valorResposta)), array_map(fn($v) => strtolower(trim((string)$v)), $valoresEsperados));
                            }
                            return false;
                        };

                        // Passagem 1: Base
                        foreach ($regras as $regra) {
                            if (($regra['tipo_regra'] ?? 'padrao') === 'padrao' && $avaliarCondicao($regra)) {
                                $pontos = (float) ($regra['pontos'] ?? 0);
                                $scoreBase += $pontos;
                                $acertosPadrao++;
                                $detalhes['auditoria_detalhada'][] = [
                                    'tipo_regra' => 'padrao', 'campo_avaliado' => $regra['campo'], 'resposta_dada' => "Condição atendida", 'pontos_ganhos' => $pontos, 'condicao' => "{$regra['operador']} {$regra['valor']}"
                                ];
                            }
                        }

                        // Passagem 2: Bônus
                        foreach ($regras as $regra) {
                            $tipo = $regra['tipo_regra'] ?? 'padrao';
                            if ($tipo !== 'padrao' && $avaliarCondicao($regra)) {
                                $multiplicador = (float) ($regra['pontos'] ?? 0);
                                $pontosGanhos = 0;
                                $motivo = "";

                                if ($tipo === 'bonus_por_acerto') {
                                    $pontosGanhos = $multiplicador * $acertosPadrao; 
                                    $motivo = "Bônus (+{$multiplicador} pts) multiplicado por {$acertosPadrao} acertos base.";
                                } elseif ($tipo === 'multiplicador_percentual') {
                                    $pontosGanhos = $scoreBase * ($multiplicador / 100); 
                                    $motivo = "Bônus de {$multiplicador}% aplicado sobre Score Base ({$scoreBase} pts).";
                                }

                                if ($pontosGanhos > 0) {
                                    $scoreBonus += $pontosGanhos;
                                    $detalhes['auditoria_detalhada'][] = [
                                        'tipo_regra' => 'especial', 'campo_avaliado' => ($regra['escopo'] ?? 'especifico') === 'todos' ? 'Regra Global' : $regra['campo'], 'resposta_dada' => "Benefício Ativado", 'pontos_ganhos' => $pontosGanhos, 'condicao' => $motivo
                                    ];
                                }
                            }
                        }

                        $totalFinal = $scoreBase + $scoreBonus;

                        $inscricao->update([
                            'pontuacao_total' => $totalFinal,
                            'pontuacao_detalhes' => $totalFinal > 0 ? array_merge($detalhes, ['motivo_auditoria' => "Recálculo Global (Background Job). Score Base: {$scoreBase}. Bônus: {$scoreBonus}. Total: {$totalFinal} pts."]) : null
                        ]);
                        
                        $atualizados++;
                    }

                    // A cada 100 alunos calculados, avisa a tela de integrações:
                    if ($tracking) $tracking->update(['linhas_processadas' => $atualizados]);
                });
            }

            if ($tracking) $tracking->update(['status' => 'concluido', 'linhas_processadas' => $atualizados]);

        } catch (\Throwable $e) {
            if ($tracking) {
                $tracking->update([
                    'status' => 'erro',
                    'erro_mensagem' => json_encode([['linha' => 0, 'tipo' => 'Erro Fatal', 'mensagem' => $e->getMessage()]])
                ]);
            }
        }
    }
}