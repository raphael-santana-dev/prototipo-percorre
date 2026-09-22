<?php

namespace App\Modules\Conteudo\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Conteudo;
use App\Models\ConteudoCategoria;
use Illuminate\Support\Str;
use App\Helpers\BreadcrumbHelper;

#[Layout('components.layouts.app')]
#[Title('Formulário de Conteúdo - Administrativo')]
class ConteudoForm extends Component
{
    public $conteudoId = null;
    public $titulo = '';
    public $categoria_id = '';
    public $tipo = 'padrao';
    public $corpo = '';
    
    public $is_active = true;
    public $data_inicio = null;
    public $data_fim = null;
    
    // Público-Alvo Padrão
    public $publico_alvo = ['geral'];
    
    public $is_destaque = false;
    public $ordem_destaque = null;

    public array $breadcrumbs = [];

    public function mount($id = null)
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403, 'Acesso Restrito');

        if ($id) {
            $conteudo = Conteudo::findOrFail($id);
            $this->conteudoId = $conteudo->id;
            $this->titulo = $conteudo->titulo;
            $this->categoria_id = $conteudo->categoria_id;
            $this->tipo = $conteudo->tipo;
            $this->corpo = $conteudo->corpo;
            $this->is_active = $conteudo->is_active;
            $this->data_inicio = $conteudo->data_inicio ? $conteudo->data_inicio->format('Y-m-d\TH:i') : null;
            $this->data_fim = $conteudo->data_fim ? $conteudo->data_fim->format('Y-m-d\TH:i') : null;
            $this->publico_alvo = $conteudo->publico_alvo ?? ['geral'];
            $this->is_destaque = $conteudo->is_destaque;
            $this->ordem_destaque = $conteudo->ordem_destaque;
        }

        $this->breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Gestão de Conteúdo', 'url' => route('conteudo.index')],
            ['label' => $this->conteudoId ? 'Editar Publicação' : 'Nova Publicação', 'url' => '#'],
        ];
    }

    // MÁGICA DO PÚBLICO-ALVO: Se "Geral" for marcado, limpa os outros. Se outro for marcado, tira o "Geral".
    public function updatedPublicoAlvo($value)
    {
        if (in_array('geral', $this->publico_alvo) && end($this->publico_alvo) === 'geral') {
            $this->publico_alvo = ['geral'];
        } elseif (in_array('geral', $this->publico_alvo) && count($this->publico_alvo) > 1) {
            $this->publico_alvo = array_diff($this->publico_alvo, ['geral']);
        }
    }

    public function salvar()
    {
        $this->validate([
            'titulo' => 'required|string|max:255',
            'categoria_id' => 'required|exists:conteudo_categorias,id',
            'tipo' => 'required|in:padrao,carrossel,story',
            'publico_alvo' => 'required|array|min:1',
            'data_inicio' => 'nullable|date',
            'data_fim' => 'nullable|date|after_or_equal:data_inicio',
        ], [
            'data_fim.after_or_equal' => 'A data de término não pode ser anterior à data de início.',
            'publico_alvo.required' => 'Selecione pelo menos um público-alvo para esta publicação.',
        ]);

        $dados = [
            'titulo' => $this->titulo,
            'categoria_id' => $this->categoria_id,
            'tipo' => $this->tipo,
            'corpo' => $this->corpo,
            'is_active' => $this->is_active,
            'data_inicio' => $this->data_inicio ?: null,
            'data_fim' => $this->data_fim ?: null,
            'publico_alvo' => $this->publico_alvo,
            'is_destaque' => $this->is_destaque,
            'ordem_destaque' => $this->is_destaque ? $this->ordem_destaque : null,
        ];

        if ($this->conteudoId) {
            Conteudo::findOrFail($this->conteudoId)->update($dados);
        } else {
            // Em nova publicação, atribui autor e gera slug único para a URL pública
            $dados['autor_id'] = auth()->id();
            $dados['slug'] = Str::slug($this->titulo) . '-' . uniqid();
            Conteudo::create($dados);
        }

        $this->dispatch('sucesso', msg: 'Conteúdo salvo com sucesso!');
        return redirect()->route('conteudo.index');
    }

    public function render()
    {
        return view('livewire.conteudo.conteudo-form', [
            'categoriasDb' => ConteudoCategoria::where('is_active', true)->orderBy('nome')->get()
        ]);
    }
}