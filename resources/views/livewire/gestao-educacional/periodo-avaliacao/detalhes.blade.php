<div class="p-6 max-w-5xl mx-auto font-sans relative">
    
    <x-page-header 
        title="{{ $periodoId ? 'Configurar Ciclo' : 'Novo Ciclo de Avaliação' }}" 
        icon="ph ph-gear"
        badge="Administração">
        
        <x-slot name="actions">
            <a href="{{ route('avaliacoes.periodos.index') }}" wire:navigate class="px-4 py-2 text-sm font-bold border rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 flex items-center gap-2">
                <i class="ph-bold ph-arrow-left"></i> Voltar
            </a>
        </x-slot>
    </x-page-header>

    @if($avaliacoesGeradas)
        <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r shadow-sm dark:bg-blue-900/20 dark:border-blue-400">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="ph-fill ph-info text-blue-500 text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700 dark:text-blue-300 font-bold">Matrizes em Andamento</p>
                    <p class="text-xs text-blue-600 mt-1 dark:text-blue-400">Como já existem avaliações atreladas a este período, a alteração de critérios e exclusão de fases foi bloqueada para manter a integridade do banco de dados.</p>
                </div>
            </div>
        </div>
    @endif

    <form wire:submit.prevent="salvar" class="space-y-6">
        
        {{-- CABEÇALHO DO PERÍODO --}}
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold border-b border-gray-100 dark:border-gray-700 pb-2 mb-4 text-gray-800 dark:text-gray-200">Datas e Regras Globais</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Ano Referência</label>
                    <input type="text" wire:model="ano" disabled class="w-full border-transparent bg-gray-100 dark:bg-gray-900 rounded-lg font-black text-gray-600 dark:text-gray-400 focus:ring-0 cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Ciclo Numérico</label>
                    <input type="text" wire:model="ciclo" disabled class="w-full border-transparent bg-gray-100 dark:bg-gray-900 rounded-lg font-black text-gray-600 dark:text-gray-400 focus:ring-0 cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Início das Respostas</label>
                    <input type="date" wire:model="data_inicio" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm text-sm">
                    @error('data_inicio') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Término do Período</label>
                    <input type="date" wire:model="data_fim" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm text-sm">
                    @error('data_fim') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Status de Visibilidade</label>
                    <select wire:model="status" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm font-bold text-purpura-700 dark:text-purpura-400 text-sm">
                        <option value="1">1 - Aberto aos Alunos/Professores</option>
                        <option value="2">2 - Fechado (Apenas Leitura)</option>
                    </select>
                    @error('status') <span class="text-red-500 text-xs block mt-1 font-bold">{{ $message }}</span> @enderror
                </div>
                
                {{-- TOGGLE DE TRAVA DE FASES --}}
                <div class="col-span-1 md:col-span-3 pt-4 border-t border-gray-100 dark:border-gray-700 md:border-none md:pt-0 flex items-center">
                    <label class="flex items-center cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" wire:model="trava_fases" class="sr-only">
                            <div class="block {{ $trava_fases ? 'bg-purpura-600' : 'bg-gray-200 dark:bg-gray-700' }} w-10 h-6 rounded-full transition"></div>
                            <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition {{ $trava_fases ? 'transform translate-x-4' : '' }}"></div>
                        </div>
                        <div class="ml-3">
                            <span class="block text-sm font-bold text-gray-800 dark:text-gray-200">Travar Fases Sequencialmente</span>
                            <span class="block text-[10px] text-gray-500">Exige a conclusão da Fase 1 para liberar a Fase 2, e assim por diante.</span>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            {{-- CRITÉRIOS --}}
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 h-full flex flex-col">
                <h3 class="text-lg font-bold border-b border-gray-100 dark:border-gray-700 pb-2 mb-4 text-gray-800 dark:text-gray-200 flex justify-between items-center">
                    Critérios Aplicados
                    @error('criterios_selecionados') <span class="text-red-500 text-[10px] font-bold uppercase">{{ $message }}</span> @enderror
                </h3>
                
                <div class="space-y-2 flex-1 overflow-y-auto pr-2 custom-scrollbar" style="max-height: 400px;">
                    @foreach($criteriosDisponiveis as $crit)
                        <label class="flex items-start space-x-3 p-3 border border-gray-100 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-900 transition {{ $avaliacoesGeradas ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer' }}">
                            <input type="checkbox" wire:model="criterios_selecionados" value="{{ $crit->id }}" 
                                   class="mt-0.5 rounded text-purpura-600 focus:ring-purpura-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                   @if($avaliacoesGeradas) disabled @endif>
                            <div>
                                <span class="block text-sm font-bold text-gray-800 dark:text-gray-200">{{ $crit->nome }}</span>
                                <span class="block text-[10px] font-mono text-gray-400">Cód: {{ $crit->codigo }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- FASES --}}
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 h-full flex flex-col">
                <h3 class="text-lg font-bold border-b border-gray-100 dark:border-gray-700 pb-2 mb-4 text-gray-800 dark:text-gray-200 flex justify-between items-center">
                    Workflow de Fases
                    @if(!$avaliacoesGeradas)
                        <button type="button" wire:click="adicionarFase" class="text-[10px] font-black uppercase bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 px-3 py-1.5 rounded hover:bg-indigo-100 transition shadow-sm">+ Adicionar Fase</button>
                    @endif
                </h3>
                
                @error('fases') <span class="text-red-500 text-xs mb-3 block font-bold">{{ $message }}</span> @enderror

                <div class="space-y-4 flex-1">
                    @foreach($fases as $index => $fase)
                        <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm">
                            <div class="w-16">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Fase</label>
                                <input type="text" value="{{ $fase['fase'] }}" disabled class="w-full text-center border-transparent bg-white dark:bg-gray-800 rounded font-black text-gray-600 dark:text-gray-300 shadow-inner">
                            </div>
                            <div class="flex-1">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Responsável</label>
                                <select wire:model="fases.{{ $index }}.responsavel" 
                                        class="w-full border-gray-300 dark:border-gray-600 rounded focus:ring-purpura-500 shadow-sm text-sm dark:bg-gray-800 dark:text-white"
                                        @if($avaliacoesGeradas) disabled class="opacity-70 cursor-not-allowed" @endif>
                                    <option value="1">Apenas o Estudante (Autoavaliação)</option>
                                    <option value="2">Apenas o Professor</option>
                                    <option value="3">Ambos (Estudante e Professor)</option>
                                </select>
                            </div>
                            
                            @if(!$avaliacoesGeradas && count($fases) > 1)
                                <div class="pt-5">
                                    <button type="button" wire:click="removerFase({{ $index }})" class="text-red-500 hover:text-red-700 transition bg-white shadow-sm rounded-lg p-2 border border-gray-200 dark:bg-gray-800 dark:border-gray-700" title="Remover Fase">
                                        <i class="ph-bold ph-trash text-lg"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-4">
            @if(feature('periodo_avaliacao.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('periodo_avaliacao.editar')))
                <button type="submit" class="px-8 py-3 bg-purpura-600 hover:bg-purpura-700 text-white font-black rounded-lg shadow-sm transition flex items-center gap-2">
                    <i class="ph-bold ph-floppy-disk text-lg"></i> Salvar Configuração
                </button>
            @endif
        </div>
    </form>
</div>