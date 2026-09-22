<div class="p-6 max-w-[1400px] mx-auto font-sans relative">
    
    <!-- Quill CSS & JS (Importação Padrão do Projeto) -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

    <style>
        .ql-toolbar.ql-snow { border-top-left-radius: 0.5rem; border-top-right-radius: 0.5rem; border-color: #e5e7eb; background-color: #f9fafb; font-family: inherit; }
        .ql-container.ql-snow { border-bottom-left-radius: 0.5rem; border-bottom-right-radius: 0.5rem; border-color: #e5e7eb; min-height: 400px; font-family: inherit; font-size: 0.875rem; }
        .ql-editor { min-height: 400px; }
        .ql-editor:focus { box-shadow: 0 0 0 2px rgba(147, 51, 234, 0.25); border-color: #9333ea; outline: none; border-bottom-left-radius: 0.5rem; border-bottom-right-radius: 0.5rem; }
    </style>

    <x-page-header title="{{ $conteudoId ? 'Editar' : 'Nova' }} Publicação" icon="ph ph-newspaper" badge="Editorial" :breadcrumbs="$breadcrumbs">
        <x-slot name="actions">
            <a href="{{ route('conteudo.index') }}" class="px-4 py-2 text-sm font-bold border rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 flex items-center gap-2">
                <i class="ph-bold ph-arrow-left"></i> Voltar
            </a>
        </x-slot>
    </x-page-header>

    <form wire:submit.prevent="salvar" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start mt-6">
        
        <!-- COLUNA PRINCIPAL: Título e Editor -->
        <div class="lg:col-span-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-gray-800 dark:text-gray-200 mb-1">Título da Publicação <span class="text-red-500">*</span></label>
                        <input wire:model="titulo" type="text" placeholder="Ex: Instituto Percorre lança novo ciclo de aprendizagem..." class="w-full rounded-md border-gray-300 px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500 shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('titulo') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                    </div>
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
                    @error('corpo') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <!-- COLUNA LATERAL: Configurações, Status, Categoria, Público -->
        <div class="lg:col-span-4 space-y-6">
            
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 space-y-5">
                <h3 class="font-bold text-gray-900 dark:text-white text-sm border-b border-gray-100 dark:border-gray-700 pb-2">Configurações Gerais</h3>
                
                <div>
                    <label class="block text-[11px] uppercase font-bold text-gray-500 dark:text-gray-400 mb-1">Status de Visibilidade</label>
                    <div class="flex items-center gap-3">
                        <x-toggle :status="$is_active" action="$toggle('is_active')" />
                        <span class="text-sm font-bold {{ $is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-500' }}">
                            {{ $is_active ? 'Publicação Ativa' : 'Publicação Inativa' }}
                        </span>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] uppercase font-bold text-gray-500 dark:text-gray-400 mb-1">Categoria <span class="text-red-500">*</span></label>
                    <select wire:model="categoria_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">Selecione...</option>
                        @foreach($categoriasDb as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->nome }}</option>
                        @endforeach
                    </select>
                    @error('categoria_id') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-[11px] uppercase font-bold text-gray-500 dark:text-gray-400 mb-1">Formato da Publicação</label>
                    <select wire:model="tipo" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="padrao">Padrão (Notícia Clássica)</option>
                        <option value="carrossel">Galeria Carrossel</option>
                        <option value="story">Visual Story (Mobile-first)</option>
                    </select>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 space-y-5">
                <h3 class="font-bold text-gray-900 dark:text-white text-sm border-b border-gray-100 dark:border-gray-700 pb-2 flex justify-between items-center">
                    Agendamento
                    <i class="ph-bold ph-calendar-blank text-gray-400"></i>
                </h3>
                
                <div>
                    <label class="block text-[11px] uppercase font-bold text-gray-500 dark:text-gray-400 mb-1">Início da Publicação</label>
                    <input wire:model="data_inicio" type="datetime-local" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-[11px] uppercase font-bold text-gray-500 dark:text-gray-400 mb-1">Fim da Publicação</label>
                    <input wire:model="data_fim" type="datetime-local" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    @error('data_fim') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                </div>
                <p class="text-[10px] text-gray-500 italic">Deixe vazio para exibição imediata e sem validade.</p>
            </div>

            <div class="bg-blue-50 dark:bg-blue-900/10 p-6 rounded-xl shadow-sm border border-blue-100 dark:border-blue-900/30 space-y-4">
                <h3 class="font-bold text-blue-900 dark:text-blue-300 text-sm border-b border-blue-200 dark:border-blue-800 pb-2">
                    <i class="ph-fill ph-users-three text-blue-500"></i> Restrição de Público-Alvo
                </h3>
                
                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-2 rounded hover:bg-blue-100/50 dark:hover:bg-blue-900/30 cursor-pointer transition">
                        <input wire:model.live="publico_alvo" type="checkbox" value="geral" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300">
                        <span class="text-sm font-bold text-blue-900 dark:text-blue-200">Geral (Acesso Público Livre)</span>
                    </label>
                    
                    <div class="pl-4 space-y-2 border-l-2 border-blue-200 dark:border-blue-800 ml-2 mt-2 {{ in_array('geral', $publico_alvo) ? 'opacity-50 pointer-events-none' : '' }}">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input wire:model.live="publico_alvo" type="checkbox" value="estudantes" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300">
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Apenas Estudantes logados</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input wire:model.live="publico_alvo" type="checkbox" value="empresas" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300">
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Apenas Gestores/Empresas logadas</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input wire:model.live="publico_alvo" type="checkbox" value="interno" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300">
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Apenas Colaboradores (Interno)</span>
                        </label>
                    </div>
                    @error('publico_alvo') <span class="text-xs text-red-500 font-bold block">{{ $message }}</span> @enderror
                </div>
            </div>
            
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('conteudo.index') }}" class="px-5 py-2.5 text-sm font-bold border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">Cancelar</a>
                <button type="submit" class="w-full md:w-auto px-6 py-2.5 text-sm font-bold text-white rounded-lg shadow-sm bg-purpura-600 hover:bg-purpura-700 transition flex items-center justify-center gap-2">
                    <i class="ph-bold ph-floppy-disk text-lg"></i> {{ $conteudoId ? 'Atualizar Publicação' : 'Salvar Publicação' }}
                </button>
            </div>
        </div>
    </form>
</div>