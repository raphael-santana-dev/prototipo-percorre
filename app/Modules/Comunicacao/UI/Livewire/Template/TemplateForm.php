<?php

namespace App\Modules\Comunicacao\UI\Livewire\Template;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;
use App\Modules\Comunicacao\Services\EmailParserService;
use App\Modules\Teste\Suri\Services\SuriApiService;
use App\Helpers\BreadcrumbHelper;
use App\Traits\WithEditingLock;
use Illuminate\Support\Facades\Schema;

class TemplateForm extends Component
{
    use WithEditingLock;
    use WithFileUploads;

    public $templateId = null;
    public string $nome = '';
    public string $canal = 'email'; // 'email', 'whatsapp', 'ambos'
    public string $assunto = '';
    public string $corpo = '';

    // Configurações avançadas de Template WhatsApp / Suri
    public string $suriCategory = 'MARKETING'; // 'MARKETING' ou 'UTILITY'
    public string $suriFooter = '';
    public string $suriHeaderMode = 'none'; // 'none', 'image', 'document'
    public string $sampleFileUrl = '';
    public string $sampleFileName = '';
    public string $sampleFileBase64 = '';
    public $samplePdfUpload = null;
    public array $bodyExamples = [];

    /**
     * Estrutura de cada botão:
     * [
     *   'id'           => 'btn_...',
     *   'suri_type'    => 2, // 0 = URL, 1 = Ligar, 2 = Resposta Rápida, 3 = Copiar Código
     *   'texto'        => 'Confirmar',
     *   'url'          => 'https://suri.ai/planos/{{1}}',
     *   'phone'        => '+5585991976502',
     *   'example'      => 'premium-anual',
     *   'exibir_email' => true,
     *   'cor'          => 'purpura'
     * ]
     */
    public array $botoes = [];

    public string $abaPreview = 'email'; // 'email', 'whatsapp' ou 'json_suri'
    public string $buscaVariavel = '';

    public array $breadcrumbs = [];

    public function mount($id = null)
    {
        if ($id) {
            $template = EmailTemplate::findOrFail($id);
            $this->templateId = $template->id;
            $this->nome = (string) $template->nome;
            $this->canal = $template->canal ?? 'email';
            $this->assunto = (string) ($template->assunto ?? '');
            $this->corpo = (string) ($template->corpo ?? '');
            $this->botoes = is_array($template->botoes)
                ? $template->botoes
                : (json_decode($template->botoes ?? '[]', true) ?? []);

            $cfgSuri = is_array($template->configuracoes_suri)
                ? $template->configuracoes_suri
                : (json_decode($template->configuracoes_suri ?? '{}', true) ?? []);

            $this->suriCategory     = $cfgSuri['category'] ?? 'MARKETING';
            $this->suriFooter       = (string) ($cfgSuri['footer'] ?? '');
            $this->suriHeaderMode   = $cfgSuri['header_mode'] ?? 'none';
            $this->sampleFileUrl    = (string) ($cfgSuri['sample_file_url'] ?? '');
            $this->sampleFileName   = (string) ($cfgSuri['sample_file_name'] ?? '');
            $this->sampleFileBase64 = (string) ($cfgSuri['sample_file_base64'] ?? '');
            $this->bodyExamples     = is_array($cfgSuri['body_examples'] ?? null) ? $cfgSuri['body_examples'] : [];

            $this->resourceLockType = 'template_comunicacao';
            $this->resourceLockId = (int) $this->templateId;
            $this->checkAndAcquireLock();
        }

        $this->sincronizarVariaveisCorpo();
        $this->abaPreview = ($this->canal === 'whatsapp') ? 'whatsapp' : 'email';
        $this->breadcrumbs = BreadcrumbHelper::generate();
    }

    public function updatedCorpo(): void
    {
        $this->sincronizarVariaveisCorpo();
    }

    public function updatedSamplePdfUpload(): void
    {
        $this->validate([
            'samplePdfUpload' => 'file|mimes:pdf|max:10240',
        ], [
            'samplePdfUpload.mimes' => 'O cabeçalho de documento deve ser um arquivo PDF.',
        ]);

        if ($this->samplePdfUpload) {
            $conteudo = file_get_contents($this->samplePdfUpload->getRealPath());
            $this->sampleFileBase64 = 'data:application/pdf;base64,' . base64_encode($conteudo);
            if (empty($this->sampleFileName)) {
                $this->sampleFileName = $this->samplePdfUpload->getClientOriginalName();
            }
        }
    }

    public function sincronizarVariaveisCorpo(): void
    {
        $clean = strip_tags(html_entity_decode($this->corpo, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $clean, $matches);
        $vars = array_values(array_unique($matches[1] ?? []));

        $novosExemplos = [];
        foreach ($vars as $v) {
            $novosExemplos[$v] = $this->bodyExamples[$v] ?? match (true) {
                str_contains(strtolower($v), 'nome')  => 'Maria',
                str_contains(strtolower($v), 'valor') => 'R$ 249,90',
                str_contains(strtolower($v), 'data')  => '10/08/2026',
                default                               => strtoupper($v),
            };
        }
        $this->bodyExamples = $novosExemplos;
    }

    public function setCanal(string $novoCanal): void
    {
        if (!in_array($novoCanal, ['email', 'whatsapp', 'ambos'])) {
            return;
        }

        $this->canal = $novoCanal;

        if ($novoCanal === 'whatsapp') {
            $this->abaPreview = 'whatsapp';
        } elseif ($novoCanal === 'email') {
            $this->abaPreview = 'email';
        }
    }

    public function adicionarBotao(int $tipoSuri = 2): void
    {
        if (count($this->botoes) >= 10) {
            $this->dispatch('erro', msg: 'Limite máximo de 10 botões por template atingido.');
            return;
        }

        $this->botoes[] = [
            'id'           => 'btn_' . uniqid(),
            'suri_type'    => $tipoSuri, // 0=URL, 1=Ligar, 2=Resposta Rápida, 3=Copiar Código
            'texto'        => '',
            'url'          => '',
            'phone'        => '',
            'example'      => '',
            'exibir_email' => true,
            'cor'          => 'purpura',
        ];
    }

    public function removerBotao(int $index): void
    {
        unset($this->botoes[$index]);
        $this->botoes = array_values($this->botoes);
    }

    public function getPayloadSuriPreviewProperty(): array
    {
        $temp = new EmailTemplate([
            'nome'               => $this->nome ?: 'Titulo do template',
            'assunto'            => $this->assunto ?: 'TESTE',
            'corpo'              => $this->corpo ?: 'Olá {{1}}, mensagem de teste.',
            'canal'              => $this->canal,
            'botoes'             => $this->botoes,
            'configuracoes_suri' => [
                'category'           => $this->suriCategory,
                'footer'             => $this->suriFooter,
                'header_mode'        => $this->suriHeaderMode,
                'sample_file_url'    => $this->sampleFileUrl,
                'sample_file_name'   => $this->sampleFileName,
                'sample_file_base64' => $this->sampleFileBase64
                    ? substr($this->sampleFileBase64, 0, 48) . '...'
                    : '',
                'body_examples'      => $this->bodyExamples,
            ],
        ]);

        return app(SuriApiService::class)->montarPayloadTemplate($temp);
    }

    public function salvar()
    {
        if ($this->isLockedByOther) {
            $this->dispatch('erro', msg: 'Este template está sendo editado por outro usuário.');
            return;
        }

        $regras = [
            'nome'  => 'required|min:3|max:255',
            'canal' => 'required|in:email,whatsapp,ambos',
            'corpo' => 'required',
        ];

        $mensagens = [
            'nome.required'    => 'Informe o título/nome do template.',
            'assunto.required' => 'O assunto é obrigatório para templates de E-mail.',
            'corpo.required'   => 'O conteúdo da mensagem não pode ficar vazio.',
        ];

        if (in_array($this->canal, ['email', 'ambos'])) {
            $regras['assunto'] = 'required|min:3|max:255';
        } else {
            $regras['assunto'] = 'nullable|max:255';
        }

        if (in_array($this->canal, ['whatsapp', 'ambos'])) {
            if ($this->suriHeaderMode === 'image') {
                $regras['sampleFileUrl']  = 'required|url';
                $regras['sampleFileName'] = 'required|string|max:120';
                $mensagens['sampleFileUrl.required']  = 'Informe a URL pública da imagem de exemplo do cabeçalho.';
                $mensagens['sampleFileName.required'] = 'Informe o nome do arquivo da imagem (ex: promo.png).';
            } elseif ($this->suriHeaderMode === 'document') {
                $regras['sampleFileBase64'] = 'required|string|starts_with:data:application/pdf;base64,';
                $regras['sampleFileName']   = 'required|string|max:120';
                $mensagens['sampleFileBase64.required'] = 'Faça o upload de um PDF ou cole o Base64 do documento de exemplo.';
                $mensagens['sampleFileName.required']   = 'Informe o nome do arquivo PDF (ex: fatura-exemplo.pdf).';
            }

            if (!empty($this->botoes)) {
                $qtdLigar  = 0;
                $qtdCopiar = 0;
                $qtdUrl    = 0;

                foreach ($this->botoes as $idx => $btn) {
                    $num = $idx + 1;
                    $tipoSuri = (int) ($btn['suri_type'] ?? 2);

                    $regras["botoes.{$idx}.texto"] = 'required|string|max:60';
                    $mensagens["botoes.{$idx}.texto.required"] = "Informe o texto do botão #{$num}.";

                    if ($tipoSuri === 0) {
                        $qtdUrl++;
                        $regras["botoes.{$idx}.url"] = [
                            'required',
                            'string',
                            function ($attr, $val, $fail) use ($btn, $num) {
                                // Se tiver variável na URL, só pode ficar no final ({{1}}) e example é obrigatório
                                if (str_contains($val, '{{')) {
                                    if (!preg_match('/\{\{1\}\}$/', trim($val))) {
                                        $fail("No botão #{$num}, a variável dinâmica só é permitida no final da URL (ex: https://site.com/rota/{{1}}).");
                                    }
                                    if (empty(trim((string) ($btn['example'] ?? '')))) {
                                        $fail("No botão #{$num} com URL dinâmica, o campo 'Exemplo do trecho final' é obrigatório.");
                                    }
                                }
                            },
                        ];
                    } elseif ($tipoSuri === 1) {
                        $qtdLigar++;
                        $regras["botoes.{$idx}.phone"] = ['required', 'regex:/^\+\d{10,15}$/'];
                        $mensagens["botoes.{$idx}.phone.required"] = "Informe o telefone com +DDI+DDD+Número no botão #{$num} (ex: +5585991976502).";
                        $mensagens["botoes.{$idx}.phone.regex"]    = "O telefone do botão #{$num} deve começar com + seguido de DDI, DDD e número (ex: +5585991976502).";
                    } elseif ($tipoSuri === 3) {
                        $qtdCopiar++;
                        $regras["botoes.{$idx}.example"] = 'required|string|max:60';
                        $mensagens["botoes.{$idx}.example.required"] = "Informe o código de exemplo obrigatório no botão de copiar código #{$num} (ex: SURI20).";
                    }
                }

                if ($qtdLigar > 1) {
                    $this->addError('botoes', 'A Meta permite no máximo 1 botão de LIGAR por template.');
                    return;
                }
                if ($qtdCopiar > 1) {
                    $this->addError('botoes', 'A Meta permite no máximo 1 botão de COPIAR CÓDIGO por template.');
                    return;
                }
                if ($qtdUrl > 2) {
                    $this->addError('botoes', 'A Meta permite no máximo 2 botões de URL por template.');
                    return;
                }
            }
        }

        $this->validate($regras, $mensagens);

        $configSuri = [
            'category'           => $this->suriCategory,
            'footer'             => trim($this->suriFooter),
            'header_mode'        => $this->suriHeaderMode,
            'sample_file_url'    => trim($this->sampleFileUrl),
            'sample_file_name'   => trim($this->sampleFileName),
            'sample_file_base64' => $this->sampleFileBase64,
            'body_examples'      => $this->bodyExamples,
        ];

        $dados = [
            'nome'    => $this->nome,
            'assunto' => $this->assunto ?: 'TESTE',
            'corpo'   => $this->corpo,
        ];

        if (Schema::hasColumn('email_templates', 'canal')) {
            $dados['canal'] = $this->canal;
        }
        if (Schema::hasColumn('email_templates', 'botoes')) {
            $dados['botoes'] = in_array($this->canal, ['whatsapp', 'ambos']) ? array_values($this->botoes) : [];
        }
        if (Schema::hasColumn('email_templates', 'configuracoes_suri')) {
            $dados['configuracoes_suri'] = $configSuri;
        }

        if ($this->templateId) {
            $template = EmailTemplate::findOrFail($this->templateId);
            $template->update($dados);
        } else {
            $template = EmailTemplate::create($dados);
        }

        if (in_array($this->canal, ['whatsapp', 'ambos']) && empty($template->suri_template_id)) {
            $suriService = app(SuriApiService::class);
            $resSync = $suriService->syncTemplateComDetalhes($template);

            if (!empty($resSync['suri_template_id'])) {
                $template->update(['suri_template_id' => $resSync['suri_template_id']]);
            } else {
                $erroDetalhe = data_get($resSync, 'resposta.error') ?? 'Verifique os logs do sistema.';
                $this->dispatch('erro', msg: "Template salvo localmente, mas falhou ao sincronizar com a Suri: {$erroDetalhe}");
                return;
            }
        }

        $this->dispatch('sucesso', msg: 'Template salvo e sincronizado com sucesso!');
        return redirect()->route('templates.index');
    }

    public function render()
    {
        $dicionarioCompleto = EmailParserService::getDicionarioDisponivel();

        if (!empty(trim($this->buscaVariavel))) {
            $termo = mb_strtolower(trim($this->buscaVariavel), 'UTF-8');
            $filtrado = [];
            foreach ($dicionarioCompleto as $categoria => $vars) {
                foreach ($vars as $chave => $desc) {
                    if (str_contains(mb_strtolower($chave, 'UTF-8'), $termo) || str_contains(mb_strtolower($desc, 'UTF-8'), $termo)) {
                        $filtrado[$categoria][$chave] = $desc;
                    }
                }
            }
            $dicionarioCompleto = $filtrado;
        }

        return view('livewire.comunicacao.template.template-form', [
            'dicionario' => $dicionarioCompleto,
        ])->layout('components.layouts.app', ['title' => $this->templateId ? 'Editar Template' : 'Novo Template']);
    }
}