<div class="p-6 max-w-7xl mx-auto font-sans">
    
    <div class="mb-6 flex justify-between items-center border-b border-gray-200 pb-4">
        <div>
            <a href="{{ route('formularios.index') }}" class="text-indigo-600 hover:text-indigo-800 transition text-sm mb-1 inline-flex items-center gap-1 font-medium">
                <i class="ph ph-arrow-left"></i> Voltar para Listagem
            </a>
            <h2 class="text-2xl font-bold text-gray-900 mt-1">{{ $formulario->titulo }}</h2>
            <p class="text-gray-500 text-sm flex items-center gap-2">
                <span class="inline-block w-2 h-2 rounded-full {{ $formulario->status ? 'bg-green-500' : 'bg-red-500' }}"></span>
                {{ $formulario->status ? 'Recebendo respostas ativamente' : 'Formulário Inativo (Fechado)' }}
            </p>
        </div>
        
        <div class="flex items-center gap-3">
            <a href="{{ route('formularios.publico', ['id' => $formulario->id, 'slug' => $formulario->slug]) }}" target="_blank" class="flex items-center gap-2 px-4 py-2 text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition rounded-lg font-bold">
                <i class="ph ph-link"></i> Abrir Link Público
            </a>
            <a href="{{ route('construtor.campos', ['tipo' => 'formulario', 'id' => $formulario->id]) }}" class="flex items-center gap-2 px-4 py-2 text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition rounded-lg font-bold">
                <i class="ph ph-list-dashes"></i> Editar Estrutura
            </a>
        </div>
    </div>

    <!-- CARDS DE INFORMAÇÃO -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 text-2xl">
                <i class="ph-fill ph-users"></i>
            </div>
            <div>
                <p class="text-sm font-bold text-gray-500 uppercase">Total de Respostas</p>
                <h3 class="text-2xl font-extrabold text-gray-900">{{ $totalRespostas }}</h3>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-purple-50 flex items-center justify-center text-purple-600 text-2xl">
                <i class="ph-fill ph-calendar-check"></i>
            </div>
            <div>
                <p class="text-sm font-bold text-gray-500 uppercase">Data de Criação</p>
                <h3 class="text-lg font-bold text-gray-900 mt-1">{{ $formulario->created_at->format('d/m/Y') }}</h3>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-orange-50 flex items-center justify-center text-orange-600 text-2xl">
                <i class="ph-fill ph-share-network"></i>
            </div>
            <div>
                <p class="text-sm font-bold text-gray-500 uppercase">Código Único (Slug)</p>
                <p class="text-sm font-mono text-gray-900 mt-1 truncate max-w-[200px]" title="{{ $formulario->slug }}">{{ $formulario->slug }}</p>
            </div>
        </div>
    </div>

    <!-- HEADER DA TABELA + BOTÃO TOGGLE -->
    <div class="flex items-center justify-between mb-4 flex-wrap gap-4">
        <h3 class="font-bold text-gray-800 text-lg">Respostas Recebidas</h3>
        
        <div class="flex bg-gray-200/80 p-1 rounded-lg">
            <button wire:click="$set('tipoVisao', 'resumo')" class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-md transition-colors {{ $tipoVisao === 'resumo' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                <i class="ph-bold ph-list-dashes text-lg"></i> Resumida
            </button>
            <button wire:click="$set('tipoVisao', 'tabela')" class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-md transition-colors {{ $tipoVisao === 'tabela' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
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
                
                <tr class="hover:bg-gray-50 transition-colors group">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2.5 py-1 text-xs font-mono font-bold bg-gray-100 text-gray-600 rounded">#{{ str_pad($resp->id, 5, '0', STR_PAD_LEFT) }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800">
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 max-w-[250px] truncate" title="{{ $valStr }}">
                                {{ $valStr }}
                            </td>
                        @endforeach
                    @else
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-green-50 text-green-700 border border-green-200">
                                <i class="ph-fill ph-check-circle"></i> Respondido até Etapa {{ $resp->etapa_parada }}
                            </span>
                        </td>

                        {{-- Coluna FIXA nas Ações AGORA EXCLUSIVA DO MODO RESUMO --}}
                        <td class="px-6 py-4 whitespace-nowrap text-right sticky right-0 bg-white group-hover:bg-gray-50 transition-colors shadow-[-4px_0_6px_-2px_rgba(0,0,0,0.05)] z-10">
                            <a href="{{ route('formularios.respostas.show', $resp->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-bold text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                <i class="ph-bold ph-file-text text-lg"></i> Ler Completo
                            </a>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    {{-- Ajustamos o colspan para "count($campos) + 2" no modo tabela, pois a ação não existe mais --}}
                    <td colspan="{{ $tipoVisao === 'tabela' ? count($campos) + 2 : 4 }}" class="px-6 py-12 text-center text-gray-500">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 mb-4">
                            <i class="ph ph-tray text-3xl text-gray-400"></i>
                        </div>
                        <p class="text-lg font-bold text-gray-900 mb-1">Caixa de entrada vazia</p>
                        <p class="text-sm">Compartilhe o link do formulário para começar a receber respostas.</p>
                    </td>
                </tr>
            @endforelse
        </x-table>
    </div>

    <style>
    /* Suaviza o Scrollbar para o modo planilha */
    .custom-scrollbar::-webkit-scrollbar { height: 8px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f9fafb; border-top: 1px solid #e5e7eb; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #d1d5db; border-radius: 10px; }
    .custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: #9ca3af; }
</style>
</div>

