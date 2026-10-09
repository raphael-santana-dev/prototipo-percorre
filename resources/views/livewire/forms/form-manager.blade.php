<div class="p-6 max-w-7xl mx-auto font-sans relative">

    <x-page-header 
        title="Gerenciamento de Formulários" 
        icon="ph ph-list-dashes"
        badge="Central de Formulários"
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">

        <x-slot name="actions">
            @if(feature('formulario.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('formulario.criar')))
                <a href="{{ route('formularios.create') }}" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600 font-bold">
                    <i class="ph ph-plus text-lg"></i> Novo Formulário
                </a>
            @endif
        </x-slot>
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
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($form->descricao ?? '', 50) }}</div>
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="flex flex-col gap-1">
                        @if($form->acesso_livre ?? true)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-green-50 text-green-700 border border-green-200 uppercase w-max"><i class="ph-bold ph-globe"></i> Público</span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-red-50 text-red-700 border border-red-200 uppercase w-max"><i class="ph-bold ph-lock-key"></i> Restrito</span>
                        @endif
                        
                        @if(isset($form->data_inicio) && ($form->data_inicio || $form->data_fim))
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
                            <span class="text-[10px] font-bold {{ $form->status ? 'text-green-600' : 'text-gray-400' }}">
                                {{ $form->status ? 'ATIVO' : 'INATIVO' }}
                            </span>
                        @else
                            <span class="w-2 h-2 rounded-full {{ $form->status ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                            <span class="text-[10px] font-bold {{ $form->status ? 'text-green-600' : 'text-gray-400' }}">
                                {{ $form->status ? 'ATIVO' : 'INATIVO' }}
                            </span>
                        @endif
                    </div>
                </td>

                <td class="px-4 py-2.5 whitespace-nowrap">
                    @if($form->tipo === 'aprendizagem')
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200 uppercase">Aprendizagem</span>
                    @elseif($form->tipo === 'pre_inscricao')
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase">Pré-Inscrição</span>
                    @else
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-50 text-gray-700 border border-gray-200 uppercase">Geral</span>
                    @endif
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-1">
                        
                        <a href="{{ route('formularios.show', $form->id) }}" target="_blank" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600" title="Acessar Detalhes">
                            <i class="text-lg ph ph-eye"></i>
                        </a>

                        @if($form->tipo === 'aprendizagem')
                            <a href="{{ route('aprendizagem.index') }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Gerenciar no Ciclo de Aprendizagem">
                                <i class="text-lg ph ph-tree-structure"></i>
                            </a>
                        @else
                            <a href="{{ route('formularios.publico', ['id' => $form->id, 'slug' => $form->slug]) }}" target="_blank" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Ver Link Público">
                                <i class="text-lg ph ph-arrow-square-in"></i>
                            </a>
                            
                            <!-- Botão Embed apenas para Formulários Gerais e de Captação -->
                            <button x-data="{ copiado: false }" 
                                    @click="
                                        let code = `<iframe src='{{ route('formularios.publico', ['id' => $form->id, 'slug' => $form->slug]) }}?embed=true' width='100%' height='800' frameborder='0' style='border:none; border-radius: 8px;'></iframe>`;
                                        navigator.clipboard.writeText(code); 
                                        copiado = true; 
                                        setTimeout(() => copiado = false, 2000);
                                    " 
                                    class="p-1.5 transition-colors rounded-lg relative" 
                                    :class="copiado ? 'text-green-600 bg-green-50 dark:bg-green-900/30' : 'text-gray-400 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-gray-600'"
                                    title="Copiar Código de Incorporação (Iframe)">
                                <i class="text-lg ph" :class="copiado ? 'ph-check-circle' : 'ph-code'"></i>
                            </button>
                        @endif

                        <a href="{{ route('formularios.planilha', $form->id) }}" wire:navigate class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-green-500 hover:bg-green-50 dark:hover:bg-gray-600" title="Visualizar Respostas em Planilha">
                            <i class="text-lg ph ph-table"></i>
                        </a>

                        @if(feature('formulario.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('formulario.editar')))
                            <a href="{{ route('construtor.campos', ['tipo' => 'formulario', 'id' => $form->id]) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Construtor de Campos">
                                <i class="text-lg ph ph-list-dashes"></i>
                            </a>
                            
                            @if($form->tipo === 'geral')
                                <button wire:click="abrirModal({{ $form->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar Informações">
                                    <i class="text-lg ph ph-pencil-simple"></i>
                                </button>
                            @endif
                        @endif
                        
                        @if(feature('formulario.excluir') && (auth()->user()->hasRole('dev') || auth()->user()->can('formulario.excluir')))
                            <button wire:click="excluir({{ $form->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir Formulário" onclick="confirm('Atenção: Ao excluir o formulário, todas as respostas vinculadas a ele também serão deletadas. Deseja continuar?') || event.stopImmediatePropagation()">
                                <i class="text-lg ph ph-trash"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                    <p class="font-semibold">Nenhum formulário encontrado.</p>
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
                        <div>
                            <x-toggle :status="$form->status" action="toggleStatus({{ $form->id }})" />
                            <div class="text-[10px] mt-1 font-bold {{ $form->status ? 'text-green-600' : 'text-gray-500' }}">
                                {{ $form->status ? 'ATIVO' : 'INATIVO' }}
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <a href="{{ route('formularios.show', $form->id) }}" target="_blank" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-ponkan-500 hover:bg-ponkan-50 dark:hover:bg-gray-600" title="Acessar Detalhes"><i class="text-lg ph ph-eye"></i></a>
                            
                            @if($form->tipo === 'aprendizagem')
                                <a href="{{ route('aprendizagem.index') }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Gerenciar no Ciclo"><i class="text-lg ph ph-tree-structure"></i></a>
                            @else
                                <a href="{{ route('formularios.publico', ['id' => $form->id, 'slug' => $form->slug]) }}" target="_blank" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600" title="Ver Link Público"><i class="text-lg ph ph-arrow-square-in"></i></a>
                            @endif

                            <a href="{{ route('formularios.planilha', $form->id) }}" wire:navigate class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-green-500 hover:bg-green-50 dark:hover:bg-gray-600" title="Planilha"><i class="text-lg ph ph-table"></i></a>
                            
                            @if(feature('formulario.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('formulario.editar')))
                                <a href="{{ route('construtor.campos', ['tipo' => 'formulario', 'id' => $form->id]) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Construtor"><i class="text-lg ph ph-list-dashes"></i></a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </x-slot>
    </x-table>

    <!-- Modal de Configuração (Apenas para Formulários Gerais) -->
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

                        <div class="flex items-center pt-2">
                            <input type="checkbox" wire:model="status" id="status" class="w-5 h-5 border-gray-300 rounded text-purpura-600 focus:ring-purpura-500">
                            <label for="status" class="block ml-2 text-sm font-bold text-gray-900 dark:text-gray-300">Ativar link do formulário (Status Geral)</label>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 mt-6 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" wire:click="$set('modalAberto', false)" class="px-5 py-2.5 text-sm font-bold border rounded-lg text-gray-600 hover:bg-gray-50 transition">Cancelar</button>
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