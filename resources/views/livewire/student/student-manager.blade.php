<div class="p-6 max-w-7xl mx-auto font-sans">
    @if (session()->has('sucesso'))
        <div class="flex items-center gap-2 p-4 mb-4 rounded-md text-pistache-100 bg-pistache-500">
            <i class="ph ph-check-circle text-lg"></i> {{ session('sucesso') }}
        </div>
    @endif

    <x-breadcrumb :items="$breadcrumbs" />

    <div class="flex items-center justify-between mb-6">
        <h2 class="flex items-center gap-2 text-2xl font-bold text-gray-900 dark:text-white">
            <i class="ph ph-calendar-check text-purpura-500"></i> Gerenciamento de Estudantes
        </h2>
        @can('estudante.criar')
            <button wire:click="openModal" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg bg-purpura-500 hover:bg-purpura-600 shadow-sm">
                <i class="ph ph-plus"></i> Novo Aluno
            </button>
        @endcan
    </div>

    @if(isset($metricas))
        <x-summary-cards :metricas="$metricas" />
    @endif

    <x-table
        :headers="$this->headers"
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid"
        :modoExibicao="$modoExibicao">

        @forelse ($registros as $student)
            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $student->id }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $student->name }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $student->email }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($student->unidade)
                        <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                            <i class="ph-fill ph-map-pin text-purpura-500"></i> {{ $student->unidade->nome }}
                        </div>
                    @else
                        <span class="text-sm text-gray-400 italic">Sem Unidade</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <x-toggle :status="$student->is_active" action="toggleStatus({{ $student->id }})" />
                    
                    <div class="text-[10px] mt-1 font-bold {{ $student->is_active ? 'text-green-600' : 'text-gray-500' }}">
                        {{ $student->is_active ? 'ATIVO' : 'INATIVO' }}
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center justify-end gap-2">
                        <button wire:click="showQuickDetails({{ $student->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Ficha Rápida">
                            <i class="text-xl ph ph-info"></i>
                        </button>

                        <a href="{{ route('students.show', $student->id) }}" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600" title="Ver Perfil Completo">
                            <i class="text-xl ph ph-eye"></i>
                        </a>
                        
                        @can('estudante.editar')
                            <button wire:click="edit({{ $student->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar Matrícula">
                                <i class="text-xl ph ph-pencil-simple"></i>
                            </button>
                        @endcan
                        
                        @can('estudante.excluir')
                            <button wire:click="delete({{ $student->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir Aluno" onclick="confirm('Excluir permanentemente este aluno do sistema?') || event.stopImmediatePropagation()">
                                <i class="text-xl ph ph-trash"></i>
                            </button>
                        @endcan
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                    <p class="text-lg font-semibold">Nenhum ciclo encontrado.</p>
                    <p class="text-sm">Ajuste os filtros ou crie um novo ciclo.</p>
                </td>
            </tr>
        @endforelse

        <x-slot name="gridSlot">
            @foreach ( $registros as $student )
                <div class="flex flex-col p-4 bg-white border border-gray-100 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-2">
                        
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                        <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $student->email }}</div>
                    </div>
                    <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-100 dark:border-gray-700">
                        <x-toggle :status="$student->is_active" action="toggleStatus({{ $student->id }})" />
                    
                        <div class="flex items-center gap-2">
                            <button wire:click="showQuickDetails({{ $student->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Ficha Rápida">
                                <i class="text-xl ph ph-info"></i>
                            </button>

                            <a href="{{ route('students.show', $student->id) }}" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600" title="Ver Perfil Completo">
                                <i class="text-xl ph ph-eye"></i>
                            </a>
                            
                            @can('estudante.editar')
                                <button wire:click="edit({{ $student->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar Matrícula">
                                    <i class="text-xl ph ph-pencil-simple"></i>
                                </button>
                            @endcan
                            
                            @can('estudante.excluir')
                                <button wire:click="delete({{ $student->id }})" class="p-2 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir Aluno" onclick="confirm('Excluir permanentemente este aluno do sistema?') || event.stopImmediatePropagation()">
                                    <i class="text-xl ph ph-trash"></i>
                                </button>
                            @endcan
                        </div>
                    </div>
                </div>
            @endforeach
        </x-slot>

    </x-table>

    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="$set('showModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-xl sm:w-full sm:p-6 dark:bg-gray-800">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 border-b border-gray-100 pb-2 dark:border-gray-700">
                        {{ $isEditMode ? 'Editar Estudante' : 'Nova Matrícula' }}
                    </h3>
                    
                    <form wire:submit="save" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Nome Completo</label>
                                <input type="text" wire:model="name" class="w-full mt-1">
                                @error('name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">E-mail</label>
                                <input type="email" wire:model="email" class="w-full mt-1">
                                @error('email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Senha {{ $isEditMode ? '(Deixe vazio para manter)' : '' }}</label>
                                <input type="password" wire:model="password" class="w-full mt-1">
                                @error('password') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Unidade (Sede)</label>
                                <select wire:model="unidade_id" class="w-full mt-1">
                                    <option value="">Selecione...</option>
                                    @foreach($unidades as $unidade)
                                        <option value="{{ $unidade->id }}">{{ $unidade->nome }}</option>
                                    @endforeach
                                </select>
                                @error('unidade_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div class="flex items-center pt-6">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="is_active" class="w-5 h-5 text-purpura-600 border-gray-300 rounded focus:ring-purpura-500">
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Matrícula Ativa</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 mt-6 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-sm font-bold border rounded-lg text-purpura-500 border-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-700">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 text-sm font-bold text-white rounded-lg bg-ponkan-500 hover:bg-ponkan-600 shadow-sm">
                                Salvar Matrícula
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>