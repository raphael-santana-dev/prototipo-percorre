<?php

namespace App\Modules\Comunicacao\UI\Livewire\Suri;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\Teste\Suri\Services\SuriApiService;
use App\Modules\Teste\Suri\Models\SuriTestLog;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;
use App\Models\Inscricao;

#[Layout('components.layouts.app')]
#[Title('Laboratório de Integração Suri (WhatsApp)')]
class SuriTestDashboard extends Component
{
    use WithPagination;

    public ?string $modalAtivo = null;

    // Parâmetros de Listagem de Contatos
    public string $continuationToken = '';
    public string $limit = '20';
    public array $contatosListadosSuri = [];
    public ?string $proximoContinuationToken = null;

    // Campos comuns dos Modais
    public string $name = '';
    public string $phone = '';
    public string $email = '';
    public int $gender = 0;
    public string $channelId = '';
    public int $channelType = 1;
    public string $defaultDepartmentId = '';

    public string $userId = '';
    public string $templateId = '';
    public string $flowId = '';
    public bool $ignorePortfolio = false;
    public int $responseActionType = 1;

    public array $bodyParameters = [''];
    public string $textoMensagem = '';
    public string $contextMsgId = '';

    public $inscricaoTesteId = '';

    // Console de Resposta e Histórico
    public ?array $ultimoResultado = null;
    public ?int $logSelecionadoId = null;

    public string $filtroBuscaLog = '';
    public string $filtroAcaoLog = '';
    public string $filtroStatusLog = '';

    public function mount()
    {
        $this->channelId = (string) env('SURI_CHANNEL_ID', '');

        $ultimoLog = SuriTestLog::latest('id')->first();
        if ($ultimoLog) {
            $this->carregarLogNoConsole($ultimoLog->id);
        }
    }

    public function updating($property)
    {
        if (in_array($property, ['filtroBuscaLog', 'filtroAcaoLog', 'filtroStatusLog'])) {
            $this->resetPage();
        }
    }

    public function limparFiltrosLog(): void
    {
        $this->reset(['filtroBuscaLog', 'filtroAcaoLog', 'filtroStatusLog']);
        $this->resetPage();
    }

    public function abrirModal(string $tipo): void
    {
        $this->resetValidation();
        $this->modalAtivo = $tipo;

        if ($tipo === 'listar_contatos' && empty($this->contatosListadosSuri)) {
            $this->dispararListarContatos();
        }
    }

    public function fecharModal(): void
    {
        $this->modalAtivo = null;
    }

    public function updatedInscricaoTesteId($id): void
    {
        if (!$id) return;
        $insc = Inscricao::find($id);
        if ($insc) {
            $this->name = (string) $insc->nome;
            $telefoneLimpo = preg_replace('/\D/', '', (string) $insc->celular);
            if ($telefoneLimpo !== '' && !str_starts_with($telefoneLimpo, '55')) {
                $telefoneLimpo = '55' . $telefoneLimpo;
            }
            $this->phone = $telefoneLimpo;
            $this->email = (string) $insc->email;
        }
    }

    public function adicionarParametro(): void
    {
        $this->bodyParameters[] = '';
    }

    public function removerParametro(int $index): void
    {
        if (count($this->bodyParameters) <= 1) {
            $this->bodyParameters = [''];
            return;
        }
        unset($this->bodyParameters[$index]);
        $this->bodyParameters = array_values($this->bodyParameters);
    }

    /**
     * O serviço já gravou o log. Aqui recuperamos pelo log_id retornado.
     */
    private function registrarResultado(string $acao, array $resultado): void
    {
        $logId = $resultado['log_id'] ?? null;
        if ($logId) {
            $this->carregarLogNoConsole($logId);
        }

        $contatoIdRetornado = data_get($resultado, 'resposta.data.id') ?? data_get($resultado, 'resposta.id');
        if (str_contains($acao, 'Importar Contato') && $contatoIdRetornado) {
            $this->userId = (string) $contatoIdRetornado;
        }

        if ($resultado['sucesso']) {
            $this->dispatch('sucesso', msg: "{$acao}: Executada com sucesso (HTTP {$resultado['http_status']})!");
        } else {
            $this->dispatch('erro', msg: "{$acao}: Falha na requisição (HTTP {$resultado['http_status']}). Verifique o console.");
        }
    }

    public function carregarLogNoConsole(int $logId): void
    {
        $log = SuriTestLog::find($logId);
        if (!$log) return;

        $this->logSelecionadoId = $log->id;
        $this->ultimoResultado = [
            'id'          => $log->id,
            'acao'        => $log->acao,
            'metodo'      => $log->metodo,
            'url'         => $log->url,
            'http_status' => $log->http_status,
            'sucesso'     => $log->sucesso,
            'payload'     => $log->payload ?? [],
            'resposta'    => $log->resposta ?? [],
            'user_nome'   => $log->user_nome ?? 'Sistema',
            'horario'     => $log->created_at ? $log->created_at->format('d/m/Y H:i:s') : '-',
        ];
    }

    public function replayLog(int $logId): void
    {
        $log = SuriTestLog::findOrFail($logId);
        $res = app(SuriApiService::class)->reexecutarLog(
            $log->metodo ?? 'POST',
            $log->url,
            is_array($log->payload) ? $log->payload : [],
            "Replay: {$log->acao}"
        );

        $this->registrarResultado("Replay: {$log->acao}", $res);
    }

    public function dispararListarTemplatesSuri(): void
    {
        $res = app(SuriApiService::class)->listarTemplates();
        $this->registrarResultado('Listar Templates', $res);
    }

    public function dispararBuscarTemplateEspecifico(): void
    {
        $this->validate([
            'templateId' => 'required|string',
        ], [
            'templateId.required' => 'Informe o templateId para consultar.',
        ]);

        $res = app(SuriApiService::class)->obterTemplate($this->templateId);
        $this->registrarResultado("Obter Template ({$this->templateId})", $res);
    }

    public function excluirLog(int $logId): void
    {
        SuriTestLog::where('id', $logId)->delete();

        if ($this->logSelecionadoId === $logId) {
            $proximo = SuriTestLog::latest('id')->first();
            if ($proximo) {
                $this->carregarLogNoConsole($proximo->id);
            } else {
                $this->ultimoResultado = null;
                $this->logSelecionadoId = null;
            }
        }
        $this->dispatch('sucesso', msg: 'Registro de log excluído.');
    }

    public function limparTodosOsLogs(): void
    {
        SuriTestLog::truncate();
        $this->ultimoResultado = null;
        $this->logSelecionadoId = null;
        $this->resetPage();
        $this->dispatch('sucesso', msg: 'Histórico de logs de testes da Suri limpo com sucesso!');
    }

    public function dispararListarContatos(bool $usarProximoToken = false): void
    {
        if ($usarProximoToken && $this->proximoContinuationToken) {
            $this->continuationToken = $this->proximoContinuationToken;
        }
        $this->validate([
            'limit'             => 'required|string',
            'continuationToken' => 'nullable|string',
        ]);
        $res = app(SuriApiService::class)->listarContatos(
            $this->continuationToken !== '' ? $this->continuationToken : null,
            $this->limit
        );

        if ($res['sucesso']) {
            $dados = data_get($res, 'resposta.data.items')
                ?? data_get($res, 'resposta.data.data')
                ?? data_get($res, 'resposta.data')
                ?? data_get($res, 'resposta.items')
                ?? [];

            $this->contatosListadosSuri = is_array($dados) && isset($dados[0]) ? $dados : [];
            $this->proximoContinuationToken = data_get($res, 'resposta.data.continuationToken')
                ?? data_get($res, 'resposta.continuationToken');
        }

        $this->registrarResultado('Listar Contatos', $res);
    }

    public function selecionarContatoSuri(string $id, string $nome = '', string $telefone = '', string $canalId = ''): void
    {
        $this->userId = $id;
        if ($nome !== '') $this->name = $nome;
        if ($telefone !== '') $this->phone = $telefone;
        if ($canalId !== '') $this->channelId = $canalId;

        $this->dispatch('sucesso', msg: "Contato '{$id}' selecionado para testes!");
    }

    public function dispararImportarContato(): void
    {
        $this->validate([
            'name'        => 'required|string|min:2',
            'phone'       => 'required|string|min:10',
            'channelId'   => 'required|string',
            'channelType' => 'required|integer',
        ]);
        $res = app(SuriApiService::class)->importarContato($this->name, $this->phone, $this->gender, $this->channelId, $this->channelType);
        $this->registrarResultado('Importar Contato', $res);
    }

    public function dispararTemplateSimples(): void
    {
        $this->validate([
            'userId'     => 'required|string',
            'templateId' => 'required|string',
        ]);
        $res = app(SuriApiService::class)->enviarTemplateSimples($this->userId, $this->templateId, $this->flowId ?: null, $this->ignorePortfolio, $this->responseActionType);
        $this->registrarResultado('Template Simples', $res);
    }

    public function dispararTemplateComBotoes(): void
    {
        $this->validate([
            'name'       => 'required|string|min:2',
            'phone'      => 'required|string|min:10',
            'templateId' => 'required|string',
            'channelId'  => 'required|string',
        ]);
        $res = app(SuriApiService::class)->enviarTemplateComBotoes($this->name, $this->phone, $this->templateId, $this->email ?: null, $this->gender, $this->channelId, $this->channelType, $this->defaultDepartmentId ?: null);
        $this->registrarResultado('Template com Botões', $res);
    }

    public function dispararTemplateComVariaveis(): void
    {
        $this->validate([
            'name'       => 'required|string|min:2',
            'phone'      => 'required|string|min:10',
            'templateId' => 'required|string',
            'channelId'  => 'required|string',
        ]);
        $paramsLimpos = array_values(array_filter(array_map('trim', $this->bodyParameters), fn($v) => $v !== ''));
        $res = app(SuriApiService::class)->enviarTemplateComVariaveis($this->name, $this->phone, $this->templateId, $paramsLimpos, $this->flowId ?: null, $this->ignorePortfolio, $this->responseActionType, $this->email ?: null, $this->gender, $this->channelId, $this->channelType, $this->defaultDepartmentId ?: null);
        $this->registrarResultado('Template com Variáveis', $res);
    }

    public function dispararMensagemSimples(): void
    {
        $this->validate([
            'userId'        => 'required|string',
            'textoMensagem' => 'required|string|min:1',
        ]);
        $res = app(SuriApiService::class)->enviarMensagemSimples($this->userId, $this->textoMensagem, $this->contextMsgId ?: null);
        $this->registrarResultado('Mensagem Simples (Texto Livre)', $res);
    }

    public function render()
    {
        $configSuri = app(SuriApiService::class)->getConfigResumo();
        $templatesLocais = EmailTemplate::whereIn('canal', ['whatsapp', 'ambos'])->orderBy('id', 'desc')->get();
        $inscricoesRecentes = Inscricao::select('id', 'nome', 'celular', 'email')->whereNotNull('celular')->latest('id')->limit(30)->get();

        $queryLogs = SuriTestLog::query()
            ->when($this->filtroAcaoLog !== '', fn($q) => $q->where('acao', $this->filtroAcaoLog))
            ->when($this->filtroStatusLog !== '', fn($q) => $q->where('sucesso', (bool) $this->filtroStatusLog))
            ->when($this->filtroBuscaLog !== '', function ($q) {
                $termo = trim($this->filtroBuscaLog);
                $q->where(function ($sub) use ($termo) {
                    $sub->where('url', 'ilike', "%{$termo}%")
                        ->orWhere('acao', 'ilike', "%{$termo}%")
                        ->orWhere('user_nome', 'ilike', "%{$termo}%")
                        ->orWhereRaw('CAST(payload AS TEXT) ILIKE ?', ["%{$termo}%"])
                        ->orWhereRaw('CAST(resposta AS TEXT) ILIKE ?', ["%{$termo}%"]);
                });
            })
            ->orderBy('id', 'desc');

        $acoesDistintas = SuriTestLog::select('acao')->distinct()->orderBy('acao')->pluck('acao')->toArray();

        return view('livewire.comunicacao.suri.suri-test-dashboard', [
            'configSuri'         => $configSuri,
            'templatesLocais'    => $templatesLocais,
            'inscricoesRecentes' => $inscricoesRecentes,
            'logsHistorico'      => $queryLogs->paginate(10),
            'acoesDistintas'     => $acoesDistintas,
            'totalLogsSalvos'    => SuriTestLog::count(),
        ]);
    }
}