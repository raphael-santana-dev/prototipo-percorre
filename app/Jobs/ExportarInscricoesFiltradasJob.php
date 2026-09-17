<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use App\Models\Inscricao;
use App\Models\Importacao;
use App\Models\CampoFormulario;

class ExportarInscricoesFiltradasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $trackingId;
    public $filtros;
    public $timeout = 3600;

    public function __construct($trackingId, $filtros)
    {
        $this->trackingId = $trackingId;
        $this->filtros = $filtros;
    }

    public function handle(): void
    {
        $tracking = Importacao::find($this->trackingId);
        if (!$tracking) return;

        try {
            $tracking->update(['status' => 'processando']);

            $query = Inscricao::with(['curso', 'unidade', 'turno', 'ciclo', 'statusInscricao']);
            
            // Aplicação de Filtros
            if (!empty($this->filtros['nome'])) {
                $query->where(function($q) {
                    $q->where('nome', 'ilike', '%' . $this->filtros['nome'] . '%')
                      ->orWhere('cpf', 'like', '%' . $this->filtros['nome'] . '%');
                });
            }
            if (!empty($this->filtros['status'])) $query->where('status_inscricao_id', $this->filtros['status']);
            if (!empty($this->filtros['unidade'])) $query->where('unidade_id', $this->filtros['unidade']);
            if (!empty($this->filtros['turno'])) $query->where('turno_id', $this->filtros['turno']);
            if (!empty($this->filtros['curso'])) $query->where('curso_id', $this->filtros['curso']);
            if (!empty($this->filtros['ciclo'])) $query->where('ciclo_id', $this->filtros['ciclo']);
            if (!empty($this->filtros['etapa'])) {
                if ($this->filtros['etapa'] === 'Finalizado') $query->where('etapa_atual', 99);
                else $query->where('etapa_atual', $this->filtros['etapa']);
            }

            // OTIMIZAÇÃO: Coleta os IDs dos ciclos de forma levíssima
            $ciclosIds = (clone $query)->pluck('ciclo_id')->unique()->filter()->toArray();
            
            $camposOficiais = CampoFormulario::whereIn('ciclo_id', $ciclosIds)
                ->whereNotIn('tipo', ['config', 'html', 'divider', 'media'])
                ->get();

            $chavesDinamicas = [];

            foreach ($camposOficiais as $campo) {
                if (str_contains(strtolower($campo->name), 'form_config')) continue;
                
                if ($campo->tipo === 'social') {
                    $config = is_string($campo->configuracoes) ? json_decode($campo->configuracoes, true) : ($campo->configuracoes ?? []);
                    $redes = $config['redes_permitidas'] ?? [];
                    foreach ($redes as $rede) {
                        $chavesDinamicas["{$campo->name}.{$rede}"] = "{$campo->label} (" . ucfirst($rede) . ")";
                    }
                } else {
                    $chavesDinamicas[$campo->name] = $campo->label;
                }
            }

            // OTIMIZAÇÃO DE MEMÓRIA: Pluck apenas no JSON em vez de carregar a Model inteira
            $dadosDinamicosAll = (clone $query)->whereNotNull('dados_dinamicos')->pluck('dados_dinamicos');
            foreach ($dadosDinamicosAll as $json) {
                $dinamicos = is_string($json) ? json_decode($json, true) : ($json ?? []);
                foreach ($dinamicos as $key => $val) {
                    if (str_contains(strtolower($key), 'form_config')) continue;
                    
                    if (is_array($val) && count(array_filter(array_keys($val), 'is_string')) > 0) {
                        foreach ($val as $rede => $user) {
                            $compoundKey = "{$key}.{$rede}";
                            if (!isset($chavesDinamicas[$compoundKey])) {
                                $chavesDinamicas[$compoundKey] = ucwords(str_replace('_', ' ', $key)) . " (" . ucfirst($rede) . ")";
                            }
                        }
                    } else {
                        if (!isset($chavesDinamicas[$key])) {
                            $chavesDinamicas[$key] = ucwords(str_replace('_', ' ', $key));
                        }
                    }
                }
            }

            $fileName = 'exportacao_inscricoes_' . time() . '.' . $tracking->formato;
            $caminhoRelativo = 'exportacoes/' . $fileName;
            Storage::disk('public')->makeDirectory('exportacoes');
            $caminhoAbsoluto = Storage::disk('public')->path($caminhoRelativo);

            $isCsv = strtolower($tracking->formato) === 'csv';
            $file = null;
            $writer = null;

            if ($isCsv) {
                $file = fopen($caminhoAbsoluto, 'w');
                // Injeta o BOM para o Excel ler o UTF-8 (Acentos) perfeitamente
                fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            } else {
                $writer = \Spatie\SimpleExcel\SimpleExcelWriter::create($caminhoAbsoluto);
            }

            $linhasProcessadas = 0;
            $cabecalhosEscritos = false;

            // OTIMIZAÇÃO OOM: O cursor() itera um registro por vez sem entupir a RAM
            foreach ($query->cursor() as $insc) {
                $etapaExibicao = $insc->etapa_atual == 99 ? 'Finalizado' : ($insc->etapa_atual == 100 ? 'Em Espera' : 'Passo ' . $insc->etapa_atual);
                
                $linha = [
                    'ID' => $insc->id,
                    'Ciclo' => $insc->ciclo->nome ?? '-',
                    'Etapa Atual' => $etapaExibicao,
                    'Status' => $insc->statusInscricao->nome ?? 'Pendente',
                    'Candidato' => $insc->nome,
                    'Possui Nome Social?' => ucfirst($insc->possui_nome_social ?? 'Não'),
                    'Nome Social' => $insc->nome_social ?? '-',
                    'CPF' => $insc->cpf,
                    'E-mail' => $insc->email,
                    'Celular / Telefone' => $insc->celular,
                    'Data Nascimento' => $insc->data_nascimento ? \Carbon\Carbon::parse($insc->data_nascimento)->format('d/m/Y') : '-',
                    'Possui Deficiência?' => ucfirst($insc->possui_deficiencia ?? 'Não'),
                    'Natureza da Deficiência' => $insc->natureza_deficiencia ?? '-',
                    'CEP' => $insc->cep,
                    'Logradouro' => $insc->logradouro ?? '-',
                    'Número' => $insc->numero ?? 'S/N',
                    'Complemento' => $insc->complemento ?? '-',
                    'Bairro' => $insc->bairro ?? '-',
                    'Cidade' => $insc->cidade ?? '-',
                    'Estado (UF)' => $insc->estado ?? '-',
                    'Região' => $insc->regiao ? ucfirst($insc->regiao) : '-',
                    'Unidade' => $insc->unidade->nome ?? '-',
                    'Curso' => $insc->curso->nome ?? '-',
                    'Turno' => $insc->turno->nome ?? '-',
                    'Autorização Uso de Dados' => $insc->autorizacao_uso_infos ? 'Sim' : 'Não',
                    'Pontuação' => $insc->pontuacao_total ?? 0,
                    'Ranking Geral' => $insc->posicao_ranking_geral ? $insc->posicao_ranking_geral . 'º' : '-',
                    'Ranking Unidade' => $insc->posicao_ranking_unidade ? $insc->posicao_ranking_unidade . 'º' : '-',
                    'Ranking Curso' => $insc->posicao_ranking_curso ? $insc->posicao_ranking_curso . 'º' : '-',
                    'Ranking Turma' => $insc->posicao_ranking ? $insc->posicao_ranking . 'º' : '-',
                ];

                $dinamicos = is_string($insc->dados_dinamicos) ? json_decode($insc->dados_dinamicos, true) : ($insc->dados_dinamicos ?? []);
                
                foreach ($chavesDinamicas as $chaveInterna => $label) {
                    if (str_contains($chaveInterna, '.')) {
                        [$parent, $child] = explode('.', $chaveInterna, 2);
                        $valor = $dinamicos[$parent][$child] ?? '';
                    } else {
                        $valor = $dinamicos[$chaveInterna] ?? '';
                    }
                    
                    $linha[$label] = is_array($valor) ? implode(', ', $valor) : $valor;
                }

                // Escreve os cabeçalhos dinâmicos na 1º linha do CSV
                if ($isCsv && !$cabecalhosEscritos) {
                    fputcsv($file, array_keys($linha), ';');
                    $cabecalhosEscritos = true;
                }

                if ($isCsv) {
                    // SEGURANÇA: Prevenção contra CSV/Formula Injection
                    $linhaSanitizada = array_map(function ($valor) {
                        $valorStr = (string) $valor;
                        if (preg_match('/^[\=\+\-\@\t\r]/', $valorStr)) {
                            return "'" . $valorStr;
                        }
                        return $valorStr;
                    }, $linha);
                    
                    fputcsv($file, $linhaSanitizada, ';');
                } else {
                    $writer->addRow($linha);
                }

                $linhasProcessadas++;
                if ($linhasProcessadas % 100 === 0) {
                    $tracking->update(['linhas_processadas' => $linhasProcessadas]);
                }
            }

            if ($isCsv) {
                fclose($file);
            }

            $tracking->update([
                'status' => 'concluido',
                'linhas_processadas' => $linhasProcessadas,
                'arquivo_gerado_caminho' => $caminhoRelativo
            ]);

        } catch (\Throwable $e) {
            // Tratamento global de erros para nunca congelar em "Processando..."
            $tracking->update([
                'status' => 'erro',
                'erro_mensagem' => json_encode([['linha' => 0, 'tipo' => 'Erro Interno', 'mensagem' => $e->getMessage()]])
            ]);
        }
    }
}