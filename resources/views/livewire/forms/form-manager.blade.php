<div class="p-6 max-w-7xl mx-auto font-sans relative">

    <x-page-header 
        title="Gerenciamento de Formulários" 
        icon="ph ph-list-dashes"
        badge="Central de Formulários"
        :breadcrumbs="$breadcrumbs" 
        :metricas="$metricas ?? null">

        <x-slot name="actions">
            @if(feature('formulario.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('formulario.criar')))
                <a href="{{ route('formularios.create') }}" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-600 hover:bg-purpura-700 font-bold text-sm">
                    <i class="ph-bold ph-plus text-base"></i> Novo Formulário
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
                <td class="px-4 py-2.5 whitespace-nowrap text-xs font-mono font-bold text-gray-500 dark:text-gray-400">
                    #{{ $form->id }}
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <a href="{{ route('formularios.show', $form->id) }}" class="font-bold text-gray-900 hover:text-purpura-600 dark:text-white transition-colors block">
                        {{ $form->titulo }}
                    </a>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($form->descricao ?? 'Sem descrição informada', 55) }}</div>
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="flex flex-col gap-1">
                        @if($form->acesso_livre ?? true)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase w-max">
                                <i class="ph-bold ph-globe"></i> Público
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 uppercase w-max">
                                <i class="ph-bold ph-lock-key"></i> Restrito
                            </span>
                        @endif
                    </div>
                </td>

                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                        @if(feature('formulario.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('formulario.editar')))
                            <x-toggle :status="$form->status" action="toggleStatus({{ $form->id }})" />
                            <span class="text-[10px] font-bold {{ $form->status ? 'text-emerald-600' : 'text-gray-400' }}">
                                {{ $form->status ? 'ATIVO' : 'INATIVO' }}
                            </span>
                        @else
                            <span class="w-2 h-2 rounded-full {{ $form->status ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                            <span class="text-[10px] font-bold {{ $form->status ? 'text-emerald-600' : 'text-gray-400' }}">
                                {{ $form->status ? 'ATIVO' : 'INATIVO' }}
                            </span>
                        @endif
                    </div>
                </td>

                <td class="px-4 py-2.5 whitespace-nowrap">
                    @if($form->tipo === 'aprendizagem')
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200 uppercase">Aprendizagem</span>
                    @else
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-700 border border-gray-200 uppercase">Geral</span>
                    @endif
                </td>

                <td class="px-4 py-2.5 whitespace-nowrap">
                    <a href="{{ route('formularios.show', $form->id) }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-purpura-50 text-purpura-700 hover:bg-purpura-100 border border-purpura-200 transition">
                        <i class="ph-bold ph-files"></i> {{ $form->total_respostas }} {{ $form->total_respostas == 1 ? 'resposta' : 'respostas' }}
                    </a>
                </td>

                <td class="px-4 py-2.5 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                    {{ $form->data_inicio ? $form->data_inicio->format('d/m/Y H:i') : '-' }}
                </td>

                <td class="px-4 py-2.5 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                    {{ $form->data_fim ? $form->data_fim->format('d/m/Y H:i') : '-' }}
                </td>

                <td class="px-4 py-2.5 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                    {{ $form->created_at ? $form->created_at->format('d/m/Y H:i') : '-' }}
                </td>

                <td class="px-4 py-2.5 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                    {{ $form->updated_at ? $form->updated_at->format('d/m/Y H:i') : '-' }}
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap text-right">
                    <div class="flex items-center justify-end gap-1">
                        <a href="{{ route('formularios.show', $form->id) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-600 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Ver Respostas e Detalhes">
                            <i class="text-lg ph-bold ph-eye"></i>
                        </a>

                        <button wire:click="duplicar({{ $form->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-gray-600" title="Duplicar Formulário">
                            <i class="text-lg ph-bold ph-copy"></i>
                        </button>

                        @if($form->tipo === 'aprendizagem')
                            <a href="{{ route('aprendizagem.index') }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Gerenciar no Ciclo de Aprendizagem">
                                <i class="text-lg ph-bold ph-tree-structure"></i>
                            </a>
                        @else
                            <a href="{{ route('formularios.publico', ['id' => $form->id, 'slug' => $form->slug]) }}" target="_blank" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-gray-600" title="Abrir Link Público">
                                <i class="text-lg ph-bold ph-arrow-square-out"></i>
                            </a>
                            
                            <button x-data="{ copiado: false }" 
                                    @click="
                                        let code = `<iframe src='{{ route('formularios.publico', ['id' => $form->id, 'slug' => $form->slug]) }}?embed=true' width='100%' height='800' frameborder='0' style='border:none; border-radius: 8px;'></iframe>`;
                                        navigator.clipboard.writeText(code); 
                                        copiado = true; 
                                        setTimeout(() => copiado = false, 2000);
                                    " 
                                    class="p-1.5 transition-colors rounded-lg relative" 
                                    :class="copiado ? 'text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30' : 'text-gray-400 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-gray-600'"
                                    title="Copiar Código de Incorporação (Iframe)">
                                <i class="text-lg ph-bold" :class="copiado ? 'ph-check-circle' : 'ph-code'"></i>
                            </button>
                        @endif

                        @if(feature('formulario.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('formulario.editar')))
                            <a href="{{ route('construtor.campos', ['tipo' => 'formulario', 'id' => $form->id]) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-600 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Construtor de Campos">
                                <i class="text-lg ph-bold ph-list-dashes"></i>
                            </a>
                            
                            <a href="{{ route('formularios.edit', $form->id) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-gray-600" title="Editar Configurações e Regras de Acesso">
                                <i class="text-lg ph-bold ph-pencil-simple"></i>
                            </a>
                        @endif
                        
                        @if(feature('formulario.excluir') && (auth()->user()->hasRole('dev') || auth()->user()->can('formulario.excluir')))
                            <button wire:click="excluir({{ $form->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-600 hover:bg-red-50 dark:hover:bg-gray-600" title="Excluir Formulário" onclick="confirm('Atenção: Ao excluir o formulário, todas as respostas vinculadas a ele também serão deletadas. Deseja continuar?') || event.stopImmediatePropagation()">
                                <i class="text-lg ph-bold ph-trash"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="11" class="px-4 py-10 text-center text-gray-500">
                    <p class="font-semibold">Nenhum formulário encontrado.</p>
                </td>
            </tr>
        @endforelse

        <x-slot name="gridSlot">
            @foreach ($registros as $form)
                <div class="flex flex-col p-4 bg-white border border-gray-200 shadow-xs rounded-xl dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-2">
                        <a href="{{ route('formularios.show', $form->id) }}" class="text-sm font-bold text-gray-900 hover:text-purpura-600 dark:text-white truncate pr-2">
                            {{ $form->titulo }}
                        </a>
                        <span class="px-2 py-0.5 text-[10px] font-mono font-bold text-gray-500 bg-gray-100 rounded border border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">#{{ $form->id }}</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-4 line-clamp-2 min-h-[32px]">
                        {{ $form->descricao ?: 'Sem descrição informada...' }}
                    </div>
                    <div class="flex items-center justify-between mt-auto pt-3 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <x-toggle :status="$form->status" action="toggleStatus({{ $form->id }})" />
                            <span class="text-[10px] font-bold {{ $form->status ? 'text-emerald-600' : 'text-gray-400' }}">
                                {{ $form->total_respostas }} resp.
                            </span>
                        </div>
                        <div class="flex items-center gap-1">
                            <a href="{{ route('formularios.show', $form->id) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-600 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Ver Respostas"><i class="text-lg ph-bold ph-eye"></i></a>
                            @if(feature('formulario.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('formulario.editar')))
                                <a href="{{ route('construtor.campos', ['tipo' => 'formulario', 'id' => $form->id]) }}" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-600 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Construtor"><i class="text-lg ph-bold ph-list-dashes"></i></a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </x-slot>
    </x-table>
</div>