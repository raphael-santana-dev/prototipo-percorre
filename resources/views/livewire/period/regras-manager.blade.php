<div class="p-6 max-w-[1400px] mx-auto h-full flex flex-col font-sans relative" wire:poll.5s="pingLock">
    
    {{-- CORTINA DE BLOQUEIO: OUTRO UTILIZADOR A EDITAR --}}
    @if($isLockedByOther)
        <div class="fixed inset-0 z-[200] bg-white/60 dark:bg-gray-900/70 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-2xl max-w-md w-full border border-gray-200 dark:border-gray-700 text-center relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-amber-500"></div>
                
                <div class="w-20 h-20 bg-amber-100 dark:bg-amber-900/30 text-amber-500 rounded-full flex items-center justify-center mx-auto mb-5">
                    <i class="ph-fill ph-lock-key text-4xl"></i>
                </div>
                
                <h3 class="text-xl font-extrabold text-gray-900 dark:text-white mb-2">Edição em Andamento</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-6 leading-relaxed">
                    O utilizador <strong>{{ $lockOwnerName }}</strong> está atualmente a editar as regras de pontuação deste ciclo. Para evitar conflitos, a edição simultânea está bloqueada.
                </p>

                @if($transferRejected)
                    <div class="mb-6 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-xs font-bold text-red-700 dark:text-red-400">
                        <i class="ph-fill ph-x-circle mr-1 text-base align-middle"></i>
                        O utilizador recusou a transferência e pediu para aguardar.
                    </div>
                @endif

                <div class="flex flex-col gap-3">
                    @php
                        $transferData = \Illuminate\Support\Facades\Cache::get($this->getTransferKey());
                        $isPending = $transferData && ($transferData['status'] ?? '') === 'pending' && ($transferData['requester_id'] ?? null) === auth()->id();
                    @endphp

                    @if($isPending)
                        <button type="button" class="py-2.5 px-4 rounded-lg font-bold text-sm bg-gray-100 text-gray-500 cursor-not-allowed w-full flex justify-center items-center gap-2 border border-gray-200" disabled>
                            <i class="ph-bold ph-spinner animate-spin"></i> Aguardando resposta...
                        </button>
                        <button type="button" wire:click="cancelTransferRequest" class="text-xs font-bold text-gray-500 hover:text-red-500 underline mt-2 transition">
                            Cancelar Pedido
                        </button>
                    @else
                        <button type="button" wire:click="requestControl" class="py-2.5 px-4 rounded-lg font-bold text-sm bg-amber-500 hover:bg-amber-600 text-white border-none shadow-md w-full flex justify-center items-center gap-2 transition">
                            <i class="ph-bold ph-hand-raising"></i> Solicitar Controlo
                        </button>
                        <a href="{{ route('ciclos.index') }}" class="py-2.5 px-4 rounded-lg font-bold text-sm bg-gray-200 hover:bg-gray-300 text-gray-700 w-full mt-2 block transition">
                            Voltar para Ciclos
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <fieldset @if($isLockedByOther) disabled class="pointer-events-none opacity-60 blur-[2px] transition-all duration-300" @endif>
        <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center border-b border-gray-200 pb-4 gap-4">
            <div>
                <a href="{{ route('ciclos.index') }}" class="text-indigo-600 hover:text-indigo-800 transition text-xs mb-1 inline-flex items-center gap-1 font-bold uppercase tracking-wider">
                    <i class="ph ph-arrow-left"></i> Voltar para Ciclos
                </a>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">Regras de Pontuação</h2>
                <p class="text-gray-500 text-sm">Configurando pontuação automatizada para o ciclo <span class="font-bold text-purpura-600">{{ $ciclo->nome }}</span>.</p>
            </div>
            @if(feature('ciclo.regras') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.regras')))
                <div class="flex items-center gap-3">
                    <button wire:click="salvar" class="flex items-center gap-2 px-5 py-2.5 text-white transition-colors rounded-lg shadow-sm bg-purpura-600 hover:bg-purpura-700 font-bold text-sm">
                        <i class="ph-bold ph-floppy-disk text-lg"></i> Salvar Regras
                    </button>
                </div>
            @endif
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            
            <div class="hidden md:flex items-center gap-3 px-4 py-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 text-[10px] font-bold text-gray-500 uppercase tracking-wider">
                <div class="w-6 text-center">#</div>
                <div class="w-40">Tipo de Regra</div>
                <div class="flex-1">Campo / Aplicação</div>
                <div class="w-32">Condição</div>
                <div class="w-48">Valor Esperado</div>
                <div class="w-24 text-center">Pontos / %</div>
                <div class="w-10"></div>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($regras as $index => $regra)
                    @php
                        $tipo = $regra['tipo_regra'] ?? 'padrao';
                        $isEspecial = $tipo !== 'padrao';
                        $escopo = $regra['escopo'] ?? 'especifico';
                        $isGlobal = $isEspecial && $escopo === 'todos';
                    @endphp

                    <div class="flex flex-col md:flex-row items-start gap-3 py-3 px-4 hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors {{ $isEspecial ? 'bg-indigo-50/20 dark:bg-indigo-900/10' : '' }}" wire:key="regra-{{ $index }}">
                        
                        <div class="hidden md:block w-6 text-xs font-black text-gray-300 text-center mt-1.5">
                            {{ $index + 1 }}
                        </div>

                        <div class="w-full md:w-40 shrink-0">
                            <select wire:model.live="regras.{{ $index }}.tipo_regra" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded text-xs focus:ring-purpura-500 focus:border-purpura-500 py-1.5 shadow-sm {{ $isEspecial ? 'bg-indigo-50 text-indigo-700 font-bold' : 'bg-gray-50' }} @error("regras.$index.tipo_regra") border-red-500 bg-red-50 text-red-700 @enderror">
                                <option value="padrao">Padrão (+ Pts)</option>
                                <option value="bonus_por_acerto">Bônus Acerto</option>
                                <option value="multiplicador_percentual">Multiplicador (%)</option>
                            </select>
                            @error("regras.$index.tipo_regra") <span class="text-[9px] text-red-500 font-bold mt-1 block leading-tight">{{ $message }}</span> @enderror
                        </div>

                        @if($isGlobal)
                            <div class="w-full md:flex-1 text-xs font-bold text-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 px-3 py-1.5 rounded flex items-center gap-2">
                                <i class="ph-fill ph-globe-hemisphere-east text-base"></i> Aplica-se incondicionalmente a todos os campos.
                            </div>
                        @else
                            @if($isEspecial)
                                <div class="w-full md:w-32 shrink-0">
                                    <select wire:model.live="regras.{{ $index }}.escopo" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded text-xs bg-gray-50 focus:ring-purpura-500 focus:border-purpura-500 py-1.5 shadow-sm @error("regras.$index.escopo") border-red-500 bg-red-50 text-red-700 @enderror">
                                        <option value="especifico">Específico</option>
                                        <option value="todos">Global</option>
                                    </select>
                                </div>
                            @endif
                            
                            <div class="w-full md:flex-1">
                                <select wire:model="regras.{{ $index }}.campo" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded text-xs bg-gray-50 focus:ring-purpura-500 focus:border-purpura-500 py-1.5 shadow-sm @error("regras.$index.campo") border-red-500 bg-red-50 text-red-700 @enderror">
                                    <option value="">Qual campo será avaliado?</option>
                                    @foreach($camposDisponiveis as $campo)
                                        <option value="{{ $campo['name'] }}">{{ Str::limit($campo['label'], 40) }}</option>
                                    @endforeach
                                </select>
                                @error("regras.$index.campo") <span class="text-[9px] text-red-500 font-bold mt-1 block leading-tight">{{ $message }}</span> @enderror
                            </div>

                            <div class="w-full md:w-32 shrink-0">
                                <select wire:model="regras.{{ $index }}.operador" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded text-xs bg-gray-50 focus:ring-purpura-500 focus:border-purpura-500 py-1.5 shadow-sm @error("regras.$index.operador") border-red-500 bg-red-50 text-red-700 @enderror">
                                    <option value="=">Igual a</option>
                                    <option value="!=">Diferente de</option>
                                    <option value=">">Maior que</option>
                                    <option value="<">Menor que</option>
                                    <option value=">=">Maior ou igual a</option>
                                    <option value="<=">Menor ou igual a</option>
                                    <option value="between">Entre valores</option>
                                    <option value="in">Dentre opções</option>
                                </select>
                            </div>

                            <div class="w-full md:w-48 shrink-0">
                                <input type="text" wire:model="regras.{{ $index }}.valor" placeholder="Ex: sim, não" class="w-full border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded text-xs bg-white focus:ring-purpura-500 focus:border-purpura-500 py-1.5 shadow-sm @error("regras.$index.valor") border-red-500 bg-red-50 text-red-700 @enderror">
                                @error("regras.$index.valor") <span class="text-[9px] text-red-500 font-bold mt-1 block leading-tight">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        <div class="w-full md:w-24 shrink-0 flex flex-col md:block">
                            <div class="relative flex items-center gap-2 md:block">
                                <span class="md:hidden text-[10px] font-bold text-gray-500 uppercase">Valor:</span>
                                <input type="number" step="0.1" wire:model="regras.{{ $index }}.pontos" placeholder="0" class="w-full border-gray-200 dark:border-gray-600 rounded text-xs font-bold text-center focus:ring-purpura-500 py-1.5 shadow-sm {{ $isEspecial ? 'bg-indigo-100 text-indigo-700' : 'bg-green-50 text-green-700' }} @error("regras.$index.pontos") border-red-500 bg-red-50 text-red-700 @enderror">
                                @if($tipo === 'multiplicador_percentual')
                                    <span class="absolute right-3 top-[7px] text-[10px] font-black text-indigo-400">%</span>
                                @endif
                            </div>
                            @error("regras.$index.pontos") <span class="text-[9px] text-red-500 font-bold mt-1 block leading-tight">{{ $message }}</span> @enderror
                        </div>

                        @if(feature('ciclo.regras') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.regras')))
                            <div class="w-full md:w-10 shrink-0 text-right md:text-center mt-1 md:mt-0">
                                <button type="button" wire:click="removeRegra({{ $index }})" class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-1.5 rounded transition" title="Excluir">
                                    <i class="ph-bold ph-trash text-base"></i>
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="p-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 flex justify-center">
                @if(feature('ciclo.regras') && (auth()->user()->hasRole('dev') || auth()->user()->can('ciclo.regras')))
                    <button type="button" wire:click="addRegra" class="text-sm font-bold text-purpura-600 hover:text-purpura-800 transition flex items-center gap-1">
                        <i class="ph-bold ph-plus"></i> Adicionar Nova Regra
                    </button>
                @endif
            </div>
        </div>
    </fieldset>

    {{-- POPUP DE SOLICITAÇÃO RECEBIDA (PARA O DONO DO LOCK) --}}
    @if(!$isLockedByOther && $hasTransferRequest)
        <div class="fixed bottom-6 right-6 z-[300] max-w-sm w-full bg-white dark:bg-gray-800 border-l-4 border-purpura-500 rounded-lg shadow-2xl p-5 animate-fade-in-up">
            <div class="flex items-start gap-4">
                <div class="p-2 bg-purpura-100 dark:bg-purpura-900/30 text-purpura-600 rounded-full shrink-0">
                    <i class="ph-fill ph-hand-waving text-xl"></i>
                </div>
                <div>
                    <h4 class="text-sm font-extrabold text-gray-900 dark:text-white">Pedido de Edição</h4>
                    <p class="text-xs text-gray-600 dark:text-gray-300 mt-1 leading-relaxed">
                        O utilizador <strong>{{ $requesterName }}</strong> abriu esta página e está a solicitar o controlo para editar as regras.
                    </p>
                </div>
            </div>
            <div class="mt-4 flex gap-2 justify-end">
                <button type="button" wire:click="rejectTransfer" class="px-3 py-1.5 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300 text-xs font-bold transition">
                    Recusar e Continuar
                </button>
                <button type="button" wire:click="acceptTransfer" class="px-3 py-1.5 rounded-md bg-purpura-600 hover:bg-purpura-700 text-white border-none text-xs font-bold transition">
                    Aceitar e Sair
                </button>
            </div>
        </div>
    @endif
</div>