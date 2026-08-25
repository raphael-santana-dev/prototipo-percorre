<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="{{ $formulario->titulo }}" 
        icon="ph ph-list-dashes"
        badge="{{ $formulario->status ? 'Recebendo Respostas' : 'Fechado' }}">
        
        <x-slot name="actions">
            <a href="{{ route('formularios.index') }}" class="px-4 py-2 text-sm font-bold border rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 flex items-center gap-2 mr-2">
                <i class="ph-bold ph-arrow-left"></i> Voltar
            </a>

            <a href="{{ route('formularios.publico', ['id' => $formulario->id, 'slug' => $formulario->slug]) }}" target="_blank" class="flex items-center gap-2 px-4 py-2 text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition rounded-lg font-bold shadow-sm text-sm dark:bg-indigo-900/30 dark:text-indigo-400 dark:border-indigo-800 border border-indigo-200">
                <i class="ph-bold ph-link text-lg"></i> Link Público
            </a>
            
            @if(feature('formulario.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('formulario.editar')))
                <a href="{{ route('construtor.campos', ['tipo' => 'formulario', 'id' => $formulario->id]) }}" class="flex items-center gap-2 px-4 py-2 text-white bg-purpura-600 hover:bg-purpura-700 shadow-sm transition rounded-lg font-bold text-sm">
                    <i class="ph-bold ph-pencil-simple text-lg"></i> Editar Estrutura
                </a>
            @endif
        </x-slot>
    </x-page-header>

    <!-- CARDS DE INFORMAÇÃO -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 text-2xl">
                <i class="ph-fill ph-users"></i>
            </div>
            <div>
                <p class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase">Total de Respostas</p>
                <h3 class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ $totalRespostas }}</h3>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 dark:text-purple-400 text-2xl">
                <i class="ph-fill ph-calendar-check"></i>
            </div>
            <div>
                <p class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase">Data de Criação</p>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mt-1">{{ $formulario->created_at->format('d/m/Y') }}</h3>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-orange-50 dark:bg-orange-900/30 flex items-center justify-center text-orange-600 dark:text-orange-400 text-2xl">
                <i class="ph-fill ph-share-network"></i>
            </div>
            <div class="overflow-hidden">
                <p class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase">Código Único (Slug)</p>
                <p class="text-sm font-mono text-gray-900 dark:text-gray-300 mt-1 truncate max-w-[200px]" title="{{ $formulario->slug }}">{{ $formulario->slug }}</p>
            </div>
        </div>
    </div>

    <!-- HEADER DA TABELA + BOTÃO TOGGLE -->
    <div class="flex items-center justify-between mb-4 flex-wrap gap-4">
        <h3 class="font-bold text-gray-800 dark:text-gray-200 text-lg">Respostas Recebidas</h3>
        
        <div class="flex bg-gray-200/80 dark:bg-gray-800/80 p-1 rounded-lg border border-gray-200 dark:border-gray-700">
            <button wire:click="$set('tipoVisao', 'resumo')" class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-md transition-colors {{ $tipoVisao === 'resumo' ? 'bg-white dark:bg-gray-700 text-purpura-700 dark:text-purpura-400 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">
                <i class="ph-bold ph-list-dashes text-lg"></i> Resumida
            </button>
            <button wire:click="$set('tipoVisao', 'tabela')" class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-md transition-colors {{ $tipoVisao === 'tabela' ? 'bg-white dark:bg-gray-700 text-purpura-700 dark:text-purpura-400 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">
                <i class="ph-bold ph-table text-lg"></i> Planilha (Perguntas)
            </button>
        </div>
    </div>

    <!-- TABELA COMPONENTE -->
    <div class="custom-scrollbar {{ $tipoVisao === 'tabela' ? 'overflow-x-auto' : '' }}">
        <x-table
            :headers="$this->headers"
            :registros="$respostas"
            :ordenacaoCampo="$ordenacaoCampo"
            :ordenacaoDirecao="$ordenacaoDirecao"
            :permiteGrid="false">

            @forelse($respostas as $resp)
                @php
                    $respostasSalvas = is_string($resp->respostas) ? json_decode($resp->respostas, true) : ($resp->respostas ?? []);
                @endphp
                
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2.5 py-1 text-xs font-mono font-bold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 border border-gray-200 dark:border-gray-700 rounded">#{{ str_pad($resp->id, 5, '0', STR_PAD_LEFT) }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-gray-300">
                        {{ $resp->created_at->format('d/m/Y \à\s H:i') }}
                    </td>
                    
                    @if($tipoVisao === 'tabela')
                        @foreach($campos as $campo)
                            @php
                                $val = $respostasSalvas[$campo->name] ?? null;
                                if(is_array($val)) {
                                    $valStr = implode(' | ', $val);
                                } else {
                                    $valStr = (string) $val;
                                }
                                $valStr = empty(trim($valStr)) ? '-' : $valStr;
                            @endphp
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400 max-w-[250px] truncate" title="{{ $valStr }}">
                                {{ $valStr }}
                            </td>
                        @endforeach
                    @else
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-green-50 text-green-700 border border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800">
                                <i class="ph-fill ph-check-circle"></i> Respondido até Etapa {{ $resp->etapa_parada }}
                            </span>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-right sticky right-0 bg-white dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-700/50 transition-colors shadow-[-4px_0_6px_-2px_rgba(0,0,0,0.05)] z-10">
                            @if(feature('formulario.respostas') && (auth()->user()->hasRole('dev') || auth()->user()->can('formulario.respostas')))
                                <a href="{{ route('formularios.respostas.show', $resp->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-bold text-purpura-600 bg-purpura-50 rounded-lg hover:bg-purpura-100 transition dark:bg-purpura-900/30 dark:text-purpura-400 dark:hover:bg-purpura-900/50">
                                    <i class="ph-bold ph-file-text text-lg"></i> Ler Completo
                                </a>
                            @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $tipoVisao === 'tabela' ? count($campos) + 2 : 4 }}" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 dark:bg-gray-800 mb-4 border border-gray-200 dark:border-gray-700">
                            <i class="ph ph-tray text-3xl text-gray-400"></i>
                        </div>
                        <p class="text-lg font-bold text-gray-900 dark:text-white mb-1">Caixa de entrada vazia</p>
                        <p class="text-sm">Compartilhe o link do formulário para começar a receber respostas.</p>
                    </td>
                </tr>
            @endforelse
        </x-table>
    </div>

    <style>
    /* Suaviza o Scrollbar para o modo planilha */
    .custom-scrollbar::-webkit-scrollbar { height: 8px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #d1d5db; border-radius: 10px; }
    .custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: #9ca3af; }
    </style>
</div>