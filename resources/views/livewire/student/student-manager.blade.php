<div class="p-6 max-w-7xl mx-auto font-sans relative">

    <x-page-header 
        title="Gerenciamento de Estudantes" 
        icon="ph ph-student"
        badge=""
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">

        <x-slot name="actions">
            @can('estudante.criar')
                <button wire:click="openModal" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600">
                    <i class="ph ph-plus text-lg"></i> Novo Aluno
                </button>
            @endcan
        </x-slot>

        <x-slot name="filters">
            <div class="flex gap-2">
                <input wire:model.live.debounce.300ms="filtro_busca" type="text" placeholder="Buscar nome ou e-mail..." class="rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500 w-56">
                
                <select wire:model.live="filtro_unidade" class="rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500">
                    <option value="">Todas as Unidades</option>
                    @foreach($unidades as $unidade)
                        <option value="{{ $unidade->id }}">{{ $unidade->nome }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filtro_status" class="rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500">
                    <option value="">Status...</option>
                    <option value="1">Ativos</option>
                    <option value="0">Inativos</option>
                </select>

                @if($filtro_busca !== '' || $filtro_unidade !== '' || $filtro_status !== '')
                    <button wire:click="limparFiltros" class="px-3 py-2 text-sm font-bold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-1">
                        <i class="ph-bold ph-x"></i> Limpar
                    </button>
                @endif
            </div>
        </x-slot>

    </x-page-header>

    <x-table
        :headers="$this->headers"
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid"
        :modoExibicao="$modoExibicao">

        @forelse ($registros as $student)
            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                
                <td class="px-4 py-2.5 whitespace-nowrap text-sm font-medium text-gray-500 dark:text-gray-400">
                    #{{ $student->id }}
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $student->name }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $student->email }}</div>
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap">
                    @if($student->unidade)
                        <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-gray-100 text-gray-800 border border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600">
                            <i class="ph-fill ph-map-pin text-purpura-500"></i> {{ $student->unidade->nome }}
                        </div>
                    @else
                        <span class="text-xs text-gray-400 italic">Sem Unidade</span>
                    @endif
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                        <x-toggle :status="$student->is_active" action="toggleStatus({{ $student->id }})" />
                        <span class="text-[10px] font-bold {{ $student->is_active ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $student->is_active ? 'ATIVO' : 'INATIVO' }}
                        </span>
                    </div>
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-1">
                        <button wire:click="showQuickDetails({{ $student->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Ficha Rápida">
                            <i class="text-lg ph ph-info"></i>
                        </button>

                        <a href="{{ route('students.show', $student->id) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600" title="Ver Perfil Completo">
                            <i class="text-lg ph ph-eye"></i>
                        </a>
                        
                        @can('estudante.editar')
                            <button wire:click="edit({{ $student->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar Matrícula">
                                <i class="text-lg ph ph-pencil-simple"></i>
                            </button>
                        @endcan
                        
                        @can('estudante.excluir')
                            <button wire:click="delete({{ $student->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir Aluno" onclick="confirm('Excluir permanentemente este aluno do sistema?') || event.stopImmediatePropagation()">
                                <i class="text-lg ph ph-trash"></i>
                            </button>
                        @endcan
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                    <p class="font-semibold">Nenhum aluno encontrado.</p>
                    <p class="text-xs mt-1">Ajuste os filtros ou cadastre um novo aluno.</p>
                </td>
            </tr>
        @endforelse

        {{-- VISÃO DE GRID (CARDS) --}}
        <x-slot name="gridSlot">
            @foreach ( $registros as $student )
                <div class="flex flex-col p-4 bg-white border border-gray-100 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $student->name }}</div>
                        <span class="px-2 py-1 text-[10px] font-bold text-gray-500 bg-gray-100 rounded border border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">#{{ $student->id }}</span>
                    </div>
                    
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-4 line-clamp-2 min-h-[32px]">
                        <i class="ph-fill ph-envelope-simple"></i> {{ $student->email }}<br>
                        @if($student->unidade)
                            <i class="ph-fill ph-map-pin text-purpura-500 mt-1"></i> {{ $student->unidade->nome }}
                        @endif
                    </div>
                    
                    <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-100 dark:border-gray-700">
                        <div>
                            <x-toggle :status="$student->is_active" action="toggleStatus({{ $student->id }})" />
                            <div class="text-[10px] mt-1 font-bold {{ $student->is_active ? 'text-green-600' : 'text-gray-500' }}">
                                {{ $student->is_active ? 'ATIVO' : 'INATIVO' }}
                            </div>
                        </div>
                    
                        <div class="flex items-center gap-1">
                            <button wire:click="showQuickDetails({{ $student->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Ficha Rápida">
                                <i class="text-lg ph ph-info"></i>
                            </button>

                            <a href="{{ route('students.show', $student->id) }}" class="p-1.5 text-gray-400 transition-colors rounded hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600" title="Ver Perfil Completo">
                                <i class="text-lg ph ph-eye"></i>
                            </a>
                            
                            @can('estudante.editar')
                                <button wire:click="edit({{ $student->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar Matrícula">
                                    <i class="text-lg ph ph-pencil-simple"></i>
                                </button>
                            @endcan
                            
                            @can('estudante.excluir')
                                <button wire:click="delete({{ $student->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir Aluno" onclick="confirm('Excluir permanentemente este aluno do sistema?') || event.stopImmediatePropagation()">
                                    <i class="text-lg ph ph-trash"></i>
                                </button>
                            @endcan
                        </div>
                    </div>
                </div>
            @endforeach
        </x-slot>

    </x-table>

    <!-- Modal Padrão -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="$set('showModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-xl sm:w-full sm:p-6 dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-bold text-gray-900 border-b border-gray-100 pb-2 dark:text-white dark:border-gray-700">
                        {{ $isEditMode ? 'Editar Estudante' : 'Nova Matrícula' }}
                    </h3>
                    
                    <form wire:submit.prevent="save" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Nome Completo <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="name" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('name') <span class="block mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">E-mail <span class="text-red-500">*</span></label>
                                <input type="email" wire:model="email" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('email') <span class="block mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Senha {{ $isEditMode ? '(Deixe vazio para manter)' : '' }}</label>
                                <input type="password" wire:model="password" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('password') <span class="block mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300">Unidade (Sede)</label>
                                <select wire:model="unidade_id" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-purpura-500 focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="">Selecione...</option>
                                    @foreach($unidades as $unidade)
                                        <option value="{{ $unidade->id }}">{{ $unidade->nome }}</option>
                                    @endforeach
                                </select>
                                @error('unidade_id') <span class="block mt-1 text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div class="sm:col-span-2 pt-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="is_active" class="w-5 h-5 text-purpura-600 border-gray-300 rounded focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600">
                                    <span class="text-sm font-bold text-gray-900 dark:text-gray-300">Matrícula Ativa</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 mt-6 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-sm font-bold border rounded-lg text-purpura-500 border-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-700">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 text-sm font-bold text-white rounded-lg shadow-sm bg-ponkan-500 hover:bg-ponkan-600">
                                Salvar Matrícula
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