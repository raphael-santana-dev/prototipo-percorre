<?php

namespace App\Modules\Conteudo\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Conteudo;
use App\Models\ConteudoCategoria;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

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
    
    public $is_destaque = false;
    public $ordem_destaque = null;
    public $texto_overlay = '';
    public $texto_destaque_overlay = '';

    // Imagens Base64
    public $banner_interno_upload = null;
    public $banner_desktop_upload = null;
    public $banner_mobile_upload = null;
    public $banner_destaque_upload = null;

    // Caminhos Atuais
    public $banner_interno_path = null;
    public $banner_desktop_path = null;
    public $banner_mobile_path = null;
    public $banner_destaque_path = null;

    // ARRAY DINÂMICO PARA CARROSSEL E STORY
    public array $slides = [];

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
            
            $opcoes = is_string($conteudo->opcoes_visuais) ? json_decode($conteudo->opcoes_visuais, true) : ($conteudo->opcoes_visuais ?? []);
            $this->texto_destaque_overlay = $opcoes['texto_destaque_overlay'] ?? '';
            
            // Carrega os slides guardados
            $this->slides = $opcoes['slides'] ?? [];

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

    // ==== LÓGICA DO CONSTRUTOR DE SLIDES ====
    public function addSlide()
    {
        $this->slides[] = [
            'imagem_upload' => null,
            'imagem_path' => null,
            'texto' => '',
            'posicao_texto' => 'bottom' // Padrão: Rodapé para não tapar a imagem
        ];
    }

    public function removeSlide($index)
    {
        unset($this->slides[$index]);
        $this->slides = array_values($this->slides);
    }

    public function removerImagemSlide($index)
    {
        $this->slides[$index]['imagem_upload'] = null;
        $this->slides[$index]['imagem_path'] = null;
    }

    private function processarImagemBase64($base64String, $prefix)
    {
        if (!$base64String) return null;

        $image_parts = explode(";base64,", $base64String);
        if(count($image_parts) < 2) return null; // Prevenção de formato inválido
        
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
        // 1. MOTOR DE VALIDAÇÃO: Higienização de Público-Alvo Absoluto
        // Se "geral" estiver presente, ignora qualquer outra seleção recebida do frontend.
        if (in_array('geral', $this->publico_alvo)) {
            $this->publico_alvo = ['geral'];
        }

        // 2. MOTOR DE VALIDAÇÃO: Regras Base e Agendamento
        $this->validate([
            'titulo' => 'required|string|max:255',
            'categoria_id' => 'required|exists:conteudo_categorias,id',
            'tipo' => 'required|in:padrao,carrossel,story',
            'publico_alvo' => 'required|array|min:1',
            'data_inicio' => 'nullable|date',
            'data_fim' => 'nullable|date|after_or_equal:data_inicio',
            'ordem_destaque' => 'required_if:is_destaque,true|nullable|integer|min:1|max:5',
        ], [
            'data_fim.after_or_equal' => 'A data de término não pode ser anterior à data de início.',
            'publico_alvo.required' => 'Selecione pelo menos um público-alvo para esta publicação.',
            'ordem_destaque.required_if' => 'Ao marcar como destaque, escolher a posição é obrigatório.',
        ]);

        // 3. MOTOR DE VALIDAÇÃO: Limite Máximo de Destaques
        if ($this->is_destaque) {
            $totalDestaques = Conteudo::where('is_destaque', true)
                ->when($this->conteudoId, function($q) {
                    return $q->where('id', '!=', $this->conteudoId);
                })
                ->count();

            if ($totalDestaques >= 5) {
                $this->dispatch('erro', msg: 'Ação bloqueada: O sistema já atingiu o limite máximo de 5 publicações em destaque.');
                return; // Trava a execução
            }
            
            // Reordenação Inteligente: Remove o destaque de quem ocupava a mesma posição
            Conteudo::where('is_destaque', true)
                    ->where('ordem_destaque', $this->ordem_destaque)
                    ->when($this->conteudoId, fn($q) => $q->where('id', '!=', $this->conteudoId))
                    ->update(['is_destaque' => false, 'ordem_destaque' => null]);
        }

        // 4. MOTOR DE VALIDAÇÃO: Consistência de Formatos
        if (in_array($this->tipo, ['carrossel', 'story']) && empty($this->slides)) {
            $this->dispatch('erro', msg: 'Para formatos de Carrossel ou Story, é obrigatório adicionar pelo menos um item (slide) no construtor.');
            return; // Trava a execução
        }

        // Processa as imagens dos slides dinâmicos
        $slidesProcessados = [];
        foreach ($this->slides as $slide) {
            $path = $slide['imagem_path'] ?? null;
            if (!empty($slide['imagem_upload'])) {
                $path = $this->processarImagemBase64($slide['imagem_upload'], 'slide_' . $this->tipo);
            }
            
            $slidesProcessados[] = [
                'imagem_path' => $path,
                'texto' => $slide['texto'] ?? '',
                'posicao_texto' => $slide['posicao_texto'] ?? 'bottom',
            ];
        }

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
                'slides' => $slidesProcessados, 
            ],
        ];

        // Gravação dos Banners
        $dados['banner_interno'] = $this->banner_interno_upload ? $this->processarImagemBase64($this->banner_interno_upload, 'interno') : $this->banner_interno_path;
        $dados['banner_desktop'] = $this->banner_desktop_upload ? $this->processarImagemBase64($this->banner_desktop_upload, 'desktop') : $this->banner_desktop_path;
        $dados['banner_mobile'] = $this->banner_mobile_upload ? $this->processarImagemBase64($this->banner_mobile_upload, 'mobile') : $this->banner_mobile_path;
        $dados['banner_destaque'] = $this->banner_destaque_upload ? $this->processarImagemBase64($this->banner_destaque_upload, 'destaque') : $this->banner_destaque_path;

        // Persistência na Base de Dados
        if ($this->conteudoId) {
            Conteudo::findOrFail($this->conteudoId)->update($dados);
        } else {
            $dados['autor_id'] = auth()->id();
            $dados['slug'] = Str::slug($this->titulo) . '-' . uniqid();
            Conteudo::create($dados);
        }

        $this->dispatch('sucesso', msg: 'Conteúdo validado e guardado com sucesso!');
        return redirect()->route('conteudo.index');
    }

    public function render()
    {
        return view('livewire.conteudo.conteudo-form', [
            'categoriasDb' => ConteudoCategoria::where('is_active', true)->orderBy('nome')->get()
        ]);
    }
}