<?php

namespace App\Modules\Comunicacao\UI\Livewire\Template;

use Livewire\Component;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;
use App\Modules\Comunicacao\Services\EmailParserService;
use App\Modules\Teste\Suri\Services\SuriApiService;
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
        

        if ($id) {
            abort_if(!feature('template.editar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('template.editar'), 403);

            $template = EmailTemplate::findOrFail($id);
            $this->templateId = $template->id;
            $this->nome = $template->nome;
            $this->assunto = $template->assunto;
            $this->corpo = $template->corpo;
        } else {
            abort_if(!feature('template.criar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('template.criar'), 403);
        }

        $this->breadcrumbs = BreadcrumbHelper::generate();
    }

    public function salvar(SuriApiService $suriService) // Injete o serviço aqui
    {
        if ($this->templateId) {
            abort_if(!feature('template.editar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('template.editar'), 403);
        } else {
            abort_if(!feature('template.criar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('template.criar'), 403);
        }
        
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

        // Salva ou atualiza no banco local
        if ($this->templateId) {
            $template = EmailTemplate::findOrFail($this->templateId);
            $template->update($dados);
        } else {
            $template = EmailTemplate::create($dados);
        }

        // Sincroniza com a Suri se ainda não possuir ID
        if (empty($template->suri_template_id)) {
            $suriId = $suriService->syncTemplate($template);
            
            if ($suriId) {
                $template->update(['suri_template_id' => $suriId]);
            } else {
                $this->dispatch('erro', msg: 'Template salvo localmente, mas falhou ao sincronizar com a Suri.');
                return;
            }
        }

        $this->dispatch('sucesso', msg: 'Template salvo e sincronizado com sucesso!');
        return redirect()->route('templates.index');
    }

    public function render()
    {
        return view('livewire.comunicacao.template.template-form', [
            'dicionario' => EmailParserService::getDicionarioDisponivel()
        ])->layout('components.layouts.app', ['title' => $this->templateId ? 'Editar Template' : 'Novo Template']);
    }
}