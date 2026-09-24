<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelReader;
use App\Models\Importacao;
use App\Models\Inscricao;

use App\Traits\FuzzyMatchingTrait;

class ProcessarImportacaoUniversalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, FuzzyMatchingTrait;

    public $timeout = 7200; // Alargado para ficheiros gigantes
    protected $importacao;

    protected $relatorioAutoCadastro = [
        '100_porcento' => [],
        '50_porcento'  => [],
        'novos'        => []
    ];

    protected $cacheVinculos = [];
    protected $configsRelacionamentoCache = null;

    public function __construct(Importacao $importacao)
    {
        $this->importacao = $importacao;
    }

    public function handle(): void
    {
        // 1. OTIMIZAÇÃO: Desativar Query Log previne fugas de memória (OOM) no Eloquent
        DB::disableQueryLog();

        $linhaAtual = 0;
        $erros = [];
        $errosCriticos = 0;

        try {
            $this->importacao->update(['status' => 'processando']);
            $caminhoAbsoluto = Storage::disk('local')->path($this->importacao->arquivo_caminho);
            $formato = $this->importacao->formato;
            
            // 2. SEGURANÇA: Restringe o Job a processar estritamente CSV e XLSX
            if (!in_array($formato, ['csv', 'xlsx', 'xls'])) {
                throw new \Exception("Apenas arquivos CSV ou Excel são permitidos.");
            }

            $reader = SimpleExcelReader::create($caminhoAbsoluto);
            if ($formato === 'csv') {
                $cabecalhoRaw = file_get_contents($caminhoAbsoluto, false, null, 0, 250);
                $reader->useDelimiter(strpos($cabecalhoRaw, ';') !== false ? ';' : ',');
            }

            $mapeamento = $this->importacao->mapeamento ?? [];
            $linhasParaReprocessar = $mapeamento['linhas_reprocessar'] ?? null;

            // 3. CHUNKING ESTRUTURAL: Processa em blocos de 500 para libertar memória
            $reader->getRows()->chunk(500)->each(function ($chunk) use (&$linhaAtual, &$erros, &$errosCriticos, $mapeamento, $linhasParaReprocessar) {
                
                // Se o usuário clicar em "Cancelar", o job reconhece na hora e aborta
                if ($this->importacao->fresh()->status !== 'processando') {
                    return false; 
                }

                foreach ($chunk as $linhaOriginal) {
                    $linhaAtual++;

                    if (is_array($linhasParaReprocessar) && !in_array($linhaAtual, $linhasParaReprocessar)) {
                        continue; 
                    }

                    try {
                        $dadosLimpos = [];
                        foreach ($linhaOriginal as $key => $value) {
                            $cleanKey = strtolower(trim(str_replace("\xEF\xBB\xBF", '', $key)));
                            $dadosLimpos[$cleanKey] = $value;
                        }

                        $this->processarInscricao($linhaOriginal, $mapeamento);

                    } catch (\Illuminate\Database\QueryException $e) {
                        $isDuplicate = $e->getCode() === '23505'; 
                        $isNotNull = $e->getCode() === '23502';   
                        
                        if (!$isDuplicate) $errosCriticos++;

                        $amigavel = 'Falha técnica ao salvar no banco de dados.';
                        if ($isDuplicate) $amigavel = 'Candidato ignorado: O registro já existe (Desative a Mesclagem ou corrija o CPF).';
                        if ($isNotNull) $amigavel = 'Falha ao vincular: Faltam dados na tabela destino.';

                        $erros[] = [
                            'linha' => $linhaAtual, 
                            'tipo' => $isDuplicate ? 'Alerta (Duplicata)' : 'Erro de Banco',
                            'mensagem' => $e->getMessage(),
                            'amigavel' => $amigavel
                        ];
                    } catch (\Throwable $e) {
                        $errosCriticos++;
                        $erros[] = [
                            'linha' => $linhaAtual, 
                            'tipo' => 'Erro de Dados',
                            'mensagem' => $e->getMessage(),
                            'amigavel' => 'Erro na planilha: ' . $e->getMessage()
                        ];
                    }
                    
                    unset($linhaOriginal, $dadosLimpos); // Liberta a variável
                }

                if ($errosCriticos >= 1000) {
                    throw new \Exception("Excesso de falhas (1000+). Planilha fora do padrão. Operação abortada.");
                }

                // Atualiza o progresso no final de cada Chunk
                $this->importacao->update([
                    'linhas_processadas' => $linhaAtual,
                    'erro_mensagem' => count($erros) > 0 ? json_encode($erros, JSON_UNESCAPED_UNICODE) : null
                ]);

                // 4. GARBAGE COLLECTION: Força o PHP a descarregar memória inútil
                if (function_exists('gc_collect_cycles')) gc_collect_cycles();
            });

            if ($this->importacao->fresh()->status !== 'processando') {
                return; // Morre em caso de cancelamento
            }

            if (!empty($this->relatorioAutoCadastro['novos']) || !empty($this->relatorioAutoCadastro['50_porcento'])) {
                $msgRelatorio = "Relatório Auto-Cadastro: \n";
                foreach ($this->relatorioAutoCadastro['50_porcento'] ?? [] as $tipo => $itens) {
                    if (!empty($itens)) $msgRelatorio .= "- $tipo Mesclados: " . implode(' | ', array_unique($itens)) . " \n";
                }
                foreach ($this->relatorioAutoCadastro['novos'] ?? [] as $tipo => $itens) {
                    if (!empty($itens)) $msgRelatorio .= "- $tipo Criados: " . implode(' | ', array_unique($itens)) . " \n";
                }
                array_unshift($erros, ['linha' => 'INFO', 'tipo' => 'Log de IA', 'mensagem' => $msgRelatorio, 'amigavel' => $msgRelatorio]);
            }

            $statusFinal = count($erros) > 0 ? (count($erros) >= $linhaAtual ? 'erro' : 'erro_parcial') : 'concluido';
            
            $this->importacao->update([
                'status' => $statusFinal,
                'linhas_processadas' => $linhaAtual,
                'erro_mensagem' => count($erros) > 0 ? json_encode($erros, JSON_UNESCAPED_UNICODE) : null
            ]);

            // Registo no Log de Auditoria
            if ($linhaAtual > 0) {
                $usuario = $this->importacao->user; 
                DB::table('auditoria_logs')->insert([
                    'tabela_alterada' => 'inscricoes',
                    'registro_id' => null,
                    'acao' => 'importacao_lote',
                    'informacao_anterior' => null,
                    'nova_informacao' => json_encode(['total_linhas_lidas' => $linhaAtual, 'falhas' => count($erros)], JSON_UNESCAPED_UNICODE),
                    'usuario_id' => $usuario->id ?? null,
                    'usuario_nome' => $usuario->name ?? 'Sistema',
                    'usuario_role' => 'Sistema',
                    'usuario_login' => $usuario->email ?? 'N/A',
                    'ip' => '127.0.0.1',
                    'navegador' => 'Background Job',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

        } catch (\Throwable $e) {
            array_unshift($erros, [
                'linha' => 'Falha Crítica', 
                'tipo' => 'Crash',
                'mensagem' => $e->getMessage(),
                'amigavel' => 'A importação caiu: ' . $e->getMessage()
            ]);
            $this->importacao->update([
                'status' => 'erro', 
                'erro_mensagem' => json_encode($erros, JSON_UNESCAPED_UNICODE)
            ]);
        }
    }

    private function buscarOuCriarVinculo($classeModel, $nomePlanilha, $permiteAutoCadastro, $tipoVinculo)
    {
        $nomePlanilha = trim(preg_replace('/\s+/', ' ', $nomePlanilha));
        if (empty($nomePlanilha)) return null;

        if (!isset($this->cacheVinculos[$classeModel])) {
            $this->cacheVinculos[$classeModel] = $classeModel::all();
        }
        $registrosBanco = $this->cacheVinculos[$classeModel];

        $melhorMatch = null;
        $maiorScore = 0;

        foreach ($registrosBanco as $registro) {
            if (mb_strtolower(trim($registro->nome)) === mb_strtolower($nomePlanilha)) {
                $melhorMatch = $registro;
                $maiorScore = 100;
                break;
            }

            $score = $this->calcularCompatibilidade($nomePlanilha, $registro->nome);
            if ($score >= 50 && $score > $maiorScore) {
                $maiorScore = $score;
                $melhorMatch = $registro;
            }
        }

        if ($melhorMatch) {
            if ($maiorScore == 100) {
                $this->relatorioAutoCadastro['100_porcento'][$tipoVinculo][] = $nomePlanilha;
            } else {
                $this->relatorioAutoCadastro['50_porcento'][$tipoVinculo][] = "'{$nomePlanilha}' ➔ '{$melhorMatch->nome}' (" . number_format($maiorScore, 1) . "%)";
            }
            return $melhorMatch->id;
        }

        if ($permiteAutoCadastro) {
            $dadosNovo = ['nome' => $nomePlanilha];
            if (str_contains($classeModel, 'Curso')) $dadosNovo['status'] = 'Ativo';
            if (str_contains($classeModel, 'Unidade')) $dadosNovo['status'] = 'Ativa';
            $novo = $classeModel::create($dadosNovo);
            $this->cacheVinculos[$classeModel]->push($novo);
            $this->relatorioAutoCadastro['novos'][$tipoVinculo][] = $nomePlanilha;
            return $novo->id;
        }

        return null;
    }

    private function processarInscricao(array $linhaOriginal, array $mapeamento)
    {
        $dadosFixos = [];
        $dadosDinamicos = [];
        $metadados = []; 

        $linhaFormatada = [];
        foreach ($linhaOriginal as $k => $v) {
            $cleanKey = mb_convert_encoding(trim($k), 'UTF-8', 'UTF-8, ISO-8859-1, WINDOWS-1252');
            $linhaFormatada[$cleanKey] = $v;
        }

        $autoCadastroAtivo = filter_var($mapeamento['config_auto_cadastro'] ?? false, FILTER_VALIDATE_BOOLEAN);

        foreach ($mapeamento as $colunaPlanilha => $config) {
            if (in_array($colunaPlanilha, ['ciclo_id', 'config_auto_cadastro', 'config_mesclar_duplicadas', 'linhas_reprocessar'])) continue;

            $destino = $config['destino'] ?? 'ignorar';
            if ($destino === 'ignorar') continue;

            $valorPlanilha = trim((string) ($linhaFormatada[$colunaPlanilha] ?? ''));
            if ($valorPlanilha === '') continue;

            $tipoMapeado = $config['tipo'] ?? 'texto';

            if (in_array($tipoMapeado, ['data', 'data_hora']) || str_contains($destino, 'data') || in_array($destino, ['created_at', 'updated_at'])) {
                $precisaDeHora = in_array($destino, ['created_at', 'updated_at']) || $tipoMapeado === 'data_hora';
                try {
                    $parsed = \Carbon\Carbon::parse(str_replace('/', '-', $valorPlanilha));
                    $valorPlanilha = $precisaDeHora ? $parsed->format('Y-m-d H:i:s') : $parsed->format('Y-m-d');
                } catch (\Exception $e) {}
            }

            if ($tipoMapeado === 'monetario' || str_contains($destino, 'renda')) {
                $valTemp = preg_replace('/[^0-9,-]/', '', $valorPlanilha); 
                $valTemp = str_replace(',', '.', $valTemp);
                if (is_numeric($valTemp)) $valorPlanilha = $valTemp;
            }

            if (in_array($destino, ['curso_id', 'unidade_id', 'turno_id'])) {
                $classe = null;
                $tipoLabel = '';
                
                if ($destino === 'curso_id') { $classe = \App\Models\Curso::class; $tipoLabel = 'Cursos'; }
                if ($destino === 'unidade_id') { $classe = \App\Modules\Unidade\Domain\Models\Unidade::class; $tipoLabel = 'Unidades'; }
                if ($destino === 'turno_id') { $classe = \App\Modules\Turno\Domain\Models\Turno::class; $tipoLabel = 'Turnos'; }
                
                $idVinculo = $this->buscarOuCriarVinculo($classe, $valorPlanilha, $autoCadastroAtivo, $tipoLabel);
                
                if ($idVinculo) {
                    $dadosFixos[$destino] = $idVinculo;
                } else {
                    throw new \Exception("{$tipoLabel}: O nome '{$valorPlanilha}' não atingiu 50% de similaridade com o banco e o auto-cadastro está desativado.");
                }
                continue; 
            }

            if (str_starts_with($destino, 'dinamico:')) {
                $chaveNome = str_replace('dinamico:', '', $destino);
                $dadosDinamicos[$chaveNome] = $valorPlanilha;
            } elseif ($destino === 'dados_dinamicos') {
                $metadados[$colunaPlanilha] = $valorPlanilha;
            } else {
                $dadosFixos[$destino] = $valorPlanilha;
            }
        }

        if (empty($dadosFixos['nome']) && empty($dadosFixos['cpf'])) {
            throw new \Exception("A linha não possui identificador básico (Nome ou CPF mapeado).");
        }

        if (empty($dadosFixos['cpf'])) $dadosFixos['cpf'] = null;
        if (empty($dadosFixos['email'])) $dadosFixos['email'] = null;

        if ($this->configsRelacionamentoCache === null) {
            $this->configsRelacionamentoCache = \App\Models\ImportacaoConfig::all();
        }
        $configsRelacionamento = $this->configsRelacionamentoCache;
        $permiteAutoCadastro = filter_var($mapeamento['config_auto_cadastro'] ?? false, FILTER_VALIDATE_BOOLEAN);

        foreach ($configsRelacionamento as $config) {
            $coluna = $config->coluna;
            if (array_key_exists($coluna, $dadosFixos)) {
                if (!empty($dadosFixos[$coluna])) {
                    if (!is_numeric($dadosFixos[$coluna])) {
                        $termoOriginal = trim($dadosFixos[$coluna]);
                        $termo = trim(explode(';', explode(',', preg_replace('/[\r\n]+.*/s', '', $termoOriginal))[0])[0]);
                        if (strlen($termo) > 80) $termo = trim(substr($termo, 0, 80));
                        if ($coluna === 'unidade_id' && str_contains($termo, '-')) {
                            $termo = trim(end(explode('-', $termo)));
                        }

                        $ModelClass = $config->model_class;
                        $campoBusca = $config->campo_busca;
                        $registro = $ModelClass::where($campoBusca, 'ilike', '%' . $termo . '%')->first();

                        if (!$registro && $permiteAutoCadastro && $config->auto_cadastro) {
                            $payload = $config->payload_padrao ?? [];
                            $payload[$campoBusca] = $termo;
                            if (!isset($payload['slug'])) $payload['slug'] = Str::slug($termo);
                            if (str_contains($ModelClass, 'Turno') && !isset($payload['horario_inicio'])) $payload['horario_inicio'] = '00:00:00';
                            $registro = $ModelClass::create($payload);
                        }

                        $dadosFixos[$coluna] = $registro ? $registro->id : null;
                    }
                } else {
                    $dadosFixos[$coluna] = null; 
                }
            }
        }

        if (empty($dadosFixos['status_inscricao_id'])) {
            $dadosFixos['status_inscricao_id'] = 1; 
        }

        $dadosFixos['dados_dinamicos'] = $dadosDinamicos;
        $dadosFixos['metadados'] = $metadados;
        $dadosFixos['ciclo_id'] = $mapeamento['ciclo_id'] ?? $linhaOriginal['ciclo_id'] ?? null;
        $dadosFixos['origem'] = 'importacao';
        $dadosFixos['criado_por'] = $this->importacao->user_id;

        $mesclarDuplicatas = filter_var($mapeamento['config_mesclar_duplicadas'] ?? false, FILTER_VALIDATE_BOOLEAN);

        Inscricao::withoutEvents(function () use ($dadosFixos, $mesclarDuplicatas, $metadados) {
            $hasCreated = isset($dadosFixos['created_at']) && $dadosFixos['created_at'] !== '';
            $hasUpdated = isset($dadosFixos['updated_at']) && $dadosFixos['updated_at'] !== '';

            if (!empty($dadosFixos['cpf']) && !empty($dadosFixos['ciclo_id'])) {
                $inscricaoExistente = Inscricao::where('cpf', $dadosFixos['cpf'])
                                               ->where('ciclo_id', $dadosFixos['ciclo_id'])
                                               ->first();
                
                if ($inscricaoExistente) {
                    if (!$mesclarDuplicatas) {
                        throw new \Exception("Candidato ignorado: CPF '{$dadosFixos['cpf']}' já cadastrado para este Ciclo.", 23505);
                    }

                    $dadosAtuais = $inscricaoExistente->toArray();
                    
                    $dinamicoAntigo = is_string($inscricaoExistente->dados_dinamicos) ? json_decode($inscricaoExistente->dados_dinamicos, true) : ($inscricaoExistente->dados_dinamicos ?? []);
                    $dinamicoNovo = $dadosFixos['dados_dinamicos'] ?? [];
                    foreach ($dinamicoNovo as $chaveNova => $valorNovo) {
                        $dinamicoAntigo[$chaveNova] = $valorNovo;
                    }
                    $dadosFixos['dados_dinamicos'] = $dinamicoAntigo;

                    $metaAntigo = is_string($inscricaoExistente->metadados) ? json_decode($inscricaoExistente->metadados, true) : ($inscricaoExistente->metadados ?? []);
                    foreach ($metadados as $k => $v) {
                        $metaAntigo[$k] = $v;
                    }
                    $dadosFixos['metadados'] = $metaAntigo;

                    foreach ($dadosFixos as $coluna => $valorImportado) {
                        if (in_array($coluna, ['id', 'student_id', 'dados_dinamicos', 'metadados', 'origem'])) continue;
                        if (in_array($coluna, ['created_at', 'updated_at'])) continue; 

                        $valorAtualBanco = $dadosAtuais[$coluna] ?? null;
                        if ($valorAtualBanco !== null && trim((string)$valorAtualBanco) !== '') {
                            unset($dadosFixos[$coluna]);
                        }
                    }

                    $inscricaoExistente->timestamps = false;
                    $inscricaoExistente->forceFill($dadosFixos);
                    
                    if ($hasCreated) $inscricaoExistente->created_at = $dadosFixos['created_at'];
                    if ($hasUpdated) $inscricaoExistente->updated_at = $dadosFixos['updated_at'];
                    
                    $inscricaoExistente->save();
                    return;
                }
            }

            $novaInscricao = new Inscricao();
            $novaInscricao->timestamps = false;
            $novaInscricao->forceFill($dadosFixos);
            
            if ($hasCreated) $novaInscricao->created_at = $dadosFixos['created_at'];
            else $novaInscricao->created_at = now();

            if ($hasUpdated) $novaInscricao->updated_at = $dadosFixos['updated_at'];
            else $novaInscricao->updated_at = now();

            $novaInscricao->save();
        });
    }
}