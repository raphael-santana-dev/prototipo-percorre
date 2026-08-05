<div class="p-6 max-w-7xl mx-auto font-sans relative">
    @if (session()->has('sucesso'))
        <div class="flex items-center gap-2 p-4 mb-6 rounded-md text-pistache-100 bg-pistache-500 font-medium">
            <i class="ph ph-check-circle text-lg"></i> {{ session('sucesso') }}
        </div>
    @endif

    <x-breadcrumb :items="$breadcrumbs" />

    <div class="flex items-center justify-between mb-6">
        <h2 class="flex items-center gap-2 text-2xl font-bold text-gray-900 dark:text-white">
            <i class="ph ph-list-dashes text-purpura-500"></i> Gerenciamento de Formulários
        </h2>
        <button wire:click="abrirModal" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600 font-bold">
            <i class="ph ph-plus text-lg"></i> Novo Formulário
        </button>
    </div>

    <x-table
        :headers="$this->headers"
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid"
        :modoExibicao="$modoExibicao">

        @forelse ($registros as $form)
            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">#{{ $form->id }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="font-bold text-gray-900 dark:text-white">{{ $form->titulo }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($form->descricao, 50) }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <x-toggle :status="$form->status" action="toggleStatus({{ $form->id }})" />
                    <div class="text-[10px] mt-1 font-bold {{ $form->status ? 'text-green-600' : 'text-gray-500' }}">
                        {{ $form->status ? 'ATIVO' : 'INATIVO' }}
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('formularios.show', $form->id) }}" target="_blank" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600" title="Acessar Link">
                            <i class="text-xl ph ph-eye"></i>
                        </a>
                        <a href="{{ route('formularios.publico', $form->slug) }}" target="_blank" class="p-2 text-gray-400 transition-colors  rounded-lg hover:text-blue-500 dark:bg-blue-900/30 dark:hover:bg-blue-900/50" title="Ver Formulário Público">
                            <i class="text-xl ph ph-arrow-square-in"></i>
                        </a>
                        <a href="{{ route('construtor.campos', ['tipo' => 'formulario', 'id' => $form->id]) }}" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Construtor de Campos">
                            <i class="text-xl ph ph-list-dashes"></i>
                        </a>
                        <button wire:click="abrirModal({{ $form->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar Informações">
                            <i class="text-xl ph ph-pencil-simple"></i>
                        </button>
                        <button wire:click="excluir({{ $form->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir Formulário" onclick="confirm('Excluir este formulário permanentemente?') || event.stopImmediatePropagation()">
                            <i class="text-xl ph ph-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                    <p class="text-lg font-semibold">Nenhum formulário encontrado.</p>
                    <p class="text-sm">Crie seu primeiro formulário customizado agora mesmo.</p>
                </td>
            </tr>
        @endforelse

        <x-slot name="gridSlot">
            @foreach ( $registros as $form )
                <div class="flex flex-col p-4 bg-white border border-gray-100 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm font-bold text-gray-900 dark:text-white truncate pr-2">{{ $form->titulo }}</div>
                        <span class="px-2 py-1 text-[10px] font-bold text-gray-500 bg-gray-100 rounded border border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">#{{ $form->id }}</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-4 line-clamp-2 min-h-[32px]">
                        {{ $form->descricao ?: 'Sem descrição informada...' }}
                    </div>
                    <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-100 dark:border-gray-700">
                        <x-toggle :status="$form->status" action="toggleStatus({{ $form->id }})" />
                        <div class="flex items-center gap-1">
                            <a href="{{ route('formularios.show', $form->id) }}" target="_blank" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600" title="Acessar Link">
                                <i class="text-xl ph ph-eye"></i>
                            </a>
                            <a href="{{ route('formularios.publico', $form->slug) }}" target="_blank" class="p-2 text-gray-400 transition-colors hover:text-blue-500 rounded-lg dark:bg-blue-900/30 dark:hover:bg-blue-900/50" title="Ver Formulário Público">
                                <i class="text-xl ph ph-arrow-square-in"></i>
                            </a>
                            <a href="{{ route('construtor.campos', ['tipo' => 'formulario', 'id' => $form->id]) }}" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Construtor de Campos">
                                <i class="text-xl ph ph-list-dashes"></i>
                            </a>
                            <button wire:click="abrirModal({{ $form->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-blue-500 dark:hover:bg-gray-600" title="Editar Informações">
                                <i class="text-xl ph ph-pencil-simple"></i>
                            </button>
                            <button wire:click="excluir({{ $form->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir Formulário" onclick="confirm('Excluir este formulário permanentemente?') || event.stopImmediatePropagation()">
                                <i class="text-xl ph ph-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </x-slot>
    </x-table>

    <!-- Modal Padrão -->
    @if($modalAberto)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="$set('modalAberto', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-md sm:w-full sm:p-6 dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-bold text-gray-900 border-b border-gray-100 pb-2 dark:text-white dark:border-gray-700">
                        {{ $formId ? 'Editar Formulário' : 'Novo Formulário' }}
                    </h3>
                    
                    <form wire:submit.prevent="salvar" class="space-y-4">
                        <div>
                            <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Título do Formulário <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="titulo" placeholder="Ex: Pesquisa de Satisfação" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('titulo') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Descrição (Opcional)</label>
                            <textarea wire:model="descricao" rows="3" placeholder="Texto de introdução a pesquisa..." class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                            @error('descricao') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center pt-2">
                            <input type="checkbox" wire:model="status" id="status" class="w-5 h-5 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600">
                            <label for="status" class="block ml-2 text-sm font-bold text-gray-900 dark:text-gray-300">
                                Ativar imediatamente (Público)
                            </label>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 mt-6 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" wire:click="$set('modalAberto', false)" class="px-4 py-2 text-sm font-bold border rounded-lg text-purpura-500 border-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-700">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 text-sm font-bold text-white rounded-lg shadow-sm bg-ponkan-500 hover:bg-ponkan-600">
                                Salvar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    
    {{-- TOAST SYSTEM --}}
    <div x-data="{ show: false, msg: '' }" 
        @sucesso.window="show = true; msg = $event.detail.msg; setTimeout(() => show = false, 3500);"
        x-show="show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-10" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-10"
        class="fixed bottom-8 right-8 bg-green-600 text-white px-6 py-4 rounded-xl shadow-2xl z-[200] flex items-center gap-3 font-bold" x-cloak>
        <i class="text-2xl ph ph-check-circle text-white"></i>
        <span x-text="msg"></span>
    </div>
</div>