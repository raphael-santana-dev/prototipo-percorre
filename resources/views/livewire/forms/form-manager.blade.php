<div class="p-6 max-w-7xl mx-auto font-sans relative">

    <x-page-header 
        title="Gerenciamento de Formulários" 
        icon="ph ph-list-dashes"
        badge="Formulários Gerais"
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">

        @if(feature('formulario.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('formulario.criar')))
            <x-slot name="actions">
                <button wire:click="abrirModal" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600 font-bold">
                    <i class="ph ph-plus text-lg"></i> Novo Formulário
                </button>
            </x-slot>
        @endif
    </x-page-header>

    <x-table
        :headers="$this->headers"
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid"
        :modoExibicao="$modoExibicao">

        @forelse ($registros as $form)
            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-4 py-2.5 whitespace-nowrap text-sm font-medium text-gray-500 dark:text-gray-400">
                    #{{ $form->id }}
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="font-bold text-gray-900 dark:text-white">{{ $form->titulo }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($form->descricao, 50) }}</div>
                </td>
                
                {{-- Coluna Nova: Regras de Acesso --}}
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="flex flex-col gap-1">
                        @if($form->acesso_livre)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-green-50 text-green-700 border border-green-200 uppercase w-max"><i class="ph-bold ph-globe"></i> Público</span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-red-50 text-red-700 border border-red-200 uppercase w-max"><i class="ph-bold ph-lock-key"></i> Restrito</span>
                        @endif
                        
                        @if($form->data_inicio || $form->data_fim)
                            <span class="text-[10px] text-gray-500 font-bold flex items-center gap-1">
                                <i class="ph-bold ph-calendar"></i>
                                {{ $form->data_inicio ? $form->data_inicio->format('d/m/y') : 'Sempre' }} até {{ $form->data_fim ? $form->data_fim->format('d/m/y') : 'Sempre' }}
                            </span>
                        @endif
                    </div>
                </td>

                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                        @if(feature('formulario.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('formulario.editar')))
                            <x-toggle :status="$form->status" action="toggleStatus({{ $form->id }})" />
                        @else
                            <span class="w-2 h-2 rounded-full {{ $form->status ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                        @endif
                        <span class="text-[10px] font-bold {{ $form->status ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $form->status ? 'ATIVO' : 'INATIVO' }}
                        </span>
                    </div>
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-1">
                        <a href="{{ route('formularios.respostas.show', $form->id) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-emerald-500 hover:bg-emerald-50 dark:hover:bg-gray-600" title="Ver Respostas Coletadas">
                            <i class="text-lg ph ph-database"></i>
                        </a>
                        <a href="{{ route('formularios.publico', ['id' => $form->id, 'slug' => $form->slug]) }}" target="_blank" class="p-1.5 text-gray-400 transition-colors rounded hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Acessar Formulário (Link)">
                            <i class="text-lg ph ph-arrow-square-in"></i>
                        </a>
                        @if(feature('formulario.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('formulario.editar')))
                            <a href="{{ route('construtor.campos', ['tipo' => 'formulario', 'id' => $form->id]) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Construtor de Blocos">
                                <i class="text-lg ph ph-list-dashes"></i>
                            </a>
                            <button wire:click="abrirModal({{ $form->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Configurações do Form">
                                <i class="text-lg ph ph-gear"></i>
                            </button>
                        @endif
                        
                        @if(feature('formulario.excluir') && (auth()->user()->hasRole('dev') || auth()->user()->can('formulario.excluir')))
                            <button wire:click="excluir({{ $form->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir Formulário" onclick="confirm('Atenção: Ao excluir o formulário, todas as respostas vinculadas a ele também serão deletadas. Deseja continuar?') || event.stopImmediatePropagation()">
                                <i class="text-lg ph ph-trash"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                    <p class="font-semibold">Nenhum formulário geral encontrado.</p>
                </td>
            </tr>
        @endforelse

        <x-slot name="gridSlot">
            {{-- Grid oculto por brevidade (Acompanha os mesmos botões da table) --}}
        </x-slot>
    </x-table>

    <!-- Modal Enriquecido -->
    @if($modalAberto)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="$set('modalAberto', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6 dark:bg-gray-800">
                    
                    <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-3">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="ph-fill ph-gear text-purpura-600"></i> {{ $formId ? 'Configurar Formulário' : 'Novo Formulário' }}
                        </h3>
                        <button wire:click="$set('modalAberto', false)" class="text-gray-400 hover:text-gray-600"><i class="ph-bold ph-x text-xl"></i></button>
                    </div>
                    
                    <form wire:submit.prevent="salvar" class="space-y-6">
                        
                        {{-- 1. Dados Básicos --}}
                        <div class="space-y-4">
                            <div>
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Título Interno do Formulário <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="titulo" placeholder="Ex: Pesquisa de Clima Organizacional" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:text-white">
                                @error('titulo') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Descrição Opcional</label>
                                <textarea wire:model="descricao" rows="2" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:text-white"></textarea>
                            </div>
                        </div>

                        {{-- 2. Restrições de Prazo (Trava de Tempo) --}}
                        <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3 flex items-center gap-2"><i class="ph-bold ph-calendar"></i> Período de Disponibilidade</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block mb-1 text-xs font-bold text-gray-700 dark:text-gray-300">Abre em (Opcional)</label>
                                    <input type="datetime-local" wire:model="data_inicio" class="w-full mt-1 border-gray-300 rounded-md shadow-sm text-sm focus:border-purpura-500 focus:ring-purpura-500">
                                </div>
                                <div>
                                    <label class="block mb-1 text-xs font-bold text-gray-700 dark:text-gray-300">Encerra em (Opcional)</label>
                                    <input type="datetime-local" wire:model="data_fim" class="w-full mt-1 border-gray-300 rounded-md shadow-sm text-sm focus:border-purpura-500 focus:ring-purpura-500">
                                </div>
                            </div>
                        </div>

                        {{-- 3. Travas de Acesso (Privacidade) --}}
                        <div class="bg-indigo-50 dark:bg-indigo-900/10 p-4 rounded-xl border border-indigo-100 dark:border-indigo-800" x-data="{ livre: @entangle('acesso_livre') }">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-800 dark:text-indigo-400 mb-3 flex items-center gap-2"><i class="ph-bold ph-shield-check"></i> Controle de Privacidade</h4>
                            
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" wire:model.live="acesso_livre" class="w-5 h-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                                <div>
                                    <span class="block text-sm font-bold text-gray-900 dark:text-white">Formulário Público (Livre)</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">Qualquer pessoa que acessar o link poderá responder de forma anônima.</span>
                                </div>
                            </label>

                            <div x-show="!livre" x-collapse class="mt-4 pt-4 border-t border-indigo-200/50 space-y-4" x-cloak>
                                
                                <label class="flex items-center gap-3 cursor-pointer p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200">
                                    <input type="checkbox" wire:model="apenas_estudantes" class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                                    <div>
                                        <span class="block text-sm font-bold text-gray-900 dark:text-white">Exigir Login de Estudantes</span>
                                        <span class="block text-xs text-gray-500">Apenas alunos matriculados e logados no Portal do Aluno poderão acessar e responder.</span>
                                    </div>
                                </label>

                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">Ou liberar para Funcionários e Professores (Roles):</label>
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                        @foreach($rolesDb as $role)
                                            <label class="flex items-center gap-2 p-2 bg-white dark:bg-gray-800 border border-gray-200 rounded cursor-pointer hover:bg-gray-50 transition">
                                                <input type="checkbox" wire:model="roles_permitidas" value="{{ $role->name }}" class="w-3.5 h-3.5 text-purpura-600 rounded border-gray-300">
                                                <span class="text-xs font-bold text-gray-700">{{ ucfirst($role->name) }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <p class="text-[10px] text-gray-500 mt-1">Se não marcar nenhum, todos os usuários internos terão acesso.</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center pt-2">
                            <input type="checkbox" wire:model="status" id="status" class="w-5 h-5 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500">
                            <label for="status" class="block ml-2 text-sm font-bold text-gray-900 dark:text-gray-300">
                                Ativar link do formulário (Status Geral)
                            </label>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 mt-6 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" wire:click="$set('modalAberto', false)" class="px-5 py-2.5 text-sm font-bold border rounded-lg text-gray-600 hover:bg-gray-50 transition">
                                Cancelar
                            </button>
                            <button type="submit" class="px-6 py-2.5 text-sm font-bold text-white rounded-lg shadow-sm bg-purpura-600 hover:bg-purpura-700 flex items-center gap-2 transition">
                                <i class="ph-bold ph-floppy-disk"></i> Salvar Formulário
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>