<div class="p-6 max-w-[1400px] mx-auto font-sans relative" x-data="cropperModal()">
    
    <!-- Quill CSS & JS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

    <!-- Cropper CSS & JS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

    <style>
        .ql-toolbar.ql-snow { border-top-left-radius: 0.5rem; border-top-right-radius: 0.5rem; border-color: #e5e7eb; background-color: #f9fafb; font-family: inherit; }
        .ql-container.ql-snow { border-bottom-left-radius: 0.5rem; border-bottom-right-radius: 0.5rem; border-color: #e5e7eb; min-height: 400px; font-family: inherit; font-size: 0.875rem; }
        .ql-editor { min-height: 400px; }
        .ql-editor:focus { box-shadow: 0 0 0 2px rgba(147, 51, 234, 0.25); border-color: #9333ea; outline: none; }
        .cropper-view-box, .cropper-face { border-radius: 4px; }
    </style>

    <x-page-header title="{{ $conteudoId ? 'Editar' : 'Nova' }} Publicação" icon="ph ph-newspaper" badge="Editorial" :breadcrumbs="$breadcrumbs">
        <x-slot name="actions">
            <a href="{{ route('conteudo.index') }}" class="px-4 py-2 text-sm font-bold border rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 flex items-center gap-2">
                <i class="ph-bold ph-arrow-left"></i> Voltar
            </a>
        </x-slot>
    </x-page-header>

    <form wire:submit.prevent="salvar" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start mt-6">
        
        <!-- COLUNA PRINCIPAL: Título, Editor e Imagens -->
        <div class="lg:col-span-8 space-y-6">
            
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 space-y-6">
                <div>
                    <label class="block text-sm font-bold text-gray-800 dark:text-gray-200 mb-1">Título da Publicação <span class="text-red-500">*</span></label>
                    <input wire:model="titulo" type="text" class="w-full rounded-md border-gray-300 px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500 shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    @error('titulo') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="w-full">
                    <label class="block text-sm font-bold text-gray-800 dark:text-gray-200 mb-1">Corpo da Mensagem</label>
                    <div class="mt-2 bg-white rounded-md shadow-sm border border-gray-300 dark:border-gray-700" wire:ignore x-data="{
                        conteudo: @entangle('corpo'),
                        init() {
                            let quill = new Quill(this.$refs.quillEditor, {
                                theme: 'snow',
                                modules: {
                                    toolbar: [
                                        [{ 'header': [1, 2, 3, false] }],
                                        ['bold', 'italic', 'underline', 'strike'],
                                        [{ 'color': [] }, { 'background': [] }],
                                        [{ 'align': [] }],
                                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                        ['link', 'image', 'video'],
                                        ['clean']
                                    ]
                                }
                            });
                            quill.clipboard.dangerouslyPasteHTML(this.conteudo || '');
                            quill.on('text-change', () => { this.conteudo = quill.root.innerHTML; });
                        }
                    }">
                        <div x-ref="quillEditor" class="min-h-[400px] border-0 rounded-b-md text-base dark:bg-gray-800 dark:text-white"></div>
                    </div>

                    <!-- CONSTRUTOR DINÂMICO PARA CARROSSEL E STORY -->
              @if($tipo === 'carrossel' || $tipo === 'story')
                  <div class="bg-gray-50 dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 animate-fade-in-up">
                      <div class="flex justify-between items-center border-b border-gray-200 dark:border-gray-700 pb-4 mb-5">
                          <div>
                              <h3 class="font-bold text-gray-900 dark:text-white text-base flex items-center gap-2">
                                  <i class="ph-fill {{ $tipo === 'story' ? 'ph-instagram-logo' : 'ph-images' }} text-purpura-500"></i> 
                                  Construtor de {{ $tipo === 'story' ? 'Stories' : 'Galeria (Carrossel)' }}
                              </h3>
                              <p class="text-[10px] text-gray-500 mt-1">
                                  O recorte forçará o formato {{ $tipo === 'story' ? 'Vertical (9:16)' : 'Horizontal (16:9)' }}.
                              </p>
                          </div>
                          <button type="button" wire:click="addSlide" class="px-3 py-1.5 bg-purpura-100 text-purpura-700 rounded-lg text-xs font-bold hover:bg-purpura-200 transition shadow-sm flex items-center gap-1">
                              <i class="ph-bold ph-plus"></i> Novo Item
                          </button>
                      </div>

                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                          @foreach($slides as $index => $slide)
                              <!-- CORREÇÃO: wire:key evita a falha silenciosa do DOM no Livewire 3 -->
                              <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4 relative group shadow-sm" wire:key="slide-{{ $index }}">
                                  <button type="button" wire:click="removeSlide({{ $index }})" class="absolute top-2 right-2 bg-red-100 text-red-600 hover:bg-red-500 hover:text-white p-1 rounded transition opacity-0 group-hover:opacity-100 z-10">
                                      <i class="ph-bold ph-x"></i>
                                  </button>
                                  
                                  <span class="absolute top-2 left-2 bg-gray-900 text-white text-[9px] font-bold px-1.5 py-0.5 rounded z-10">#{{ $index + 1 }}</span>

                                  <!-- Imagem do Slide -->
                                  <div class="mt-4">
                                      @if(!empty($slide['imagem_upload']) || !empty($slide['imagem_path']))
                                          <div class="relative rounded overflow-hidden border border-gray-200 {{ $tipo === 'story' ? 'w-32 mx-auto h-48' : 'w-full h-32' }}">
                                              <img src="{{ $slide['imagem_upload'] ?? Storage::url($slide['imagem_path']) }}" class="w-full h-full object-cover">
                                              <button type="button" wire:click="removerImagemSlide({{ $index }})" class="absolute top-1 right-1 bg-red-500 text-white p-1 rounded-md shadow hover:bg-red-600 transition"><i class="ph-bold ph-trash text-xs"></i></button>
                                          </div>
                                      @else
                                          <label class="flex flex-col items-center justify-center border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition {{ $tipo === 'story' ? 'w-32 mx-auto h-48' : 'w-full h-32' }}">
                                              <i class="ph ph-upload-simple text-2xl text-gray-400"></i>
                                              <span class="text-[9px] font-bold mt-1 text-gray-500 uppercase">Fazer Crop</span>
                                              <input type="file" accept="image/*" class="hidden" @change="openCropper($event, 'slides.{{ $index }}.imagem_upload', {{ $tipo === 'story' ? 9/16 : 16/9 }})">
                                          </label>
                                      @endif
                                  </div>

                                  <!-- Configurações de Overlay do Slide (AGORA COM QUILL BÁSICO) -->
                                  <div class="mt-4 space-y-3">
                                      <div>
                                          <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Texto Overlay (Legenda Rica)</label>
                                          <div class="bg-white rounded-md border border-gray-300 dark:border-gray-700" wire:ignore x-data="{
                                              slideTexto: @entangle('slides.' . $index . '.texto'),
                                              init() {
                                                  let quill = new Quill(this.$refs.quillSlide, {
                                                      theme: 'snow',
                                                      modules: {
                                                          toolbar: [
                                                              ['bold', 'italic', 'underline', 'strike'],
                                                              [{ 'color': [] }, { 'background': [] }],
                                                              [{ 'size': ['small', false, 'large'] }],
                                                              ['link'],
                                                              ['clean']
                                                          ]
                                                      }
                                                  });
                                                  quill.clipboard.dangerouslyPasteHTML(this.slideTexto || '');
                                                  quill.on('text-change', () => { this.slideTexto = quill.root.innerHTML; });
                                              }
                                          }">
                                              <div x-ref="quillSlide" class="min-h-[100px] border-0 rounded-b-md text-sm dark:bg-gray-800 dark:text-white"></div>
                                          </div>
                                      </div>
                                      
                                      @if($tipo === 'story')
                                          <div>
                                              <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Ajuste Vertical do Texto</label>
                                              <select wire:model="slides.{{ $index }}.posicao_texto" class="w-full text-xs rounded border-gray-300 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                                  <option value="bottom">Rodapé (Inferior) - Ideal para visualização</option>
                                                  <option value="center">Centro</option>
                                                  <option value="top">Topo (Superior)</option>
                                              </select>
                                          </div>
                                      @endif
                                  </div>
                              </div>
                          @endforeach
                          
                          @if(empty($slides))
                              <div class="col-span-1 md:col-span-2 py-8 text-center border-2 border-dashed border-gray-300 rounded-xl">
                                  <i class="ph-fill ph-cards text-4xl text-gray-300 mb-2"></i>
                                  <p class="text-sm font-bold text-gray-500">Nenhum slide adicionado.</p>
                                  <p class="text-xs text-gray-400">Clique em "Novo Item" para construir a sequência.</p>
                              </div>
                          @endif
                      </div>
                  </div>
              @endif
                </div>

                
            </div>

            <!-- MÓDULO DE IMAGENS & BANNERS -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 space-y-6">
                <h3 class="font-bold text-gray-900 dark:text-white text-base border-b border-gray-100 dark:border-gray-700 pb-3 flex items-center gap-2">
                    <i class="ph-fill ph-image text-purpura-500"></i> Banners e Recortes (Cropper)
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Banner Interno (16:9) -->
                    <div>
                        <label class="block text-[11px] uppercase font-bold text-gray-500 dark:text-gray-400 mb-1">Banner Interno (Artigo) <br><span class="font-medium">Formato 16:9</span></label>
                        
                        @if($banner_interno_upload || $banner_interno_path)
                            <div class="relative rounded-lg overflow-hidden border border-gray-200 mt-2">
                                <img src="{{ $banner_interno_upload ?? Storage::url($banner_interno_path) }}" class="w-full h-40 object-cover">
                                <button type="button" wire:click="removerImagem('banner_interno')" class="absolute top-2 right-2 bg-red-500 text-white p-1.5 rounded-lg shadow hover:bg-red-600 transition"><i class="ph-bold ph-trash"></i></button>
                            </div>
                        @else
                            <label class="mt-2 flex flex-col items-center justify-center w-full h-40 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <i class="ph ph-upload-simple text-3xl text-gray-400"></i>
                                <span class="text-xs font-bold mt-2 text-gray-500">Clique para Recortar</span>
                                <input type="file" accept="image/*" class="hidden" @change="openCropper($event, 'banner_interno_upload', 16/9)">
                            </label>
                        @endif

                        <div class="mt-3">
                            <label class="block text-[10px] uppercase font-bold text-gray-400 mb-1">Texto Overlay (Sobre a Imagem)</label>
                            <input wire:model="texto_overlay" type="text" placeholder="Ex: Últimas Notícias..." class="w-full text-xs rounded-md border-gray-300 shadow-sm focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                    </div>

                    <!-- Banner Desktop Listagem (16:9) -->
                    <div>
                        <label class="block text-[11px] uppercase font-bold text-gray-500 dark:text-gray-400 mb-1">Banner Listagem (Desktop) <br><span class="font-medium">Formato 16:9</span></label>
                        @if($banner_desktop_upload || $banner_desktop_path)
                            <div class="relative rounded-lg overflow-hidden border border-gray-200 mt-2">
                                <img src="{{ $banner_desktop_upload ?? Storage::url($banner_desktop_path) }}" class="w-full h-40 object-cover">
                                <button type="button" wire:click="removerImagem('banner_desktop')" class="absolute top-2 right-2 bg-red-500 text-white p-1.5 rounded-lg shadow hover:bg-red-600 transition"><i class="ph-bold ph-trash"></i></button>
                            </div>
                        @else
                            <label class="mt-2 flex flex-col items-center justify-center w-full h-40 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <i class="ph ph-monitor text-3xl text-gray-400"></i>
                                <span class="text-xs font-bold mt-2 text-gray-500">Imagem Desktop</span>
                                <input type="file" accept="image/*" class="hidden" @change="openCropper($event, 'banner_desktop_upload', 16/9)">
                            </label>
                        @endif
                    </div>

                    <!-- Banner Mobile Listagem (4:5) -->
                    <div>
                        <label class="block text-[11px] uppercase font-bold text-gray-500 dark:text-gray-400 mb-1">Banner Listagem (Mobile) <br><span class="font-medium">Formato 4:5 (Vertical)</span></label>
                        @if($banner_mobile_upload || $banner_mobile_path)
                            <div class="relative rounded-lg overflow-hidden border border-gray-200 mt-2 w-3/4 mx-auto">
                                <img src="{{ $banner_mobile_upload ?? Storage::url($banner_mobile_path) }}" class="w-full h-48 object-cover">
                                <button type="button" wire:click="removerImagem('banner_mobile')" class="absolute top-2 right-2 bg-red-500 text-white p-1.5 rounded-lg shadow hover:bg-red-600 transition"><i class="ph-bold ph-trash"></i></button>
                            </div>
                        @else
                            <label class="mt-2 flex flex-col items-center justify-center w-3/4 mx-auto h-48 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <i class="ph ph-device-mobile text-3xl text-gray-400"></i>
                                <span class="text-xs font-bold mt-2 text-gray-500">Imagem Mobile</span>
                                <input type="file" accept="image/*" class="hidden" @change="openCropper($event, 'banner_mobile_upload', 4/5)">
                            </label>
                        @endif
                    </div>
                    
                </div>
            </div>

        </div>

        <!-- COLUNA LATERAL: Configurações, Status, Categoria, Público -->
        <div class="lg:col-span-4 space-y-6">
            
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 space-y-5">
                <h3 class="font-bold text-gray-900 dark:text-white text-sm border-b border-gray-100 dark:border-gray-700 pb-2">Configurações Gerais</h3>
                
                <div>
                    <div class="flex items-center gap-3">
                        <x-toggle :status="$is_active" action="$toggle('is_active')" />
                        <span class="text-sm font-bold {{ $is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-500' }}">
                            {{ $is_active ? 'Publicação Ativa' : 'Publicação Inativa' }}
                        </span>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] uppercase font-bold text-gray-500 dark:text-gray-400 mb-1">Categoria <span class="text-red-500">*</span></label>
                    <select wire:model="categoria_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">Selecione...</option>
                        @foreach($categoriasDb as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->nome }}</option>
                        @endforeach
                    </select>
                    @error('categoria_id') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-[11px] uppercase font-bold text-gray-500 dark:text-gray-400 mb-1">Formato da Publicação</label>
                    <select wire:model.live="tipo" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="padrao">Padrão (Notícia Clássica)</option>
                        <option value="carrossel">Galeria Carrossel</option>
                        <option value="story">Visual Story (Mobile-first)</option>
                    </select>
                </div>
            </div>

            <div class="bg-blue-50 dark:bg-blue-900/10 p-6 rounded-xl shadow-sm border border-blue-100 dark:border-blue-900/30 space-y-4">
                <h3 class="font-bold text-blue-900 dark:text-blue-300 text-sm border-b border-blue-200 dark:border-blue-800 pb-2">
                    <i class="ph-fill ph-users-three text-blue-500"></i> Público-Alvo
                </h3>
                
                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-2 rounded cursor-pointer transition">
                        <input wire:model.live="publico_alvo" type="checkbox" value="geral" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300">
                        <span class="text-sm font-bold text-blue-900 dark:text-blue-200">Geral (Acesso Público)</span>
                    </label>
                    
                    <div class="pl-4 space-y-2 border-l-2 border-blue-200 dark:border-blue-800 ml-2 mt-2 {{ in_array('geral', $publico_alvo) ? 'opacity-50 pointer-events-none' : '' }}">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input wire:model.live="publico_alvo" type="checkbox" value="estudantes" class="rounded text-blue-600 border-gray-300">
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Apenas Estudantes</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input wire:model.live="publico_alvo" type="checkbox" value="empresas" class="rounded text-blue-600 border-gray-300">
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Apenas Gestores/Empresas</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input wire:model.live="publico_alvo" type="checkbox" value="interno" class="rounded text-blue-600 border-gray-300">
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Apenas Interno</span>
                        </label>
                    </div>
                    @error('publico_alvo') <span class="text-xs text-red-500 font-bold block">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- BLOCO DE DESTAQUE NA HOME -->
            <div class="bg-yellow-50 dark:bg-yellow-900/10 p-6 rounded-xl shadow-sm border border-yellow-200 dark:border-yellow-900/30 space-y-4">
                <h3 class="font-bold text-yellow-900 dark:text-yellow-500 text-sm border-b border-yellow-200 dark:border-yellow-800 pb-2">
                    <i class="ph-fill ph-star text-yellow-500"></i> Destaque Principal (Home)
                </h3>
                
                <label class="flex items-center gap-3 cursor-pointer mb-2">
                    <x-toggle :status="$is_destaque" action="$toggle('is_destaque')" />
                    <span class="text-sm font-bold text-yellow-900 dark:text-yellow-400">Marcar como Destaque</span>
                </label>

                @if($is_destaque)
                    <div class="space-y-4 animate-fade-in-up">
                        <div>
                            <label class="block text-[11px] font-bold text-yellow-800 uppercase mb-1">Posição (Máximo 5)</label>
                            <select wire:model="ordem_destaque" class="w-full text-sm rounded border-yellow-300 shadow-sm focus:ring-yellow-500">
                                <option value="1">1º Destaque (Principal)</option>
                                <option value="2">2º Destaque</option>
                                <option value="3">3º Destaque</option>
                                <option value="4">4º Destaque</option>
                                <option value="5">5º Destaque</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-[11px] font-bold text-yellow-800 uppercase mb-1">Banner Destaque Especial</label>
                            @if($banner_destaque_upload || $banner_destaque_path)
                                <div class="relative rounded-lg overflow-hidden border border-yellow-200 mt-2">
                                    <img src="{{ $banner_destaque_upload ?? Storage::url($banner_destaque_path) }}" class="w-full h-32 object-cover">
                                    <button type="button" wire:click="removerImagem('banner_destaque')" class="absolute top-2 right-2 bg-red-500 text-white p-1 rounded-md shadow hover:bg-red-600 transition"><i class="ph-bold ph-trash"></i></button>
                                </div>
                            @else
                                <label class="mt-1 flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-yellow-300 rounded-lg cursor-pointer hover:bg-yellow-100 transition bg-white/50">
                                    <i class="ph ph-image text-2xl text-yellow-500"></i>
                                    <span class="text-[10px] font-bold mt-1 text-yellow-700">Fazer Crop (16:9)</span>
                                    <input type="file" accept="image/*" class="hidden" @change="openCropper($event, 'banner_destaque_upload', 16/9)">
                                </label>
                            @endif
                        </div>
                        
                        <div>
                            <label class="block text-[11px] font-bold text-yellow-800 uppercase mb-1">Título / Frase Destaque (Overlay)</label>
                            <input wire:model="texto_destaque_overlay" type="text" placeholder="Ex: Vagas Abertas!" class="w-full text-xs rounded border-yellow-300 shadow-sm focus:ring-yellow-500">
                        </div>
                    </div>
                @endif
            </div>

            <div class="flex flex-col gap-3 pt-4">
                <button type="submit" class="w-full px-6 py-3 text-sm font-bold text-white rounded-lg shadow-sm bg-purpura-600 hover:bg-purpura-700 transition flex items-center justify-center gap-2">
                    <i class="ph-bold ph-floppy-disk text-lg"></i> {{ $conteudoId ? 'Atualizar Publicação' : 'Publicar Conteúdo' }}
                </button>
            </div>
        </div>
    </form>

    <!-- MODAL GLOBAL DO CROPPER.JS VIA ALPINE.JS -->
    <div x-show="isCropping" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/90 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-4xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2"><i class="ph-fill ph-crop text-purpura-500"></i> Enquadrar Imagem</h3>
                <button @click="closeCropper" type="button" class="text-gray-400 hover:text-red-500 transition"><i class="ph-bold ph-x text-xl"></i></button>
            </div>
            
            <div class="p-4 bg-gray-100 dark:bg-gray-900 flex-1 min-h-[400px] flex items-center justify-center">
                <div class="w-full max-w-2xl max-h-[500px]">
                    <img x-ref="imageElement" class="max-w-full block">
                </div>
            </div>
            
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3 bg-white dark:bg-gray-800">
                <button @click="closeCropper" type="button" class="px-4 py-2 border rounded-lg text-sm font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">Cancelar</button>
                <button @click="cropAndSave" type="button" class="px-6 py-2 bg-purpura-600 hover:bg-purpura-700 text-white rounded-lg text-sm font-bold shadow-sm flex items-center gap-2 transition">
                    <i class="ph-bold ph-scissors"></i> Recortar e Aplicar
                </button>
            </div>
        </div>
    </div>

    <!-- SCRIPT ALPINE PARA O CROPPER -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('cropperModal', () => ({
                isCropping: false,
                cropperInstance: null,
                targetLivewireProperty: '',
                
                openCropper(event, propertyName, ratio) {
                    let file = event.target.files[0];
                    if (!file) return;

                    this.targetLivewireProperty = propertyName;
                    
                    let reader = new FileReader();
                    reader.onload = (e) => {
                        this.$refs.imageElement.src = e.target.result;
                        this.isCropping = true;
                        
                        // Inicializa o Cropper.js assim que o Modal abre
                        this.$nextTick(() => {
                            if (this.cropperInstance) {
                                this.cropperInstance.destroy();
                            }
                            this.cropperInstance = new Cropper(this.$refs.imageElement, {
                                aspectRatio: ratio,
                                viewMode: 1,
                                autoCropArea: 1,
                                responsive: true,
                            });
                        });
                    };
                    reader.readAsDataURL(file);
                    event.target.value = ''; // Reseta o input de arquivo
                },

                cropAndSave() {
                    if (this.cropperInstance) {
                        let canvas = this.cropperInstance.getCroppedCanvas({
                            width: 1000, // Reduzido de 1200 para 1000
                            imageSmoothingEnabled: true,
                            imageSmoothingQuality: 'high',
                        });
                        
                        // Reduzido para 0.8 de qualidade para não estourar o post_max_size do PHP
                        let base64Image = canvas.toDataURL('image/jpeg', 0.8);
                        
                        @this.set(this.targetLivewireProperty, base64Image);
                        this.closeCropper();
                    }
                },

                closeCropper() {
                    this.isCropping = false;
                    if (this.cropperInstance) {
                        this.cropperInstance.destroy();
                        this.cropperInstance = null;
                    }
                    this.$refs.imageElement.src = '';
                }
            }));
        });
    </script>

</div>