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

    public $timeout = 3600;
    protected $trackingId;
    protected $cicloId;

    public function __construct($trackingId, $cicloId = null)
    {
        $this->trackingId = $trackingId;
        $this->cicloId = $cicloId;
    }

    public function handle(): void
    {
        $tracking = Importacao::find($this->trackingId);
        if ($tracking) $tracking->update(['status' => 'processando']);

        $queryCiclos = Ciclo::whereNotNull('regras_pontuacao');
        if ($this->cicloId) {
            $queryCiclos->where('id', $this->cicloId);
        } else {
            $queryCiclos->where('status', true);
        }
        $ciclos = $queryCiclos->get();
        
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

                $ciclo->inscricoes()->with(['curso', 'turno', 'unidade'])->orderBy('id')->chunkById(100, function ($inscricoes) use ($regras, &$atualizados, $tracking) {
                    foreach ($inscricoes as $inscricao) {
                        
                        $scoreBase = 0;
                        $scoreBonus = 0;
                        $acertosPadrao = 0;
                        $detalhes = ['auditoria_detalhada' => []];

                        $respostas = is_string($inscricao->dados_dinamicos) ? json_decode($inscricao->dados_dinamicos, true) : ($inscricao->dados_dinamicos ?? []);

                        $formatarCondicao = function($operador, $valor) {
                            $valores = array_map('trim', explode(',', (string)$valor));
                            switch ($operador) {
                                case '=': return "Exigência: Igual a '{$valor}'";
                                case '!=': return "Exigência: Diferente de '{$valor}'";
                                case '>=': return "Exigência: Maior ou igual a {$valor}";
                                case '<=': return "Exigência: Menor ou igual a {$valor}";
                                case '>': return "Exigência: Maior que {$valor}";
                                case '<': return "Exigência: Menor que {$valor}";
                                case 'between': 
                                    $v1 = $valores[0] ?? '';
                                    $v2 = $valores[1] ?? '';
                                    return "Exigência: Estar entre {$v1} e {$v2}";
                                case 'in': 
                                    return "Exigência: Dentre as opções (" . implode(' ou ', $valores) . ")";
                                default: return "Exigência: {$operador} {$valor}";
                            }
                        };

                        $obterResposta = function($campo) use ($inscricao, $respostas) {
                            if ($campo === 'idade' && $inscricao->data_nascimento) return \Carbon\Carbon::parse($inscricao->data_nascimento)->age . ' anos';
                            if ($campo === 'curso_id') return $inscricao->curso->nome ?? 'Curso não informado';
                            if ($campo === 'turno_id') return $inscricao->turno->nome ?? 'Turno não informado';
                            if ($campo === 'unidade_id') return $inscricao->unidade->nome ?? 'Unidade não informada';
                            if ($campo === 'estado') return $inscricao->estado ?? 'Estado não informado';
                            if ($campo === 'cidade') return $inscricao->cidade ?? 'Cidade não informada';
                            if ($campo === 'possui_deficiencia') return ucfirst($inscricao->possui_deficiencia) ?? 'Não informada';
                            
                            if (isset($respostas[$campo])) {
                                return is_array($respostas[$campo]) ? implode(', ', $respostas[$campo]) : $respostas[$campo];
                            }
                            return '';
                        };

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

                        foreach ($regras as $regra) {
                            if (($regra['tipo_regra'] ?? 'padrao') === 'padrao' && $avaliarCondicao($regra)) {
                                $pontos = (float) ($regra['pontos'] ?? 0);
                                $scoreBase += $pontos;
                                $acertosPadrao++;
                                
                                $valorEncontrado = $obterResposta($regra['campo'] ?? '');
                                $respostaDada = (!empty($valorEncontrado) || $valorEncontrado === '0' || $valorEncontrado === 0) ? $valorEncontrado : 'Não informada / Em branco';

                                $detalhes['auditoria_detalhada'][] = [
                                    'tipo_regra' => 'padrao', 
                                    'campo_avaliado' => $regra['campo'], 
                                    'resposta_dada' => $respostaDada, 
                                    'pontos_ganhos' => $pontos, 
                                    'condicao' => $formatarCondicao($regra['operador'] ?? '=', $regra['valor'] ?? '')
                                ];
                            }
                        }

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
                                    $motivo = "Multiplicador de {$multiplicador}% aplicado sobre a pontuação base ({$scoreBase} pts).";
                                }

                                if ($pontosGanhos > 0) {
                                    $scoreBonus += $pontosGanhos;
                                    
                                    $escopo = $regra['escopo'] ?? 'especifico';
                                    $campoAvaliado = $escopo === 'todos' ? 'Regra Global' : ($regra['campo'] ?? 'Regra Específica');
                                    
                                    $valorEncontradoEspecial = $obterResposta($regra['campo'] ?? '');
                                    $respostaDadaEspecial = $escopo === 'todos' ? 'Benefício aplicado a todos' : (!empty($valorEncontradoEspecial) ? $valorEncontradoEspecial : 'Ativada');

                                    $detalhes['auditoria_detalhada'][] = [
                                        'tipo_regra' => 'especial', 
                                        'campo_avaliado' => $campoAvaliado, 
                                        'resposta_dada' => $respostaDadaEspecial, 
                                        'pontos_ganhos' => $pontosGanhos, 
                                        'condicao' => $motivo
                                    ];
                                }
                            }
                        }

                        $totalFinal = $scoreBase + $scoreBonus;

                        $inscricao->update([
                            'pontuacao_total' => $totalFinal,
                            'pontuacao_detalhes' => $totalFinal > 0 ? array_merge($detalhes, ['motivo_auditoria' => "Avaliação de requisitos concluída com sucesso. A resposta do candidato correspondeu a {$acertosPadrao} regra(s) base. Pontos conquistados diretamente: {$scoreBase}. Acréscimos por bônus: {$scoreBonus}. Total = {$totalFinal} pontos."]) : null
                        ]);
                        
                        $atualizados++;
                    }

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