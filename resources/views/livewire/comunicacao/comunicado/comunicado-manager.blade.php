<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Histórico de Comunicados" 
        icon="ph ph-paper-plane-tilt"
        badge=""
        :breadcrumbs="$breadcrumbs">

        @if(feature('comunicado.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('comunicado.criar')))
            <x-slot name="actions">
                <a href="{{ route('comunicados.create') }}" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-600 hover:bg-purpura-700 font-bold text-sm">
                    <i class="ph ph-plus text-lg"></i> Novo Disparo
                </a>
            </x-slot>
        @endif

        <x-slot name="filters">
            <div class="flex gap-2 items-center flex-wrap">
                
                <select wire:model.live="filtro_template" class="rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500 max-w-[180px] truncate">
                    <option value="">Todos os Templates</option>
                    @if(isset($templatesDisponiveis))
                        @foreach($templatesDisponiveis as $tpl)
                            <option value="{{ $tpl->id }}">{{ $tpl->nome }}</option>
                        @endforeach
                    @endif
                </select>

                <select wire:model.live="filtro_status" class="rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500">
                    <option value="">Status...</option>
                    <option value="pendente">Agendados/Fila</option>
                    <option value="enviando">Enviando</option>
                    <option value="concluido">Concluídos</option>
                    <option value="erro">Com Erro</option>
                </select>

                <div class="flex items-center bg-white border border-gray-300 rounded-md shadow-sm px-2 overflow-hidden focus-within:ring-1 focus-within:ring-purpura-500 focus-within:border-purpura-500">
                    <span class="text-[10px] text-gray-500 font-bold uppercase mr-1">De</span>
                    <input wire:model.live="filtro_data_inicio" type="date" class="border-0 text-sm p-1.5 focus:ring-0 text-gray-700 bg-transparent cursor-pointer">
                    <div class="w-px h-4 bg-gray-200 mx-1"></div>
                    <span class="text-[10px] text-gray-500 font-bold uppercase mr-1">Até</span>
                    <input wire:model.live="filtro_data_fim" type="date" class="border-0 text-sm p-1.5 focus:ring-0 text-gray-700 bg-transparent cursor-pointer">
                </div>

                @if($filtro_status !== '' || $filtro_template !== '' || $filtro_data_inicio !== '' || $filtro_data_fim !== '')
                    <button wire:click="limparFiltros" class="px-3 py-2 text-sm font-bold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-1">
                        <i class="ph-bold ph-x"></i> Limpar
                    </button>
                @endif
            </div>
        </x-slot>

    </x-page-header>

    {{-- O Poller atualiza a tela para vermos se um "Pendente" virou "Concluído" --}}
    <div wire:poll.10s>
        <x-table 
            :headers="$this->headers" 
            :registros="$registros"
            :ordenacaoCampo="$ordenacaoCampo"
            :ordenacaoDirecao="$ordenacaoDirecao"
            :permiteGrid="$permiteGrid"
            :modoExibicao="$modoExibicao">
            
            @forelse($registros as $comunicado)
                <tr class="hover:bg-gray-50 transition-colors duration-200">
                    <td class="px-4 py-2.5 whitespace-nowrap text-sm font-medium text-gray-500">
                        #{{ $comunicado->id }}
                    </td>
                    <td class="px-4 py-2.5 whitespace-nowrap">
                        <div class="text-sm font-bold text-gray-900">{{ $comunicado->template->nome ?? 'Template Excluído' }}</div>
                        <div class="text-xs text-gray-500">{{ $comunicado->template->assunto ?? 'Sem Assunto' }}</div>
                    </td>
                    <td class="px-4 py-2.5 whitespace-nowrap">
                        @php 
                            $qtdDest = is_array($comunicado->destinatarios) ? count($comunicado->destinatarios) : 0;
                            $qtdAnexos = is_array($comunicado->anexos) ? count($comunicado->anexos) : 0;
                        @endphp
                        <span class="inline-flex items-center gap-1 text-sm font-bold text-gray-700">
                            <i class="ph-fill ph-users text-gray-400"></i> {{ $qtdDest }} e-mails
                        </span>
                        @if($qtdAnexos > 0)
                            <div class="text-[10px] text-gray-500 font-medium flex items-center gap-1 mt-0.5">
                                <i class="ph ph-paperclip"></i> {{ $qtdAnexos }} anexo(s)
                            </div>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 whitespace-nowrap">
                        @if($comunicado->data_agendamento)
                            <div class="text-sm font-bold text-gray-800">{{ $comunicado->data_agendamento->format('d/m/Y') }}</div>
                            <div class="text-xs text-gray-500">{{ $comunicado->data_agendamento->format('H:i') }}</div>
                        @else
                            <span class="text-xs text-gray-400 italic">Imediato</span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 whitespace-nowrap">
                        <span class="px-2.5 py-1 text-[11px] font-bold rounded-full uppercase tracking-wider border {{ $comunicado->status_color }}">
                            @if($comunicado->status === 'pendente') <i class="ph ph-clock mr-1"></i> Agendado
                            @elseif($comunicado->status === 'enviando') <i class="ph ph-spinner animate-spin mr-1"></i> Enviando
                            @elseif($comunicado->status === 'concluido') <i class="ph-bold ph-check mr-1"></i> Enviado
                            @elseif($comunicado->status === 'erro') <i class="ph-bold ph-warning mr-1"></i> Falhou
                            @endif
                        </span>
                    </td>
                    <td class="px-4 py-2.5 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-1">
                            @if(feature('comunicado.excluir') && (auth()->user()->hasRole('dev') || auth()->user()->can('comunicado.excluir')))
                                @if($comunicado->status === 'pendente')
                                    <button wire:click="excluir({{ $comunicado->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-red-500 hover:bg-red-50" title="Cancelar Agendamento" onclick="confirm('Tem certeza que deseja cancelar este envio?') || event.stopImmediatePropagation()">
                                        <i class="text-lg ph ph-x-square"></i>
                                    </button>
                                @else
                                    <button wire:click="excluir({{ $comunicado->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-gray-900 hover:bg-gray-100" title="Excluir Histórico" onclick="confirm('Excluir este histórico?') || event.stopImmediatePropagation()">
                                        <i class="text-lg ph ph-trash"></i>
                                    </button>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-gray-500 text-sm border-t border-gray-100">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3 border border-gray-200">
                            <i class="ph ph-paper-plane-tilt text-3xl text-gray-400"></i>
                        </div>
                        <p class="font-bold text-gray-600">Nenhum disparo registrado.</p>
                        <p class="text-xs mt-1">Crie um novo disparo para notificar seus estudantes.</p>
                    </td>
                </tr>
            @endforelse
        </x-table>
    </div>

    {{-- TOAST SYSTEM GLOBAL --}}
    <div x-data="{ show: false, msg: '', type: 'sucesso' }" 
        @sucesso.window="show = true; msg = $event.detail.msg; type = 'sucesso'; setTimeout(() => show = false, 3500);"
        @erro.window="show = true; msg = $event.detail.msg; type = 'erro'; setTimeout(() => show = false, 4500);"
        x-show="show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-10" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-10"
        :class="type === 'sucesso' ? 'bg-green-600' : 'bg-red-600'"
        class="fixed bottom-8 right-8 text-white px-6 py-4 rounded-xl shadow-2xl z-[200] flex items-center gap-3 font-bold" x-cloak>
        <i class="text-2xl ph" :class="type === 'sucesso' ? 'ph-check-circle' : 'ph-warning-circle'"></i>
        <span x-text="msg"></span>
    </div>
</div>