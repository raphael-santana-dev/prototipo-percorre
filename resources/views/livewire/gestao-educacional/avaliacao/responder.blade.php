<div class="p-6 max-w-[1400px] mx-auto font-sans relative">
    
    <x-page-header 
        title="Matriz de Avaliação" 
        icon="ph ph-exam"
        badge="Formulário">
        
        <x-slot name="actions">
            <a href="{{ route('avaliacoes.index') }}" wire:navigate class="px-4 py-2 text-sm font-bold border rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 flex items-center gap-2">
                <i class="ph-bold ph-arrow-left"></i> Voltar
            </a>
        </x-slot>
    </x-page-header>

    {{-- CABEÇALHO DO ALUNO E MÉDIAS --}}
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <p class="text-xl font-black text-purpura-600 dark:text-purpura-400">{{ $alunoNome }}</p>
            <p class="text-sm font-bold text-gray-500">{{ $turmaNome }} <span class="mx-2">|</span> {{ $periodoNome }}</p>
        </div>
        
        <div class="flex gap-2">
            <div class="bg-indigo-50 border border-indigo-100 dark:bg-indigo-900/30 dark:border-indigo-800 rounded-lg px-4 py-2 shadow-sm text-center min-w-[120px]">
                <span class="text-[9px] text-indigo-500 dark:text-indigo-400 font-black uppercase tracking-wider block">Média Parcial (F1+F2)</span>
                <span class="text-2xl font-black text-indigo-700 dark:text-indigo-300 leading-none">{{ $mediaParcial }}</span>
            </div>
            <div class="bg-purpura-50 border border-purpura-100 dark:bg-purpura-900/30 dark:border-purpura-800 rounded-lg px-4 py-2 shadow-sm text-center min-w-[120px]">
                <span class="text-[9px] text-purpura-600 dark:text-purpura-400 font-black uppercase tracking-wider block">Média Final (F3)</span>
                <span class="text-2xl font-black text-purpura-700 dark:text-purpura-300 leading-none">{{ $mediaFinal }}</span>
            </div>
        </div>
    </div>

    <form wire:submit.prevent="salvar">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                            <th class="p-4 font-black text-gray-700 dark:text-gray-300 w-1/4 uppercase tracking-wider text-sm sticky left-0 bg-gray-50 dark:bg-gray-900 z-10 shadow-[1px_0_0_0_#e5e7eb] dark:shadow-[1px_0_0_0_#374151]">
                                Critério Avaliado
                            </th>
                            @foreach($avaliacoesFases as $avFase)
                                <th class="p-4 text-center border-l border-gray-200 dark:border-gray-700 {{ ($permissoesFase[$avFase->fase] ?? false) ? 'text-purpura-800 bg-purpura-50/50 dark:text-purpura-300 dark:bg-purpura-900/20' : 'text-gray-400 dark:text-gray-500' }}">
                                    <span class="font-black">FASE {{ $avFase->fase }}</span>
                                    
                                    <span class="block text-[10px] font-bold mt-1 uppercase text-gray-500">
                                        Resp: {{ $responsaveisDesc[$avFase->fase] ?? '' }}
                                    </span>
                                    
                                    <span class="block text-[10px] {{ $avFase->status == '2' ? 'text-green-600' : 'text-orange-500' }} font-bold mt-1">
                                        {{ $avFase->status == '2' ? 'CONCLUÍDA' : 'PENDENTE' }}
                                    </span>

                                    {{-- BOTÃO: Solicitar Alteração (Exclusivo Aluno) --}}
                                    @if(auth()->guard('student')->check() && $avFase->status == '2')
                                        @if($solicitacoesPendentes[$avFase->fase] ?? false)
                                            <span class="mt-2 inline-block px-2 py-1 bg-yellow-100 text-yellow-800 text-[9px] rounded font-bold uppercase dark:bg-yellow-900/30 dark:text-yellow-500">Em Análise</span>
                                        @else
                                            <button type="button" wire:click="abrirModalSolicitacao({{ $avFase->id }}, {{ $avFase->fase }})" class="mt-2 text-[10px] text-purpura-600 hover:text-purpura-800 font-bold underline transition">
                                                Solicitar Alteração
                                            </button>
                                        @endif
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($criterios as $criterio)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition">
                                <td class="p-4 font-bold text-gray-800 dark:text-white text-sm align-top sticky left-0 bg-white dark:bg-gray-800 z-10 shadow-[1px_0_0_0_#e5e7eb] dark:shadow-[1px_0_0_0_#374151]">
                                    {{ $criterio->nome }}
                                </td>
                                
                                @foreach($avaliacoesFases as $avFase)
                                    @php $podeEditar = $permissoesFase[$avFase->fase] ?? false; @endphp
                                    <td class="p-4 border-l border-gray-100 dark:border-gray-700 align-top {{ $podeEditar ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-900' }}">
                                        
                                        <div class="mb-3 flex flex-col items-center">
                                            <label class="block text-[10px] font-bold {{ $podeEditar ? 'text-gray-500' : 'text-gray-500' }} uppercase mb-1">Nota NPS (0-10)</label>
                                            
                                            @if($podeEditar)
                                                <input type="number" min="0" max="10" step="1" 
                                                       wire:model="nps.{{ $criterio->id }}.{{ $avFase->fase }}" 
                                                       class="w-20 border border-gray-300 dark:border-gray-600 rounded-md font-black text-center text-purpura-700 dark:text-purpura-400 bg-white dark:bg-gray-700 focus:ring-purpura-500 focus:border-purpura-500 shadow-sm py-1.5 outline-none transition">
                                            @else
                                                <div class="w-20 py-1.5 border border-transparent rounded-md font-black text-center text-gray-600 dark:text-gray-400 bg-transparent text-lg">
                                                    {{ $nps[$criterio->id][$avFase->fase] ?? '-' }}
                                                </div>
                                            @endif
                                            @error("nps.{$criterio->id}.{$avFase->fase}") <span class="text-red-500 text-[10px] font-bold mt-1">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="mt-2">
                                            <label class="block text-[10px] font-bold {{ $podeEditar ? 'text-gray-500' : 'text-gray-500' }} uppercase mb-1">Autoavaliação e Metas</label>
                                            
                                            @if($podeEditar)
                                                <textarea rows="3" 
                                                          wire:model="metas.{{ $criterio->id }}.{{ $avFase->fase }}" 
                                                          placeholder="{{ $motivosBloqueio[$avFase->fase] ?? 'Descreva observações...' }}"
                                                          class="w-full border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-purpura-500 focus:border-purpura-500 shadow-sm resize-y p-2.5 outline-none transition"></textarea>
                                            @else
                                                <div class="w-full text-sm text-gray-600 dark:text-gray-400 italic leading-relaxed whitespace-pre-line p-2">
                                                    {{ $metas[$criterio->id][$avFase->fase] ?? 'Nenhuma observação registrada.' }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($podeEditarGeral)
                <div class="p-6 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                    <button type="submit" class="px-8 py-3 bg-purpura-600 hover:bg-purpura-700 text-white font-black rounded-lg shadow-sm transition flex items-center gap-2">
                        <i class="ph-bold ph-floppy-disk text-lg"></i>
                        Salvar Respostas
                    </button>
                </div>
            @endif
        </div>
    </form>

    {{-- MODAL DE SOLICITAÇÃO --}}
    @if($modalSolicitacao)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="$set('modalSolicitacao', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white dark:bg-gray-800 rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <h3 class="mb-4 text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-2">
                        Solicitar Alteração - Fase {{ $faseNumero }}
                    </h3>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">1. Selecione os Critérios que deseja alterar:</label>
                        <div class="space-y-2 max-h-40 overflow-y-auto p-3 border border-gray-200 dark:border-gray-600 rounded bg-gray-50 dark:bg-gray-900">
                            @foreach($criterios as $crit)
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="checkbox" wire:model="criteriosSelecionados" value="{{ $crit->id }}" class="rounded text-purpura-600 focus:ring-purpura-500 dark:bg-gray-700 border-gray-300 dark:border-gray-600">
                                    <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $crit->nome }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('criteriosSelecionados') <span class="text-red-500 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">2. Descreva o motivo da solicitação:</label>
                        <textarea wire:model="motivoTexto" rows="4" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded focus:ring-purpura-500 focus:border-purpura-500" placeholder="Ex: Avaliei minha nota incorretamente no quesito colaboração porque..."></textarea>
                        @error('motivoTexto') <span class="text-red-500 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="flex justify-end gap-3 pt-6 mt-4 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" wire:click="$set('modalSolicitacao', false)" class="px-4 py-2 text-sm font-bold border rounded-lg text-gray-600 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 transition">Cancelar</button>
                        <button type="button" wire:click="enviarSolicitacao" wire:loading.attr="disabled" class="px-6 py-2 text-sm font-bold text-white rounded-lg shadow-sm bg-purpura-600 hover:bg-purpura-700 transition flex items-center gap-2">
                            <span wire:loading.remove>Enviar para o Professor</span>
                            <span wire:loading>Enviando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- TOAST --}}
    <div x-data="{ show: false, msg: '' }" @sucesso.window="show = true; msg = $event.detail.msg; setTimeout(() => show = false, 3500);" x-show="show" x-transition class="fixed bottom-8 right-8 bg-green-600 text-white px-6 py-4 rounded-xl shadow-2xl z-[200] flex items-center gap-3 font-bold" x-cloak>
        <i class="text-2xl ph ph-check-circle"></i> <span x-text="msg"></span>
    </div>
</div>