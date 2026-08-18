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
            
            $registros = $this->extrairRegistrosLazy($caminhoAbsoluto, $formato);
            $mapeamento = $this->importacao->mapeamento ?? [];

            foreach ($registros as $linhaOriginal) {
                $linhaAtual++;

                try {
                    // LIMPEZA ANTI-BOM: Remove o caractere invisível que o Excel deixa e coloca tudo em minúsculo
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

                } catch (\Throwable $e) {
                    $erros[] = ['linha' => $linhaAtual, 'mensagem' => $e->getMessage()];
                    
                    if (count($erros) >= 100) {
                        throw new \Exception("Excesso de erros detectados (100+). Processamento abortado por segurança.");
                    }
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

        } catch (\Throwable $e) {
            array_unshift($erros, ['linha' => 'Crítico/Sistema', 'mensagem' => $e->getMessage()]);
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

    // =========================================================================
    // PROCESSADORES (AGORA ACEITAM OS CABEÇALHOS AMIGÁVEIS!)
    // =========================================================================

    private function processarCampo(array $dados, array $mapeamento)
    {
        $cicloId = $mapeamento['ciclo_id'] ?? null;
        if (!$cicloId) throw new \Exception("ID do Ciclo ausente no mapeamento.");
        
        $label = trim($dados['nome do campo'] ?? $dados['label'] ?? '');
        if (empty($label)) throw new \Exception("A coluna 'Nome do Campo' (ou 'label') é obrigatória.");

        // Se o Excel vier com a coluna "ID no Banco", usamos, senão usamos Slug do Título
        $name = trim($dados['id no banco'] ?? $dados['id no banco (name)'] ?? $dados['name'] ?? '');
        if (empty($name)) {
            $name = Str::slug($label, '_');
        }

        $largura = trim($dados['largura'] ?? '');
        $larguraVal = empty($largura) ? 12 : (int)$largura;

        $obrigRaw = trim($dados['obrigatório'] ?? $dados['obrigatorio'] ?? 'nao');
        $isObrigatorio = in_array(strtolower($obrigRaw), ['sim', 's', '1', 'true', 'yes']);

        $sempreVisivelRaw = trim($dados['sempre visível?'] ?? $dados['sempre visivel'] ?? 'sim');
        $sempreVisivel = in_array(strtolower($sempreVisivelRaw), ['sim', 's', '1', 'true', 'yes']);

        $regrasStr = trim($dados['regras de exibição'] ?? $dados['regras de exibicao'] ?? '');
        $dependeDe = null;
        $dependeOperador = '=';
        $dependeValor = null;

        if (!$sempreVisivel && !empty($regrasStr)) {
            if (preg_match('/^([a-zA-Z0-9_]+)(>=|<=|!=|=|>|<)(.*)$/', $regrasStr, $matches)) {
                $dependeDe = trim($matches[1]);
                $dependeOperador = trim($matches[2]);
                $dependeValor = trim($matches[3]);
            } else {
                throw new \Exception("Regra mal formatada. Exemplo correto: 'como_conheceu=Instagram'.");
            }
        }

        $opcoesRaw = trim($dados['opções'] ?? $dados['opcoes'] ?? '');
        $opcoesArray = null;

        if (!empty($opcoesRaw)) {
            if (str_starts_with(strtolower($opcoesRaw), 'bd:') || str_starts_with(strtolower($opcoesRaw), 'db:')) {
                // Suporta db:unidade:ativas ou bd:unidade:ativas
                $partes = explode(':', $opcoesRaw);
                $opcoesArray = [
                    'origem_bd' => $partes[1] ?? '',
                    'filtro' => $partes[2] ?? ''
                ];
            } else {
                $opcoesArray = array_map('trim', explode(',', $opcoesRaw));
            }
        }

        CampoFormulario::updateOrCreate(
            ['ciclo_id' => $cicloId, 'name' => $name],
            [
                'etapa' => (int)($dados['etapa'] ?? 1),
                'ordem' => (int)($dados['ordem'] ?? 0),
                'label' => $label,
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

    private function processarUsuario(array $dados)
    {
        $cpfRaw = $dados['cpf'] ?? null;
        if (empty($cpfRaw)) throw new \Exception("A coluna 'CPF' é obrigatória.");

        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpfRaw);
        $email = trim($dados['e-mail'] ?? $dados['email'] ?? '');
        $nome = trim($dados['nome completo'] ?? $dados['nome'] ?? 'Usuário Sem Nome');
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

        $roleName = trim($dados['grupo de acesso'] ?? $dados['role'] ?? '');
        if (!empty($roleName)) {
            $usuario->assignRole(Str::slug($roleName, '-'));
        }

        $permissoesRaw = trim($dados['permissões extras'] ?? $dados['permissoes'] ?? '');
        if (!empty($permissoesRaw)) {
            $permissoesArray = array_map('trim', explode(',', $permissoesRaw));
            $permissoesValidas = [];

            foreach ($permissoesArray as $p) {
                $pSlug = Str::slug($p, '_'); 
                if (\Spatie\Permission\Models\Permission::where('name', $pSlug)->exists()) {
                    $permissoesValidas[] = $pSlug;
                }
            }
            
            if (count($permissoesValidas) > 0) {
                $usuario->givePermissionTo($permissoesValidas);
            }
        }
    }

    private function processarInscricao(array $linhaOriginal, array $mapeamento)
    {
        // Aqui usamos a linha inteira crua porque o mapeamento se baseia nas colunas EXATAS enviadas pelo usuario
        $dadosFixos = [];
        $dadosDinamicos = [];

        foreach ($mapeamento as $colunaExcel => $config) {
            if (!isset($linhaOriginal[$colunaExcel])) continue;

            $destino = $config['destino'] ?? 'ignorar';
            $tipoDado = $config['tipo'] ?? 'texto';
            if ($destino === 'ignorar') continue;
            
            $valor = trim($linhaOriginal[$colunaExcel]);

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
        $dadosFixos['status_inscricao_id'] = 1; 
        
        Inscricao::create($dadosFixos);
    }
}