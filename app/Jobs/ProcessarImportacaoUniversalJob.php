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

class ProcessarImportacaoUniversalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; 
    protected $importacao;

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

            foreach ($registros as $linhaOriginal) {
                $linhaAtual++;

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
                    $isDuplicate = $e->getCode() === '23505'; // Unique violation
                    $isNotNull = $e->getCode() === '23502';   // Not null violation
                    
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
                        'amigavel' => 'A informação fornecida na planilha está em um formato inválido.'
                    ];
                }

                // O sistema só aborta se tiver 100+ erros CRÍTICOS (ignora os alertas de duplicatas na contagem de aborto)
                if ($errosCriticos >= 100) {
                    throw new \Exception("Excesso de erros estruturais detectados (100+). Processamento abortado por segurança.");
                }

                if ($linhaAtual % 50 === 0) {
                    $this->importacao->update(['linhas_processadas' => $linhaAtual]);
                }
            }

            $statusFinal = count($erros) > 0 ? (count($erros) >= $linhaAtual ? 'erro' : 'erro_parcial') : 'concluido';
            $this->importacao->update([
                'status' => $statusFinal,
                'linhas_processadas' => $linhaAtual,
                'erro_mensagem' => count($erros) > 0 ? json_encode($erros, JSON_UNESCAPED_UNICODE) : null
            ]);

            // === LOG DE AUDITORIA EM LOTE ===
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
            // ======================================

        } catch (\Throwable $e) {
            array_unshift($erros, [
                'linha' => 'Crítico/Sistema', 
                'tipo' => 'Falha Crítica',
                'mensagem' => $e->getMessage(),
                'amigavel' => 'A importação falhou de maneira irrecuperável. Verifique se o arquivo está no formato correto.'
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
            return $reader->getRows();
        }

        if ($formato === 'json') {
            $dados = json_decode(file_get_contents($caminho), true);
            return collect($dados ?? []);
        }

        if ($formato === 'xml') {
            $xml = simplexml_load_file($caminho);
            $json = json_encode($xml);
            $dados = json_decode($json, true);
            $primeiraChave = array_key_first($dados);
            return collect($dados[$primeiraChave] ?? $dados);
        }

        throw new \Exception("Formato de arquivo não suportado.");
    }

    private function processarCampo(array $dados, array $mapeamento)
    {
        // ... Lógica mantida sem alterações ...
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

    private function processarUsuario(array $dados)
    {
        // ... Lógica mantida sem alterações ...
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

        foreach ($mapeamento as $colunaExcel => $config) {
            $colunaValida = is_array($config) && isset($config['coluna_nome']) ? $config['coluna_nome'] : $colunaExcel;

            if (!isset($linhaOriginal[$colunaValida])) continue;

            $destino = is_array($config) ? ($config['destino'] ?? 'ignorar') : $config;
            $tipoDado = is_array($config) ? ($config['tipo'] ?? 'texto') : 'texto';
            
            if ($destino === 'ignorar') continue;
            
            $valor = trim($linhaOriginal[$colunaValida]);

            if (!empty($valor) && in_array($tipoDado, ['data', 'data_hora'])) {
                try {
                    $formatoSaida = $tipoDado === 'data' ? 'Y-m-d' : 'Y-m-d H:i:s';
                    $valor = \Carbon\Carbon::parse(str_replace('/', '-', $valor))->format($formatoSaida);
                } catch (\Exception $e) {
                    throw new \Exception("Data inválida na coluna '{$colunaValida}': {$valor}");
                }
            }
            elseif (!empty($valor) && $tipoDado === 'monetario') {
                $valor = floatval(preg_replace('/[^0-9.]/', '', str_replace(',', '.', $valor)));
            }

            if (str_starts_with($destino, 'dinamico:')) {
                $chaveDinamica = str_replace('dinamico:', '', $destino);
                $dadosDinamicos[$chaveDinamica] = $valor;
            } elseif ($destino === 'dados_dinamicos') {
                $dadosDinamicos[Str::slug($colunaValida, '_')] = $valor;
            } else {
                $dadosFixos[$destino] = $valor;
            }
        }

        if (empty($dadosFixos['nome']) && empty($dadosFixos['cpf'])) {
            throw new \Exception("A linha não possui identificador básico (Nome ou CPF mapeado).");
        }

        if (empty($dadosFixos['cpf'])) $dadosFixos['cpf'] = null;
        if (empty($dadosFixos['email'])) $dadosFixos['email'] = null;

        // =========================================================================
        // MOTOR INTELIGENTE DE TRADUÇÃO DE IDS (Lido dinamicamente do Hub do Dev)
        // =========================================================================

        $configsRelacionamento = \App\Models\ImportacaoConfig::all();
        $permiteAutoCadastro = $mapeamento['config_auto_cadastro'] ?? false;

        foreach ($configsRelacionamento as $config) {
            $coluna = $config->coluna;
            
            if (array_key_exists($coluna, $dadosFixos)) {
                
                if (!empty($dadosFixos[$coluna])) {
                    if (!is_numeric($dadosFixos[$coluna])) {
                        $termo = trim($dadosFixos[$coluna]);
                        
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
                            
                            // === SALVAGUARDAS PARA COLUNAS NOT NULL ESTRUTURAIS ===
                            // Garante que a coluna 'slug' seja sempre gerada para não quebrar o banco
                            if (!isset($payload['slug'])) {
                                $payload['slug'] = Str::slug($termo);
                            }
                            // Salvaguarda específica para a tabela de Turnos
                            if (str_contains($ModelClass, 'Turno') && !isset($payload['horario_inicio'])) {
                                $payload['horario_inicio'] = '00:00:00';
                            }
                            // ======================================================

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

        // =========================================================================

        $dadosFixos['dados_dinamicos'] = $dadosDinamicos;
        $dadosFixos['ciclo_id'] = $mapeamento['ciclo_id'] ?? $linhaOriginal['ciclo_id'] ?? null;
        $dadosFixos['origem'] = 'importacao';
        
        Inscricao::withoutEvents(function () use ($dadosFixos) {
            Inscricao::create($dadosFixos);
        });
    }
}