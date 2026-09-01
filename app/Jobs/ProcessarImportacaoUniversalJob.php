<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelReader;
use App\Models\Importacao;
use App\Models\User;
use App\Models\CampoFormulario;
use App\Models\Inscricao;
use Illuminate\Database\QueryException;

use App\Traits\FuzzyMatchingTrait;
use App\Models\Curso;
use App\Modules\Unidade\Domain\Models\Unidade;
use App\Modules\Turno\Domain\Models\Turno;

class ProcessarImportacaoUniversalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, FuzzyMatchingTrait;

    public $timeout = 3600; 
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
        $linhaAtual = 0;
        $erros = [];
        $errosCriticos = 0;

        try {
            $this->importacao->update(['status' => 'processando']);
            $caminhoAbsoluto = Storage::disk('local')->path($this->importacao->arquivo_caminho);
            $formato = $this->importacao->formato;
            
            $registros = $this->extrairRegistrosLazy($caminhoAbsoluto, $formato);
            $mapeamento = $this->importacao->mapeamento ?? [];
            
            $linhasParaReprocessar = $mapeamento['linhas_reprocessar'] ?? null;

            foreach ($registros as $linhaOriginal) {
                $linhaAtual++;

                if (is_array($linhasParaReprocessar) && !in_array($linhaAtual, $linhasParaReprocessar)) {
                    if ($linhaAtual % 50 === 0) {
                        $this->importacao->update(['linhas_processadas' => $linhaAtual]);
                    }
                    continue; 
                }

                try {
                    $dadosLimpos = [];
                    foreach ($linhaOriginal as $key => $value) {
                        $cleanKey = strtolower(trim(str_replace("\xEF\xBB\xBF", '', $key)));
                        $dadosLimpos[$cleanKey] = $value;
                    }

                    match ($this->importacao->tipo) {
                        'campos' => $this->processarCampo($dadosLimpos, $mapeamento),
                        'usuarios' => $this->processarUsuario($dadosLimpos),
                        'inscricoes' => $this->processarInscricao($linhaOriginal, $mapeamento),
                        default => throw new \Exception("Tipo de importação '{$this->importacao->tipo}' não implementado."),
                    };

                } catch (QueryException $e) {
                    $isDuplicate = $e->getCode() === '23505'; 
                    $isNotNull = $e->getCode() === '23502';   
                    
                    if (!$isDuplicate) $errosCriticos++;

                    $amigavel = 'Falha técnica ao salvar no banco de dados.';
                    if ($isDuplicate) $amigavel = 'Candidato ignorado: CPF ou E-mail já está cadastrado no sistema.';
                    if ($isNotNull) $amigavel = 'Falha ao auto-cadastrar vínculo: Faltam dados obrigatórios na tabela destino.';

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
                        'amigavel' => 'A informação fornecida na planilha está em um formato inválido: ' . $e->getMessage()
                    ];
                }

                // Aumentado para 1000 erros críticos antes de abortar a operação
                if ($errosCriticos >= 1000) {
                    throw new \Exception("Excesso de erros estruturais detectados (1000+). Processamento abortado por segurança.");
                }

                if ($linhaAtual % 10 === 0) {
                    $this->importacao->update([
                        'linhas_processadas' => $linhaAtual,
                        'erro_mensagem' => count($erros) > 0 ? json_encode($erros, JSON_UNESCAPED_UNICODE) : null
                    ]);
                }
            }

            if (count($this->relatorioAutoCadastro['novos'] ?? []) > 0 || count($this->relatorioAutoCadastro['50_porcento'] ?? []) > 0) {
                $msgRelatorio = "Mapeamento IA (50%+): \n";
                
                foreach ($this->relatorioAutoCadastro['50_porcento'] as $tipo => $itens) {
                    if (!empty($itens)) {
                        $msgRelatorio .= "- $tipo Compatíveis: " . implode(' | ', array_unique($itens)) . " \n";
                    }
                }
                foreach ($this->relatorioAutoCadastro['novos'] as $tipo => $itens) {
                    if (!empty($itens)) {
                        $msgRelatorio .= "- $tipo Criados: " . implode(' | ', array_unique($itens)) . " \n";
                    }
                }
                
                array_unshift($erros, [
                    'linha' => 'INFO',
                    'tipo' => 'Alerta: Inteligência Artificial',
                    'mensagem' => $msgRelatorio,
                    'amigavel' => $msgRelatorio
                ]);
            }

            $statusFinal = count($erros) > 0 ? (count($erros) >= $linhaAtual ? 'erro' : 'erro_parcial') : 'concluido';
            
            $this->importacao->update([
                'status' => $statusFinal,
                'linhas_processadas' => $linhaAtual,
                'erro_mensagem' => count($erros) > 0 ? json_encode($erros, JSON_UNESCAPED_UNICODE) : null
            ]);

            if ($this->importacao->tipo === 'inscricoes' && $linhaAtual > 0) {
                $usuario = $this->importacao->user; 
                \Illuminate\Support\Facades\DB::table('auditoria_logs')->insert([
                    'tabela_alterada' => 'inscricoes',
                    'registro_id' => null,
                    'acao' => 'importacao_lote',
                    'informacao_anterior' => null,
                    'nova_informacao' => json_encode([
                        'total_linhas_lidas' => $linhaAtual, 
                        'falhas' => count($erros),
                        'arquivo_origem' => $this->importacao->arquivo_nome
                    ], JSON_UNESCAPED_UNICODE),
                    'usuario_id' => $usuario ? $usuario->id : null,
                    'usuario_nome' => $usuario ? $usuario->name : 'Sistema (Job)',
                    'usuario_role' => $usuario ? ($usuario->getRoleNames()->first() ?? 'N/A') : 'Sistema',
                    'usuario_login' => $usuario ? $usuario->email : 'N/A',
                    'ip' => 'Processo Background',
                    'navegador' => 'Módulo de Integração Universal',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

        } catch (\Throwable $e) {
            array_unshift($erros, [
                'linha' => 'Crítico/Sistema', 
                'tipo' => 'Falha Crítica',
                'mensagem' => $e->getMessage(),
                'amigavel' => 'A importação falhou de maneira irrecuperável: ' . $e->getMessage()
            ]);
            $this->importacao->update([
                'status' => 'erro', 
                'erro_mensagem' => json_encode($erros, JSON_UNESCAPED_UNICODE)
            ]);
        }
    }

    private function extrairRegistrosLazy(string $caminho, string $formato): iterable
    {
        if (in_array($formato, ['csv', 'xlsx', 'xls'])) {
            $reader = SimpleExcelReader::create($caminho);
            if ($formato === 'csv') {
                $cabecalhoRaw = file_get_contents($caminho, false, null, 0, 250);
                $reader->useDelimiter(strpos($cabecalhoRaw, ';') !== false ? ';' : ',');
            }
            foreach ($reader->getRows() as $rowProperties) {
                yield $rowProperties;
            }
            return;
        }

        if ($formato === 'json') {
            $dados = json_decode(file_get_contents($caminho), true);
            foreach ($dados as $row) yield $row;
            return;
        }

        if ($formato === 'xml') {
            $xml = simplexml_load_file($caminho);
            $json = json_encode($xml);
            $dados = json_decode($json, true);
            $primeiraChave = array_key_first($dados);
            $dataset = $dados[$primeiraChave] ?? $dados;
            foreach ($dataset as $row) yield $row;
            return;
        }

        throw new \Exception("Formato de arquivo não suportado.");
    }

    private function processarCampo(array $dados, array $mapeamento)
    {
        $cicloId = $mapeamento['ciclo_id'] ?? null;
        if (!$cicloId) throw new \Exception("ID do Ciclo ausente no mapeamento.");
        $label = trim($dados['nome do campo'] ?? $dados['label'] ?? '');
        if (empty($label)) throw new \Exception("A coluna 'Nome do Campo' (ou 'label') é obrigatória.");
        $name = trim($dados['id no banco'] ?? $dados['id no banco (name)'] ?? $dados['name'] ?? '');
        if (empty($name)) $name = Str::slug($label, '_');
        $larguraVal = empty(trim($dados['largura'] ?? '')) ? 12 : (int)trim($dados['largura'] ?? '');
        $isObrigatorio = in_array(strtolower(trim($dados['obrigatório'] ?? $dados['obrigatorio'] ?? 'nao')), ['sim', 's', '1', 'true', 'yes']);
        $sempreVisivel = in_array(strtolower(trim($dados['sempre visível?'] ?? $dados['sempre visivel'] ?? 'sim')), ['sim', 's', '1', 'true', 'yes']);
        
        $regrasStr = trim($dados['regras de exibição'] ?? $dados['regras de exibicao'] ?? '');
        $dependeDe = null; $dependeOperador = '='; $dependeValor = null;
        if (!$sempreVisivel && !empty($regrasStr)) {
            if (preg_match('/^([a-zA-Z0-9_]+)(>=|<=|!=|=|>|<)(.*)$/', $regrasStr, $matches)) {
                $dependeDe = trim($matches[1]); $dependeOperador = trim($matches[2]); $dependeValor = trim($matches[3]);
            } else { throw new \Exception("Regra mal formatada. Exemplo correto: 'como_conheceu=Instagram'."); }
        }

        $opcoesRaw = trim($dados['opções'] ?? $dados['opcoes'] ?? '');
        $opcoesArray = null;
        if (!empty($opcoesRaw)) {
            if (str_starts_with(strtolower($opcoesRaw), 'bd:') || str_starts_with(strtolower($opcoesRaw), 'db:')) {
                $partes = explode(':', $opcoesRaw);
                $opcoesArray = ['origem_bd' => $partes[1] ?? '', 'filtro' => $partes[2] ?? ''];
            } else {
                $opcoesArray = array_map('trim', explode(',', $opcoesRaw));
            }
        }

        CampoFormulario::updateOrCreate(
            ['ciclo_id' => $cicloId, 'name' => $name],
            [
                'etapa' => (int)($dados['etapa'] ?? 1), 'ordem' => (int)($dados['ordem'] ?? 0), 'label' => $label,
                'tipo' => trim($dados['tipo'] ?? 'text'), 'largura' => $larguraVal, 'subtipo' => trim($dados['subtipo'] ?? 'text'),
                'opcoes' => $opcoesArray, 'obrigatorio' => $isObrigatorio, 'depende_de' => $dependeDe, 'depende_operador' => $dependeOperador, 'depende_valor' => $dependeValor,
            ]
        );
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

    private function processarUsuario(array $dados)
    {
        $cpfRaw = $dados['cpf'] ?? null;
        if (empty($cpfRaw)) throw new \Exception("A coluna 'CPF' é obrigatória.");
        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpfRaw);
        $email = trim($dados['e-mail'] ?? $dados['email'] ?? '');
        $nome = trim($dados['nome completo'] ?? $dados['nome'] ?? 'Usuário Sem Nome');
        $senha = trim($dados['senha'] ?? $cpfLimpo);
        $usuario = User::where('cpf', $cpfLimpo)->orWhere('email', $email)->first();
        if (!$usuario) { $usuario = User::create(['name' => $nome, 'email' => $email, 'cpf' => $cpfLimpo, 'password' => Hash::make($senha)]); } 
        else { $usuario->update(['name' => $nome, 'cpf' => $cpfLimpo, 'email' => $email ?: $usuario->email]); }
        $roleName = trim($dados['grupo de acesso'] ?? $dados['role'] ?? '');
        if (!empty($roleName)) $usuario->assignRole(Str::slug($roleName, '-'));
        $permissoesRaw = trim($dados['permissões extras'] ?? $dados['permissoes'] ?? '');
        if (!empty($permissoesRaw)) {
            $permissoesArray = array_map('trim', explode(',', $permissoesRaw));
            $permissoesValidas = [];
            foreach ($permissoesArray as $p) {
                $pSlug = Str::slug($p, '_'); 
                if (\Spatie\Permission\Models\Permission::where('name', $pSlug)->exists()) $permissoesValidas[] = $pSlug;
            }
            if (count($permissoesValidas) > 0) $usuario->givePermissionTo($permissoesValidas);
        }
    }

    private function processarInscricao(array $linhaOriginal, array $mapeamento)
    {
        $dadosFixos = [];
        $dadosDinamicos = [];

        $autoCadastroAtivo = filter_var($mapeamento['config_auto_cadastro'] ?? false, FILTER_VALIDATE_BOOLEAN);

        foreach ($mapeamento as $colunaPlanilha => $config) {
            if (in_array($colunaPlanilha, ['ciclo_id', 'config_auto_cadastro', 'config_mesclar_duplicadas', 'linhas_reprocessar'])) continue;

            $destino = $config['destino'] ?? 'ignorar';
            if ($destino === 'ignorar') continue;

            $valorPlanilha = trim((string) ($linhaOriginal[$colunaPlanilha] ?? ''));
            if ($valorPlanilha === '') continue;

            $tipoMapeado = $config['tipo'] ?? 'texto';

            // ========================================================
            // CORREÇÃO: INTERPRETADOR UNIVERSAL DE DATAS
            // ========================================================
            if (in_array($tipoMapeado, ['data', 'data_hora']) || str_contains($destino, 'data')) {
                $valData = str_replace('/', '-', $valorPlanilha); // Padroniza tudo para hífen
                
                // Formato de 2 dígitos no ano (DD-MM-YY ou MM-DD-YY)
                if (preg_match('/^(\d{2})-(\d{2})-(\d{2})$/', $valData, $m)) {
                    $p1 = (int)$m[1];
                    $p2 = (int)$m[2];
                    $y = (int)$m[3];
                    $year = $y < 50 ? 2000 + $y : 1900 + $y; // Assume que 00-49 é anos 2000, e 50-99 é 1900
                    
                    if ($p1 > 12) { // Ex: 31-01-10 (DD-MM)
                        $valorPlanilha = sprintf('%04d-%02d-%02d', $year, $p2, $p1);
                    } elseif ($p2 > 12) { // Ex: 01-31-10 (MM-DD)
                        $valorPlanilha = sprintf('%04d-%02d-%02d', $year, $p1, $p2);
                    } else { // Assume padrão Brasileiro (DD-MM) para duvidosos
                        $valorPlanilha = sprintf('%04d-%02d-%02d', $year, $p2, $p1);
                    }
                } 
                // Formato de 4 dígitos no ano (DD-MM-YYYY ou MM-DD-YYYY)
                elseif (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $valData, $m)) {
                    $p1 = (int)$m[1];
                    $p2 = (int)$m[2];
                    $year = (int)$m[3];

                    if ($p1 > 12) { 
                        $valorPlanilha = sprintf('%04d-%02d-%02d', $year, $p2, $p1);
                    } elseif ($p2 > 12) { 
                        $valorPlanilha = sprintf('%04d-%02d-%02d', $year, $p1, $p2);
                    } else { 
                        $valorPlanilha = sprintf('%04d-%02d-%02d', $year, $p2, $p1);
                    }
                }
            }

            // Tratamento Monetário
            if ($tipoMapeado === 'monetario' || str_contains($destino, 'renda')) {
                $valTemp = preg_replace('/[^0-9,-]/', '', $valorPlanilha); 
                $valTemp = str_replace(',', '.', $valTemp);
                if (is_numeric($valTemp)) {
                    $valorPlanilha = $valTemp;
                }
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
                        
                        $termo = preg_replace('/[\r\n]+.*/s', '', $termoOriginal);
                        $termo = explode(',', $termo)[0];
                        $termo = explode(';', $termo)[0];
                        $termo = trim($termo);
                        
                        if (strlen($termo) > 80) {
                            $termo = trim(substr($termo, 0, 80));
                        }

                        if ($coluna === 'unidade_id' && str_contains($termo, '-')) {
                            $partes = explode('-', $termo);
                            $termo = trim(end($partes));
                        }

                        $ModelClass = $config->model_class;
                        $campoBusca = $config->campo_busca;

                        $registro = $ModelClass::where($campoBusca, 'ilike', '%' . $termo . '%')->first();

                        if (!$registro && $permiteAutoCadastro && $config->auto_cadastro) {
                            $payload = $config->payload_padrao ?? [];
                            $payload[$campoBusca] = $termo;
                            
                            if (!isset($payload['slug'])) {
                                $payload['slug'] = Str::slug($termo);
                            }
                            if (str_contains($ModelClass, 'Turno') && !isset($payload['horario_inicio'])) {
                                $payload['horario_inicio'] = '00:00:00';
                            }

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
        $dadosFixos['ciclo_id'] = $mapeamento['ciclo_id'] ?? $linhaOriginal['ciclo_id'] ?? null;
        $dadosFixos['origem'] = 'importacao';

        $mesclarDuplicatas = filter_var($mapeamento['config_mesclar_duplicadas'] ?? false, FILTER_VALIDATE_BOOLEAN);

        Inscricao::withoutEvents(function () use ($dadosFixos, $mesclarDuplicatas) {
            if (!empty($dadosFixos['cpf']) && !empty($dadosFixos['ciclo_id'])) {
                $inscricaoExistente = Inscricao::where('cpf', $dadosFixos['cpf'])
                                               ->where('ciclo_id', $dadosFixos['ciclo_id'])
                                               ->first();
                
                if ($inscricaoExistente) {
                    if (!$mesclarDuplicatas) {
                        throw new \Exception("Candidato ignorado: CPF '{$dadosFixos['cpf']}' já cadastrado para este mesmo Ciclo.", 23505);
                    }

                    $dadosAtuais = $inscricaoExistente->toArray();
                    $dinamicoAntigo = is_string($inscricaoExistente->dados_dinamicos) ? json_decode($inscricaoExistente->dados_dinamicos, true) : ($inscricaoExistente->dados_dinamicos ?? []);
                    $dinamicoNovo = $dadosFixos['dados_dinamicos'] ?? [];
                    
                    foreach ($dinamicoNovo as $chaveNova => $valorNovo) {
                        if (!isset($dinamicoAntigo[$chaveNova]) || empty(trim($dinamicoAntigo[$chaveNova]))) {
                            $dinamicoAntigo[$chaveNova] = $valorNovo;
                        }
                    }
                    $dadosFixos['dados_dinamicos'] = $dinamicoAntigo;

                    foreach ($dadosFixos as $coluna => $valorImportado) {
                        if (in_array($coluna, ['id', 'created_at', 'updated_at', 'student_id', 'dados_dinamicos', 'origem'])) continue;
                        $valorAtualBanco = $dadosAtuais[$coluna] ?? null;
                        
                        if ($valorAtualBanco !== null && trim((string)$valorAtualBanco) !== '') {
                            unset($dadosFixos[$coluna]);
                        }
                    }

                    $inscricaoExistente->update($dadosFixos);
                    return;
                }
            }

            Inscricao::create($dadosFixos);
        });
    }
}