<?php

namespace App\Modules\Comunicacao\UI\Livewire\Template;

use Livewire\Component;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;
use App\Modules\Comunicacao\Services\EmailParserService;
use App\Helpers\BreadcrumbHelper;
use App\Traits\WithEditingLock;
use Illuminate\Support\Facades\Schema;

class TemplateForm extends Component
{
    use WithEditingLock;

    public $templateId = null;
    public string $nome = '';
    public string $canal = 'email'; // 'email', 'whatsapp', 'ambos'
    public string $assunto = '';
    public string $corpo = '';

    /**
     * Estrutura de cada botão:
     * [
     *   'id' => 'btn_...',
     *   'texto' => 'Confirmar Presença',
     *   'exibir_email' => true,
     *   'url' => '{{link_assinatura}}',
     *   'cor' => 'purpura' // 'purpura', 'emerald', 'blue', 'dark'
     * ]
     */
    public array $botoes = [];

    public string $abaPreview = 'email'; // 'email' ou 'whatsapp'
    public string $buscaVariavel = '';

    public array $breadcrumbs = [];

    public function mount($id = null)
    {
        if ($id) {
            abort_if(!feature('template.editar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('template.editar'), 403);

            $template = EmailTemplate::findOrFail($id);
            $this->templateId = $template->id;
            $this->nome = (string) $template->nome;
            $this->canal = $template->canal ?? 'email';
            $this->assunto = (string) ($template->assunto ?? '');
            $this->corpo = (string) ($template->corpo ?? '');
            $this->botoes = is_array($template->botoes) ? $template->botoes : (json_decode($template->botoes ?? '[]', true) ?? []);

            // Trava de edição simultânea (Solicitação 3)
            $this->resourceLockType = 'template_comunicacao';
            $this->resourceLockId = (int) $this->templateId;
            $this->checkAndAcquireLock();
        } else {
            abort_if(!feature('template.criar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('template.criar'), 403);
        }

        $this->abaPreview = ($this->canal === 'whatsapp') ? 'whatsapp' : 'email';
        $this->breadcrumbs = BreadcrumbHelper::generate();
    }

    public function setCanal(string $novoCanal)
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

    public function adicionarBotao()
    {
        $this->botoes[] = [
            'id'           => 'btn_' . uniqid(),
            'texto'        => '',
            'exibir_email' => true,
            'url'          => '',
            'cor'          => 'purpura',
        ];
    }

    public function removerBotao(int $index)
    {
        unset($this->botoes[$index]);
        $this->botoes = array_values($this->botoes);
    }

    public function salvar()
    {
        if ($this->templateId) {
            abort_if(!feature('template.editar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('template.editar'), 403);
        } else {
            abort_if(!feature('template.criar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('template.criar'), 403);
        }

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
            'nome.required'    => 'Informe o nome de identificação do template.',
            'assunto.required' => 'O assunto é obrigatório para templates de E-mail.',
            'corpo.required'   => 'O conteúdo da mensagem não pode ficar vazio.',
        ];

        if (in_array($this->canal, ['email', 'ambos'])) {
            $regras['assunto'] = 'required|min:3|max:255';
        } else {
            $regras['assunto'] = 'nullable|max:255';
        }

        // Validação dos botões (quando houver botões adicionados)
        if (in_array($this->canal, ['whatsapp', 'ambos']) && !empty($this->botoes)) {
            foreach ($this->botoes as $idx => $btn) {
                $regras["botoes.{$idx}.texto"] = 'required|string|max:60';
                $mensagens["botoes.{$idx}.texto.required"] = 'Informe o texto do botão #' . ($idx + 1) . '.';

                $exibirNoEmail = filter_var($btn['exibir_email'] ?? false, FILTER_VALIDATE_BOOLEAN);
                if ($this->canal === 'ambos' && $exibirNoEmail) {
                    $regras["botoes.{$idx}.url"] = 'required|string|max:500';
                    $mensagens["botoes.{$idx}.url.required"] = 'Defina a URL de destino para exibir o botão #' . ($idx + 1) . ' no E-mail.';
                }
            }
        }

        $this->validate($regras, $mensagens);

        $dados = [
            'nome'    => $this->nome,
            'assunto' => $this->assunto ?: $this->nome,
            'corpo'   => $this->corpo,
        ];

        if (Schema::hasColumn('email_templates', 'canal')) {
            $dados['canal'] = $this->canal;
        }

        if (Schema::hasColumn('email_templates', 'botoes')) {
            $dados['botoes'] = in_array($this->canal, ['whatsapp', 'ambos']) ? array_values($this->botoes) : [];
        }

        if ($this->templateId) {
            EmailTemplate::findOrFail($this->templateId)->update($dados);
        } else {
            EmailTemplate::create($dados);
        }

        $this->dispatch('sucesso', msg: 'Template salvo com sucesso!');
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
            'dicionario' => $dicionarioCompleto
        ])->layout('components.layouts.app', ['title' => $this->templateId ? 'Editar Template' : 'Novo Template']);
    }
}