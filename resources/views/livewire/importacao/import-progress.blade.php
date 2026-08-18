{{-- O poll.3s faz o componente se atualizar sozinho a cada 3 segundos --}}
<div wire:poll.3s class="relative flex items-center">
    
    @if($totalAtivas > 0)
        <!-- MODO ATIVO: Processos rodando na Fila -->
        <a href="{{ route('importacoes.index') }}" class="flex items-center gap-2 px-3 py-1.5 bg-blue-50 border border-blue-200 rounded-full hover:bg-blue-100 transition-colors group">
            <i class="ph ph-arrows-clockwise text-blue-600 animate-spin text-lg"></i>
            <span class="text-xs font-bold text-blue-700 hidden sm:inline-block">
                {{ $totalAtivas }} processo(s)
            </span>
            
            <!-- TOOLTIP DE PREVIEW (Aparece no Hover) -->
            <div class="hidden group-hover:block absolute top-full right-0 mt-3 w-64 bg-white border border-gray-200 shadow-xl rounded-xl p-4 z-50">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-3">Em andamento na Nuvem</p>
                
                <div class="space-y-3">
                    @foreach($ativas as $proc)
                        <div>
                            <div class="flex justify-between text-[11px] font-bold text-gray-700 mb-1">
                                <span class="truncate pr-2" title="{{ $proc->arquivo_nome }}">{{ Str::limit($proc->arquivo_nome ?? 'Processamento', 20) }}</span>
                                <span class="text-blue-600">{{ $proc->progresso }}%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-blue-500 h-1.5 rounded-full transition-all duration-500" style="width: {{ $proc->progresso }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div class="mt-3 pt-3 border-t border-gray-100 text-center">
                    <span class="text-[10px] font-bold text-purpura-600">Clique para abrir o Gerenciador</span>
                </div>
            </div>
        </a>
    @else
        <!-- MODO OCIOSO: Apenas o ícone para acesso rápido -->
        <a href="{{ route('importacoes.index') }}" class="flex items-center justify-center w-9 h-9 text-gray-400 hover:text-purpura-600 hover:bg-purpura-50 transition-colors rounded-full" title="Gerenciador de Integrações">
            <i class="ph ph-arrows-left-right text-xl"></i>
        </a>
    @endif

</div>