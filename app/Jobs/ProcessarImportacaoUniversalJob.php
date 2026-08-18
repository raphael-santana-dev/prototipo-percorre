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

class ProcessarImportacaoUniversalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Aumentamos o tempo limite do Job para arquivos muito grandes (em segundos)
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

        try {
            $this->importacao->update(['status' => 'processando']);
            $caminhoAbsoluto = Storage::disk('local')->path($this->importacao->arquivo_caminho);
            $formato = $this->importacao->formato;
            
            // Extrai as linhas sob demanda (Lazy Loading) para não estourar a RAM
            $registros = $this->extrairRegistrosLazy($caminhoAbsoluto, $formato);
            $mapeamento = $this->importacao->mapeamento ?? [];

            foreach ($registros as $linha) {
                $linhaAtual++;

                try {
                    // Direciona para o processador correto
                    match ($this->importacao->tipo) {
                        'campos' => $this->processarCampo($linha, $mapeamento),
                        'usuarios' => $this->processarUsuario($linha),
                        'inscricoes' => $this->processarInscricao($linha, $mapeamento),
                        default => throw new \Exception("Tipo de importação '{$this->importacao->tipo}' não implementado."),
                    };

                } catch (\Throwable $e) {
                    $erros[] = ['linha' => $linhaAtual, 'mensagem' => $e->getMessage()];
                    
                    // Trava de segurança: se der mais de 100 erros seguidos, aborta a operação.
                    if (count($erros) >= 100) {
                        throw new \Exception("Excesso de erros detectados. Processamento abortado por segurança.");
                    }
                }

                // Atualiza a barra de progresso a cada 50 linhas para não floodar o banco de dados
                if ($linhaAtual % 50 === 0) {
                    $this->importacao->update(['linhas_processadas' => $linhaAtual]);
                }
            }

            // Definição do Status Final
            $statusFinal = count($erros) > 0 ? (count($erros) >= $linhaAtual ? 'erro' : 'erro_parcial') : 'concluido';
            $this->importacao->update([
                'status' => $statusFinal,
                'linhas_processadas' => $linhaAtual,
                'erro_mensagem' => count($erros) > 0 ? json_encode($erros, JSON_UNESCAPED_UNICODE) : null
            ]);

        } catch (\Throwable $e) {
            array_unshift($erros, ['linha' => 'Crítico/Sistema', 'mensagem' => $e->getMessage()]);
            $this->importacao->update([
                'status' => 'erro', 
                'erro_mensagem' => json_encode($erros, JSON_UNESCAPED_UNICODE)
            ]);
        }
    }

    /**
     * Leitor Universal que retorna os dados via Iterable (Generator ou LazyCollection)
     */
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

    // =========================================================================
    // PROCESSADORES ESPECÍFICOS POR TIPO DE DADO
    // =========================================================================

    private function processarCampo(array $linha, array $mapeamento)
    {
        $cicloId = $mapeamento['ciclo_id'] ?? null;
        if (!$cicloId) throw new \Exception("ID do Ciclo ausente no mapeamento.");

        // Normalização das chaves do Excel
        $dados = array_change_key_case($linha, CASE_LOWER);
        
        $name = trim($dados['name'] ?? $dados['nome do campo'] ?? '');
        if (empty($name)) throw new \Exception("A coluna 'name' ou 'nome do campo' é obrigatória.");

        // Tratamento da Largura (Vazio = 100% / 12 cols)
        $largura = trim($dados['largura'] ?? '');
        $larguraVal = empty($largura) ? 12 : (int)$largura;

        // Tratamento dos Booleanos
        $obrigRaw = trim($dados['obrigatório'] ?? $dados['obrigatorio'] ?? 'nao');
        $isObrigatorio = in_array(strtolower($obrigRaw), ['sim', 's', '1', 'true', 'yes']);

        $sempreVisivelRaw = trim($dados['sempre visível?'] ?? $dados['sempre visivel'] ?? 'sim');
        $sempreVisivel = in_array(strtolower($sempreVisivelRaw), ['sim', 's', '1', 'true', 'yes']);

        // Regras de Exibição via Regex (ex: "nome>=Luis")
        $regrasStr = trim($dados['regras de exibição'] ?? $dados['regras de exibicao'] ?? '');
        $dependeDe = null;
        $dependeOperador = '=';
        $dependeValor = null;

        if (!$sempreVisivel && !empty($regrasStr)) {
            // Separa Variável, Operador e Valor usando Regex
            if (preg_match('/^([a-zA-Z0-9_]+)(>=|<=|!=|=|>|<)(.*)$/', $regrasStr, $matches)) {
                $dependeDe = trim($matches[1]);
                $dependeOperador = trim($matches[2]);
                $dependeValor = trim($matches[3]);
            } else {
                throw new \Exception("Regra de exibição mal formatada. Use o padrão 'campo=valor' ou 'campo>=valor'.");
            }
        }

        // Tratamento das Opções Inteligentes (db:tabela:filtro ou CSV)
        $opcoesRaw = trim($dados['opções'] ?? $dados['opcoes'] ?? '');
        $opcoesArray = null;

        if (!empty($opcoesRaw)) {
            if (str_starts_with(strtolower($opcoesRaw), 'db:')) {
                // Exemplo de Input: db:unidade:ativas
                $partes = explode(':', $opcoesRaw);
                $opcoesArray = [
                    'origem_bd' => $partes[1] ?? '',
                    'filtro' => $partes[2] ?? ''
                ];
            } else {
                // Opções normais separadas por vírgula
                $opcoesArray = array_map('trim', explode(',', $opcoesRaw));
            }
        }

        // Salva o Campo
        CampoFormulario::updateOrCreate(
            ['ciclo_id' => $cicloId, 'name' => Str::slug($name, '_')],
            [
                'etapa' => (int)($dados['etapa'] ?? 1),
                'ordem' => (int)($dados['ordem'] ?? 0),
                'label' => trim($dados['label'] ?? $name),
                'tipo' => trim($dados['tipo'] ?? 'text'),
                'largura' => $larguraVal,
                'subtipo' => trim($dados['subtipo'] ?? 'text'),
                'opcoes' => $opcoesArray,
                'obrigatorio' => $isObrigatorio,
                'depende_de' => $dependeDe,
                'depende_operador' => $dependeOperador,
                'depende_valor' => $dependeValor,
            ]
        );
    }

    private function processarUsuario(array $linha)
    {
        $dados = array_change_key_case($linha, CASE_LOWER);
        
        $cpfRaw = $dados['cpf'] ?? null;
        if (empty($cpfRaw)) throw new \Exception("A coluna 'cpf' é obrigatória para usuários.");

        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpfRaw);
        $email = trim($dados['email'] ?? '');
        $nome = trim($dados['nome'] ?? 'Usuário Sem Nome');
        $senha = trim($dados['senha'] ?? $cpfLimpo);
        
        $usuario = User::where('cpf', $cpfLimpo)->orWhere('email', $email)->first();

        if (!$usuario) {
            $usuario = User::create([
                'name' => $nome,
                'email' => $email,
                'cpf' => $cpfLimpo,
                'password' => Hash::make($senha),
            ]);
        } else {
            $usuario->update([
                'name' => $nome,
                'cpf' => $cpfLimpo,
                'email' => $email ?: $usuario->email,
            ]);
        }

        // Atribuição de Grupo de Acesso
        $roleName = trim($dados['role'] ?? $dados['grupo'] ?? '');
        if (!empty($roleName)) {
            $usuario->assignRole(Str::slug($roleName, '-'));
        }
    }

    private function processarInscricao(array $linha, array $mapeamento)
    {
        $dadosFixos = [];
        $dadosDinamicos = [];

        foreach ($mapeamento as $colunaExcel => $config) {
            if (!isset($linha[$colunaExcel])) continue;

            $destino = $config['destino'] ?? 'ignorar';
            $tipoDado = $config['tipo'] ?? 'texto';
            if ($destino === 'ignorar') continue;
            
            $valor = trim($linha[$colunaExcel]);

            // Limpeza e Formatação de Datas
            if (!empty($valor) && in_array($tipoDado, ['data', 'data_hora'])) {
                try {
                    $formatoSaida = $tipoDado === 'data' ? 'Y-m-d' : 'Y-m-d H:i:s';
                    $valor = \Carbon\Carbon::parse(str_replace('/', '-', $valor))->format($formatoSaida);
                } catch (\Exception $e) {
                    throw new \Exception("Data inválida na coluna '{$colunaExcel}': {$valor}");
                }
            }

            if ($destino === 'dados_dinamicos') {
                $dadosDinamicos[Str::slug($colunaExcel, '_')] = $valor;
            } else {
                $dadosFixos[$destino] = $valor;
            }
        }

        if (empty($dadosFixos['nome']) && empty($dadosFixos['cpf'])) {
            throw new \Exception("A linha não possui identificador básico (Nome ou CPF mapeado).");
        }

        $dadosFixos['dados_dinamicos'] = $dadosDinamicos;
        $dadosFixos['status_inscricao_id'] = 1; // 1 = Pendente
        
        Inscricao::create($dadosFixos);
    }
}