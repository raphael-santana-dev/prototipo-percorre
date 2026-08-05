<div class="p-6 h-[calc(100vh-80px)] flex flex-col font-sans relative">

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 8px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
        .custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: #94a3b8; }
    </style>
    
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

    <!-- HEADER DO CRM -->
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 shrink-0">
        <div>
            <a href="{{ route('ciclos.show', $ciclo->id) }}" class="text-purpura-600 hover:text-purpura-800 transition text-sm mb-1 inline-flex items-center gap-1 font-bold">
                <i class="ph ph-arrow-left"></i> Voltar para Visão Resumida
            </a>
            <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <i class="ph-fill ph-kanban text-purpura-500"></i> Painel CRM: {{ $ciclo->nome }}
            </h2>
        </div>

        <!-- BARRA AÇÃO EM LOTE -->
        <div x-data="{ count: @entangle('selecionados').live }" x-show="count.length > 0" x-cloak class="flex items-center gap-3 bg-white p-2 rounded-lg shadow-md border border-purpura-200">
            <span class="text-sm font-bold text-purpura-700 ml-2">
                <span x-text="count.length"></span> registros
            </span>
            <select wire:model="statusDestinoLote" class="text-sm border-gray-300 rounded-md py-1.5 focus:ring-purpura-500">
                <option value="">Mover para coluna...</option>
                @foreach($colunas as $col)
                    <option value="{{ $col->id }}">{{ $col->nome }}</option>
                @endforeach
            </select>
            <button wire:click="moverLote" class="bg-purpura-600 text-white px-4 py-1.5 rounded-md text-sm font-bold shadow-sm hover:bg-purpura-700 transition">
                Mover Todos
            </button>
        </div>
    </div>

    <!-- AREA DO KANBAN -->
    <div class="flex-1 overflow-x-auto overflow-y-hidden custom-scrollbar pb-4"
         x-data="{
             initSortable() {
                 document.querySelectorAll('.kanban-coluna').forEach(el => {
                     new Sortable(el, {
                         group: 'crm-pipeline', // Permite arrastar para outras colunas
                         animation: 150,
                         ghostClass: 'opacity-50', // Efeito visual no card flutuante
                         onEnd: (evt) => {
                             let inscricaoId = evt.item.dataset.id;
                             let novoStatusId = evt.to.dataset.status;
                             // Notifica o Livewire se mudou de coluna
                             if(evt.from !== evt.to) {
                                 @this.atualizarStatus(inscricaoId, novoStatusId);
                             }
                         }
                     });
                 });
             }
         }" x-init="initSortable()">
         
        <div class="flex h-full gap-4 items-start w-max px-1">
            @foreach($colunas as $coluna)
                <!-- COLUNA -->
                <div class="w-80 flex flex-col max-h-full bg-gray-100/50 border border-gray-200 rounded-xl overflow-hidden shrink-0">
                    
                    <div class="p-3 bg-gray-100 border-b border-gray-200 flex justify-between items-center shrink-0">
                        <h3 class="font-bold text-gray-700 text-xs uppercase tracking-wide">{{ $coluna->nome }}</h3>
                        <span class="bg-white border border-gray-200 text-gray-600 text-xs font-bold px-2 py-0.5 rounded shadow-sm">
                            {{ isset($inscricoesGrupadas[$coluna->id]) ? count($inscricoesGrupadas[$coluna->id]) : 0 }}
                        </span>
                    </div>

                    <!-- DROPZONE -->
                    <div class="p-3 flex-1 overflow-y-auto custom-scrollbar kanban-coluna space-y-3 min-h-[150px]" data-status="{{ $coluna->id }}">
                        @if(isset($inscricoesGrupadas[$coluna->id]))
                            @foreach($inscricoesGrupadas[$coluna->id] as $inscricao)
                                
                                <!-- CARD DO ALUNO -->
                                <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 cursor-grab active:cursor-grabbing hover:border-purpura-400 hover:shadow-md transition group" data-id="{{ $inscricao->id }}">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox" wire:model.live="selecionados" value="{{ $inscricao->id }}" class="rounded text-purpura-600 border-gray-300 w-4 h-4 cursor-pointer" onmousedown="event.stopPropagation()">
                                            <span class="text-[10px] font-bold text-gray-400 font-mono">#{{ str_pad($inscricao->id, 4, '0', STR_PAD_LEFT) }}</span>
                                        </div>
                                        <a href="{{ route('inscricoes.show', $inscricao->id) }}" target="_blank" class="text-gray-400 hover:text-purpura-600 transition" onmousedown="event.stopPropagation()">
                                            <i class="ph-bold ph-arrow-square-out"></i>
                                        </a>
                                    </div>
                                    <h4 class="font-bold text-gray-900 text-sm truncate" title="{{ $inscricao->nome }}">{{ $inscricao->nome }}</h4>
                                    <div class="mt-3 text-xs font-bold text-gray-400 flex items-center gap-1">
                                        <i class="ph-fill ph-clock"></i> {{ $inscricao->updated_at->diffForHumans() }}
                                    </div>
                                </div>

                            @endforeach
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

