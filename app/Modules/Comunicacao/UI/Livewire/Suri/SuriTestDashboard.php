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

    public ?string $modalAtivo = null; // 'listar_contatos', 'contato', 'tpl_simples', 'tpl_botoes', 'tpl_variaveis', 'msg_simples', 'guia_suri'

    // Parâmetros de Listagem de Contatos (GET /api/contacts)
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

    // Preenchimento rápido a partir de um inscrito do banco
    public $inscricaoTesteId = '';

    // Console de Resposta da Última Execução / Log Selecionado
    public ?array $ultimoResultado = null;
    public ?int $logSelecionadoId = null;

    // Filtros do Histórico de Logs Exclusivo
    public string $filtroBuscaLog = '';
    public string $filtroAcaoLog = '';
    public string $filtroStatusLog = '';

    public function mount()
    {
        $this->channelId = (string) env('SURI_CHANNEL_ID', '');

        // Carrega o último teste realizado no banco para já exibi-lo no Console
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
     * Registra todas as informações do Console de Inspeção HTTP na tabela exclusiva suri_test_logs
     */
    private function registrarResultado(string $acao, array $resultado): void
    {
        $user = auth()->user();

        $logModel = SuriTestLog::create([
            'acao'        => $acao,
            'metodo'      => $resultado['metodo'] ?? 'POST',
            'url'         => $resultado['url'] ?? '',
            'http_status' => (int) ($resultado['http_status'] ?? 0),
            'sucesso'     => (bool) ($resultado['sucesso'] ?? false),
            'payload'     => $resultado['payload'] ?? [],
            'resposta'    => $resultado['resposta'] ?? [],
            'user_id'     => $user?->id,
            'user_nome'   => $user?->name ?? 'Sistema',
            'ip'          => request()->ip(),
        ]);

        $this->logSelecionadoId = $logModel->id;
        $this->ultimoResultado = [
            'id'          => $logModel->id,
            'acao'        => $logModel->acao,
            'metodo'      => $logModel->metodo,
            'url'         => $logModel->url,
            'http_status' => $logModel->http_status,
            'sucesso'     => $logModel->sucesso,
            'payload'     => $logModel->payload,
            'resposta'    => $logModel->resposta,
            'user_nome'   => $logModel->user_nome,
            'horario'     => $logModel->created_at->format('d/m/Y H:i:s'),
        ];

        $contatoIdRetornado = data_get($resultado, 'resposta.data.id') ?? data_get($resultado, 'resposta.id');
        if ($acao === 'Importar Contato' && $contatoIdRetornado) {
            $this->userId = (string) $contatoIdRetornado;
        }

        if ($resultado['sucesso']) {
            $this->dispatch('sucesso', msg: "{$acao}: Requisição executada e gravada no Log (HTTP {$resultado['http_status']})!");
        } else {
            $this->dispatch('erro', msg: "{$acao}: Falha na requisição (HTTP {$resultado['http_status']}). Registro salvo no Log.");
        }
    }

    /**
     * Carrega qualquer registro do histórico de logs de volta para o Console de Inspeção HTTP
     */
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

    // =========================================================================
    // ENDPOINTS DE TESTE DA SURI
    // =========================================================================

    public function dispararListarContatos(bool $usarProximoToken = false): void
    {
        if ($usarProximoToken && $this->proximoContinuationToken) {
            $this->continuationToken = $this->proximoContinuationToken;
        }

        $this->validate([
            'limit'             => 'required|string',
            'continuationToken' => 'nullable|string',
        ]);

        $service = app(SuriApiService::class);
        $res = $service->listarContatos(
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

        $this->dispatch('sucesso', msg: "Contato '{$id}' selecionado e carregado para os testes de envio!");
    }

    public function dispararImportarContato(): void
    {
        $this->validate([
            'name'        => 'required|string|min:2',
            'phone'       => 'required|string|min:10',
            'channelId'   => 'required|string',
            'channelType' => 'required|integer',
        ]);

        $service = app(SuriApiService::class);
        $res = $service->importarContato(
            $this->name,
            $this->phone,
            $this->gender,
            $this->channelId,
            $this->channelType
        );

        $this->registrarResultado('Importar Contato', $res);
    }

    public function dispararTemplateSimples(): void
    {
        $this->validate([
            'userId'     => 'required|string',
            'templateId' => 'required|string',
        ]);

        $service = app(SuriApiService::class);
        $res = $service->enviarTemplateSimples(
            $this->userId,
            $this->templateId,
            $this->flowId ?: null,
            $this->ignorePortfolio,
            $this->responseActionType
        );

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

        $service = app(SuriApiService::class);
        $res = $service->enviarTemplateComBotoes(
            $this->name,
            $this->phone,
            $this->templateId,
            $this->email ?: null,
            $this->gender,
            $this->channelId,
            $this->channelType,
            $this->defaultDepartmentId ?: null
        );

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

        $service = app(SuriApiService::class);
        $res = $service->enviarTemplateComVariaveis(
            $this->name,
            $this->phone,
            $this->templateId,
            $paramsLimpos,
            $this->flowId ?: null,
            $this->ignorePortfolio,
            $this->responseActionType,
            $this->email ?: null,
            $this->gender,
            $this->channelId,
            $this->channelType,
            $this->defaultDepartmentId ?: null
        );

        $this->registrarResultado('Template com Variáveis', $res);
    }

    public function dispararMensagemSimples(): void
    {
        $this->validate([
            'userId'        => 'required|string',
            'textoMensagem' => 'required|string|min:1',
        ]);

        $service = app(SuriApiService::class);
        $res = $service->enviarMensagemSimples(
            $this->userId,
            $this->textoMensagem,
            $this->contextMsgId ?: null
        );

        $this->registrarResultado('Mensagem Simples (Texto Livre)', $res);
    }

    public function sincronizarTemplateLocal(int $id): void
    {
        $tpl = EmailTemplate::findOrFail($id);
        $service = app(SuriApiService::class);

        $cleanBody = strip_tags(html_entity_decode((string) $tpl->corpo, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $payloadPreview = [
            'template_local_id' => $tpl->id,
            'nome'              => $tpl->nome,
            'corpo_limpo'       => $cleanBody,
        ];

        $suriId = $service->syncTemplate($tpl);

        $this->registrarResultado('Sincronizar Template Local', [
            'sucesso'     => !empty($suriId),
            'metodo'      => 'POST',
            'http_status' => !empty($suriId) ? 200 : 400,
            'url'         => rtrim((string) env('SURI_API_URL', ''), '/') . '/api/templates',
            'payload'     => $payloadPreview,
            'resposta'    => !empty($suriId)
                ? ['success' => true, 'suri_template_id' => $suriId]
                : ['success' => false, 'error' => 'Falha ao sincronizar template com a Suri.'],
        ]);

        if ($suriId) {
            $tpl->update(['suri_template_id' => $suriId]);
            $this->templateId = $suriId;
            $this->dispatch('sucesso', msg: "Template sincronizado! ID Suri: {$suriId}");
        } else {
            $this->dispatch('erro', msg: 'Falha ao sincronizar template com a Suri.');
        }
    }

    public function render()
    {
        $configSuri = app(SuriApiService::class)->getConfigResumo();

        $templatesLocais = EmailTemplate::whereIn('canal', ['whatsapp', 'ambos'])
            ->orderBy('id', 'desc')
            ->get();

        $inscricoesRecentes = Inscricao::select('id', 'nome', 'celular', 'email')
            ->whereNotNull('celular')
            ->latest('id')
            ->limit(30)
            ->get();

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