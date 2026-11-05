<?php

namespace App\Modules\Comunicacao\UI\Livewire\Suri;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\Teste\Suri\Services\SuriApiService;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;
use App\Models\Inscricao;

#[Layout('components.layouts.app')]
#[Title('Laboratório de Integração Suri (WhatsApp)')]
class SuriTestDashboard extends Component
{
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

    // Console de Resposta da Última Execução
    public ?array $ultimoResultado = null;
    public array $historicoTestes = [];

    public function mount()
    {
        $this->channelId = (string) env('SURI_CHANNEL_ID', '');
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

    private function registrarResultado(string $acao, array $resultado): void
    {
        $this->ultimoResultado = array_merge(['acao' => $acao, 'horario' => now()->format('H:i:s')], $resultado);
        array_unshift($this->historicoTestes, $this->ultimoResultado);
        $this->historicoTestes = array_slice($this->historicoTestes, 0, 10);

        $contatoIdRetornado = data_get($resultado, 'resposta.data.id') ?? data_get($resultado, 'resposta.id');
        if ($acao === 'Importar Contato' && $contatoIdRetornado) {
            $this->userId = (string) $contatoIdRetornado;
        }

        if ($resultado['sucesso']) {
            $this->dispatch('sucesso', msg: "{$acao}: Requisição executada com sucesso (HTTP {$resultado['http_status']})!");
        } else {
            $this->dispatch('erro', msg: "{$acao}: Falha na requisição (HTTP {$resultado['http_status']}). Verifique o console abaixo.");
        }
    }

    // =========================================================================
    // LISTAR CONTATOS (GET /api/contacts?continuationToken=string&limit=string)
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
        $suriId = app(SuriApiService::class)->syncTemplate($tpl);

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

        return view('livewire.comunicacao.suri.suri-test-dashboard', [
            'configSuri'         => $configSuri,
            'templatesLocais'    => $templatesLocais,
            'inscricoesRecentes' => $inscricoesRecentes,
        ]);
    }
}