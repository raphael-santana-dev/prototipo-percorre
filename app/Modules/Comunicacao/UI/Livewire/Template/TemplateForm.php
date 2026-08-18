<?php

namespace App\Modules\Comunicacao\UI\Livewire\Template;

use Livewire\Component;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;
use App\Modules\Comunicacao\Services\EmailParserService;
use App\Helpers\BreadcrumbHelper;

class TemplateForm extends Component
{
    public $templateId = null;
    public $nome = '';
    public $assunto = '';
    public $corpo = '';

    public array $breadcrumbs = [];

    public function mount($id = null)
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403);
        $this->breadcrumbs = BreadcrumbHelper::generate();

        if ($id) {
            $template = EmailTemplate::findOrFail($id);
            $this->templateId = $template->id;
            $this->nome = $template->nome;
            $this->assunto = $template->assunto;
            $this->corpo = $template->corpo;
        }
    }

    public function salvar()
    {
        $this->validate([
            'nome' => 'required|min:3|max:255',
            'assunto' => 'required|min:3|max:255',
            'corpo' => 'required',
        ]);

        $dados = [
            'nome' => $this->nome,
            'assunto' => $this->assunto,
            'corpo' => $this->corpo,
        ];

        // Se tiver ID, atualiza. Se não tiver, cria um novo (evitando o erro de NULL no PostgreSQL)
        if ($this->templateId) {
            EmailTemplate::findOrFail($this->templateId)->update($dados);
        } else {
            EmailTemplate::create($dados);
        }

        session()->flash('sucesso', 'Template salvo com sucesso!');
        return redirect()->route('templates.index');
    }

    public function render()
    {
        return view('livewire.comunicacao.template.template-form', [
            'dicionario' => EmailParserService::getDicionarioDisponivel()
        ])->layout('components.layouts.app', ['title' => $this->templateId ? 'Editar Template' : 'Novo Template']);
    }
}