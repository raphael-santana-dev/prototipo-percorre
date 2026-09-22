<?php

namespace App\Modules\Conteudo\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Conteudo;
use App\Models\ConteudoCategoria;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
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
    public $publico_alvo = ['geral'];
    
    // Destaque e Overlays
    public $is_destaque = false;
    public $ordem_destaque = null;
    public $texto_overlay = '';
    public $texto_destaque_overlay = '';

    // Imagens (Base64 vindas do Cropper)
    public $banner_interno_upload = null;
    public $banner_desktop_upload = null;
    public $banner_mobile_upload = null;
    public $banner_destaque_upload = null;

    // Caminhos Atuais (Imagens já salvas)
    public $banner_interno_path = null;
    public $banner_desktop_path = null;
    public $banner_mobile_path = null;
    public $banner_destaque_path = null;

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
            $this->texto_overlay = $conteudo->texto_overlay;
            
            // Extracção do JSON de opções visuais
            $opcoes = is_string($conteudo->opcoes_visuais) ? json_decode($conteudo->opcoes_visuais, true) : ($conteudo->opcoes_visuais ?? []);
            $this->texto_destaque_overlay = $opcoes['texto_destaque_overlay'] ?? '';

            // Paths atuais
            $this->banner_interno_path = $conteudo->banner_interno;
            $this->banner_desktop_path = $conteudo->banner_desktop;
            $this->banner_mobile_path = $conteudo->banner_mobile;
            $this->banner_destaque_path = $conteudo->banner_destaque;
        }

        $this->breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Gestão de Conteúdo', 'url' => route('conteudo.index')],
            ['label' => $this->conteudoId ? 'Editar Publicação' : 'Nova Publicação', 'url' => '#'],
        ];
    }

    public function updatedPublicoAlvo($value)
    {
        if (in_array('geral', $this->publico_alvo) && end($this->publico_alvo) === 'geral') {
            $this->publico_alvo = ['geral'];
        } elseif (in_array('geral', $this->publico_alvo) && count($this->publico_alvo) > 1) {
            $this->publico_alvo = array_diff($this->publico_alvo, ['geral']);
        }
    }

    // Processador Genérico de Base64 para Imagens JPG/PNG
    private function processarImagemBase64($base64String, $prefix)
    {
        if (!$base64String) return null;

        $image_parts = explode(";base64,", $base64String);
        $image_base64 = base64_decode($image_parts[1]);
        $fileName = $prefix . '_' . uniqid() . '.jpg';
        $path = 'conteudos/' . $fileName;

        Storage::disk('public')->put($path, $image_base64);
        return $path;
    }

    public function removerImagem($tipo)
    {
        $propertyUpload = $tipo . '_upload';
        $propertyPath = $tipo . '_path';
        
        $this->$propertyUpload = null;
        $this->$propertyPath = null;
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
            'texto_overlay' => $this->texto_overlay,
            'opcoes_visuais' => [
                'texto_destaque_overlay' => $this->texto_destaque_overlay,
            ],
        ];

        // Processamento das Imagens (Se houver upload novo, descodifica. Se não, mantém o path atual)
        $dados['banner_interno'] = $this->banner_interno_upload 
            ? $this->processarImagemBase64($this->banner_interno_upload, 'interno') 
            : $this->banner_interno_path;

        $dados['banner_desktop'] = $this->banner_desktop_upload 
            ? $this->processarImagemBase64($this->banner_desktop_upload, 'desktop') 
            : $this->banner_desktop_path;

        $dados['banner_mobile'] = $this->banner_mobile_upload 
            ? $this->processarImagemBase64($this->banner_mobile_upload, 'mobile') 
            : $this->banner_mobile_path;

        $dados['banner_destaque'] = $this->banner_destaque_upload 
            ? $this->processarImagemBase64($this->banner_destaque_upload, 'destaque') 
            : $this->banner_destaque_path;

        if ($this->conteudoId) {
            Conteudo::findOrFail($this->conteudoId)->update($dados);
        } else {
            $dados['autor_id'] = auth()->id();
            $dados['slug'] = Str::slug($this->titulo) . '-' . uniqid();
            Conteudo::create($dados);
        }

        $this->dispatch('sucesso', msg: 'Conteúdo guardado com sucesso!');
        return redirect()->route('conteudo.index');
    }

    public function render()
    {
        return view('livewire.conteudo.conteudo-form', [
            'categoriasDb' => ConteudoCategoria::where('is_active', true)->orderBy('nome')->get()
        ]);
    }
}