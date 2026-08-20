<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Fila de E-mails" 
        icon="ph ph-envelope-open"
        badge=""
        :breadcrumbs="$breadcrumbs">

        <x-slot name="filters">
            <div class="flex gap-4">
                <select wire:model.live="filtro_status" class="rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500">
                    <option value="">Todos os Status</option>
                    <option value="pendente">Pendentes (Na Fila)</option>
                    <option value="enviado">Enviados (Sucesso)</option>
                    <option value="erro">Falhas de Envio</option>
                </select>

                <select wire:model.live="filtro_origem" class="rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500">
                    <option value="">Todas as Origens</option>
                    <option value="comunicado">Envio Manual/Campanha</option>
                    <option value="automacao">Automação de Sistema</option>
                </select>

                @if($filtro_status !== '' || $filtro_origem !== '')
                    <div class="md:col-span-12 flex justify-end mt-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button wire:click="limparFiltros" class="px-4 py-2 text-sm font-bold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-2 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            <i class="ph-bold ph-x"></i> Limpar Filtros
                        </button>
                    </div>
                @endif
            </div>
        </x-slot>

    </x-page-header>

    <div wire:poll.10s>
        <x-table 
            :headers="$this->headers" 
            :registros="$registros"
            :ordenacaoCampo="$ordenacaoCampo"
            :ordenacaoDirecao="$ordenacaoDirecao"
            :permiteGrid="$permiteGrid"
            :modoExibicao="$modoExibicao">
            
            @forelse($registros as $log)
                <tr class="hover:bg-gray-50 transition-colors duration-200">
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-500">
                        #{{ $log->id }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="text-sm font-bold text-gray-900">{{ $log->destinatario }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm font-bold text-gray-800 truncate max-w-xs" title="{{ $log->assunto }}">{{ $log->assunto }}</div>
                        <div class="text-[10px] uppercase font-bold text-gray-400 mt-0.5"><i class="ph ph-tag"></i> {{ $log->origem }}</div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="text-sm text-gray-700">
                            <i class="ph ph-calendar-plus text-gray-400"></i> {{ $log->data_agendamento ? $log->data_agendamento->format('d/m/Y H:i') : 'Imediato' }}
                        </div>
                        @if($log->data_envio)
                            <div class="text-xs font-bold text-green-600 mt-0.5">
                                <i class="ph-bold ph-check"></i> {{ $log->data_envio->format('d/m/Y H:i:s') }}
                            </div>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="px-2.5 py-1 text-[11px] font-bold rounded-full uppercase tracking-wider border 
                            {{ $log->status === 'pendente' ? 'bg-yellow-100 text-yellow-800 border-yellow-200' : '' }}
                            {{ $log->status === 'enviado' ? 'bg-green-100 text-green-800 border-green-200' : '' }}
                            {{ $log->status === 'erro' ? 'bg-red-100 text-red-800 border-red-200' : '' }}
                        ">
                            @if($log->status === 'pendente') <i class="ph ph-clock mr-1"></i> Fila/Agendado
                            @elseif($log->status === 'enviado') <i class="ph-bold ph-check mr-1"></i> Enviado
                            @elseif($log->status === 'erro') <i class="ph-bold ph-warning mr-1"></i> Erro
                            @endif
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-1">
                            @if($log->status === 'erro')
                                <button wire:click="verErro({{ $log->id }})" class="p-1.5 text-red-500 transition-colors rounded hover:bg-red-50" title="Ver Log de Erro">
                                    <i class="text-lg ph-fill ph-warning-circle"></i>
                                </button>
                            @endif
                            <button wire:click="verPreview({{ $log->id }})" class="p-1.5 text-blue-500 transition-colors rounded hover:bg-blue-50" title="Pré-visualizar E-mail">
                                <i class="text-lg ph-bold ph-eye"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-gray-500 text-sm border-t border-gray-100">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3 border border-gray-200">
                            <i class="ph ph-envelope-open text-3xl text-gray-400"></i>
                        </div>
                        <p class="font-bold text-gray-600">Fila de e-mails vazia.</p>
                    </td>
                </tr>
            @endforelse
        </x-table>
    </div>

    <!-- MODAL: PREVIEW DO E-MAIL -->
    @if($modalPreviewAberto && $logSelecionado)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/80 backdrop-blur-sm" wire:click="$set('modalPreviewAberto', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block w-full max-w-3xl overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-2xl sm:my-8 sm:align-middle">
                    
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                <i class="ph-fill ph-envelope-simple text-purpura-600"></i> Visualização de E-mail
                            </h3>
                            <button wire:click="$set('modalPreviewAberto', false)" class="text-gray-400 hover:text-red-500 transition"><i class="ph-bold ph-x text-xl"></i></button>
                        </div>
                        
                        <div class="space-y-1 text-sm bg-white p-3 rounded border border-gray-200 shadow-sm">
                            <p><span class="font-bold text-gray-500 w-16 inline-block">Para:</span> <span class="font-bold text-gray-900">{{ $logSelecionado->destinatario }}</span></p>
                            <p><span class="font-bold text-gray-500 w-16 inline-block">Assunto:</span> <span class="font-bold text-gray-900">{{ $logSelecionado->assunto }}</span></p>
                        </div>
                    </div>

                    <!-- PREVIEW REAL DO HTML RENDERIZADO -->
                    <div class="p-6 max-h-[60vh] overflow-y-auto bg-white prose max-w-none">
                        {!! $logSelecionado->corpo !!}
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                        <button type="button" wire:click="$set('modalPreviewAberto', false)" class="px-6 py-2 text-sm font-bold bg-gray-200 rounded-lg text-gray-700 hover:bg-gray-300 transition">Fechar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL: ERRO -->
    @if($modalErroAberto && $logSelecionado)
        <div class="fixed inset-0 z-[60] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="$set('modalErroAberto', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
                    <h3 class="mb-2 text-lg font-extrabold text-red-600 flex items-center gap-2 border-b border-gray-100 pb-3">
                        <i class="ph-fill ph-warning-circle text-2xl"></i> Falha no Disparo
                    </h3>
                    <p class="text-xs text-gray-500 font-medium mb-4">O provedor de e-mail retornou o seguinte erro para o destinatário <b>{{ $logSelecionado->destinatario }}</b>:</p>
                    
                    <div class="bg-gray-900 text-red-400 p-4 rounded-lg font-mono text-[11px] max-h-64 overflow-y-auto shadow-inner break-words whitespace-pre-wrap">
                        {{ $logSelecionado->erro_mensagem ?? 'Erro desconhecido retornado pelo servidor.' }}
                    </div>

                    <div class="flex justify-end gap-3 pt-4 mt-4 border-t border-gray-100">
                        <button type="button" wire:click="$set('modalErroAberto', false)" class="px-6 py-2 text-sm font-bold bg-gray-100 rounded-lg text-gray-700 hover:bg-gray-200 transition">Fechar Detalhes</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>