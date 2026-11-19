@if($modalCondicoesAvancadasAberto)
    @php
        $diagModal       = $this->diagnosticoSimulacaoCondicional;
        $camposParaCond  = $this->camposDisponiveisParaCondicao;
        $operadoresLista = $this->operadoresCondicionais;
    @endphp
    <template x-teleport="body">
        <div class="fixed inset-0 z-[999] flex items-center justify-center bg-black/50 p-3 sm:p-4 font-sans"
             @keydown.escape.window="$wire.set('modalCondicoesAvancadasAberto', false)"
             role="dialog"
             aria-modal="true">
            
            <div class="bg-white dark:bg-gray-900 rounded-lg w-full max-w-5xl border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-[92vh]">
                
                {{-- CABEÇALHO --}}
                <div class="px-5 py-3.5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between gap-3 bg-white dark:bg-gray-900 shrink-0">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                Condições de Visibilidade
                            </h3>
                            <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 text-xs truncate max-w-[260px]">
                                {{ strip_tags($label) ?: ($name ?: 'Novo Campo') }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Configure quando este campo será exibido utilizando grupos lógicos E (AND) e OU (OR).
                        </p>
                    </div>

                    <button type="button" wire:click="$set('modalCondicoesAvancadasAberto', false)" class="text-gray-400 hover:text-gray-700 dark:hover:text-white p-1 rounded transition shrink-0" aria-label="Fechar">
                        <i class="ph ph-x text-lg"></i>
                    </button>
                </div>

                {{-- CORPO EM 2 COLUNAS (RESPONSIVO NO MOBILE/TABLET) --}}
                <div class="flex-1 overflow-y-auto lg:overflow-hidden flex flex-col lg:flex-row divide-y lg:divide-y-0 lg:divide-x divide-gray-200 dark:divide-gray-800">
                    
                    {{-- COLUNA ESQUERDA: GRUPOS DE CONDIÇÕES --}}
                    <div class="flex-1 lg:overflow-y-auto p-4 sm:p-5 space-y-4 custom-scrollbar bg-gray-50 dark:bg-gray-950">
                        
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                Grupos ({{ count($gruposCondicionais) }})
                            </span>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <button type="button" wire:click="adicionarGrupoCondicional('AND')" class="px-2.5 py-1.5 bg-white dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-700 rounded-md text-xs font-medium transition">
                                    + Grupo E
                                </button>
                                <button type="button" wire:click="adicionarGrupoCondicional('OR')" class="px-2.5 py-1.5 bg-white dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-700 rounded-md text-xs font-medium transition">
                                    + Grupo OU
                                </button>
                            </div>
                        </div>

                        @foreach($gruposCondicionais as $gIndex => $grupo)
                            @php
                                $grupoPassou = $diagModal['grupos_status'][$gIndex] ?? false;
                            @endphp

                            {{-- CONECTOR ENTRE GRUPOS --}}
                            @if($gIndex > 0)
                                <div class="flex items-center gap-3 my-1">
                                    <div class="flex-1 h-px bg-gray-200 dark:bg-gray-800"></div>
                                    <div class="inline-flex items-center bg-white dark:bg-gray-900 p-0.5 rounded-md border border-gray-300 dark:border-gray-700 text-xs">
                                        <button type="button"
                                                wire:click="$set('gruposCondicionais.{{ $gIndex }}.conector', 'AND')"
                                                class="px-2.5 py-1 rounded font-medium transition {{ ($grupo['conector'] ?? 'AND') === 'AND' ? 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                                            E
                                        </button>
                                        <button type="button"
                                                wire:click="$set('gruposCondicionais.{{ $gIndex }}.conector', 'OR')"
                                                class="px-2.5 py-1 rounded font-medium transition {{ ($grupo['conector'] ?? 'AND') === 'OR' ? 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                                            OU
                                        </button>
                                    </div>
                                    <div class="flex-1 h-px bg-gray-200 dark:bg-gray-800"></div>
                                </div>
                            @endif

                            {{-- BLOCO DO GRUPO --}}
                            <div wire:key="modal-grp-{{ $grupo['id'] ?? $gIndex }}"
                                 class="rounded-md border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
                                
                                <div class="px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">
                                            Grupo {{ $gIndex + 1 }}
                                        </span>
                                        <span class="text-[11px] font-medium {{ $grupoPassou ? 'text-emerald-700 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400' }}">
                                            · {{ $grupoPassou ? 'Atendido' : 'Não atendido' }}
                                        </span>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <button type="button" wire:click="adicionarRegraNoGrupo({{ $gIndex }}, 'AND')" class="px-2 py-1 bg-white dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-700 rounded text-xs font-medium transition">
                                            + E
                                        </button>
                                        <button type="button" wire:click="adicionarRegraNoGrupo({{ $gIndex }}, 'OR')" class="px-2 py-1 bg-white dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-700 rounded text-xs font-medium transition">
                                            + OU
                                        </button>
                                        @if(count($gruposCondicionais) > 1)
                                            <button type="button" wire:click="removerGrupoCondicional({{ $gIndex }})" class="p-1 text-gray-400 hover:text-red-600 transition ml-1" title="Remover grupo">
                                                <i class="ph ph-trash text-sm"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                <div class="p-3.5 divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach($grupo['regras'] ?? [] as $rIndex => $regra)
                                        @php
                                            $regraPassou  = $diagModal['regras_status']["{$gIndex}_{$rIndex}"] ?? false;
                                            $opAtual      = $regra['operador'] ?? '=';
                                            $ocultarValor = in_array($opAtual, ['empty', 'not_empty'], true);
                                        @endphp

                                        <div wire:key="modal-rule-{{ $regra['id'] ?? $rIndex }}" class="py-2.5 first:pt-0 last:pb-0">
                                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center">
                                                
                                                {{-- Conector --}}
                                                <div class="sm:col-span-2">
                                                    @if($rIndex === 0)
                                                        <span class="inline-flex items-center justify-center w-full py-1.5 px-2 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 text-xs font-medium border border-gray-200 dark:border-gray-700">
                                                            Quando
                                                        </span>
                                                    @else
                                                        <select wire:model.live="gruposCondicionais.{{ $gIndex }}.regras.{{ $rIndex }}.conector" class="w-full text-xs font-medium py-1.5 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                                            <option value="AND">E (AND)</option>
                                                            <option value="OR">OU (OR)</option>
                                                        </select>
                                                    @endif
                                                </div>

                                                {{-- Campo --}}
                                                <div class="sm:col-span-4">
                                                    <select wire:model.live="gruposCondicionais.{{ $gIndex }}.regras.{{ $rIndex }}.campo" class="w-full text-xs py-1.5 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                                        <option value="">Selecione o campo...</option>
                                                        @foreach($camposParaCond as $cKey => $cNome)
                                                            <option value="{{ $cKey }}">{{ $cNome }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                {{-- Operador --}}
                                                <div class="{{ $ocultarValor ? 'sm:col-span-5' : 'sm:col-span-3' }}">
                                                    <select wire:model.live="gruposCondicionais.{{ $gIndex }}.regras.{{ $rIndex }}.operador" class="w-full text-xs py-1.5 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                                        @foreach($operadoresLista as $opK => $opLabel)
                                                            <option value="{{ $opK }}">{{ $opLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                {{-- Valor --}}
                                                @if(!$ocultarValor)
                                                    <div class="sm:col-span-2">
                                                        @if($opAtual === 'between')
                                                            <div class="grid grid-cols-2 gap-1">
                                                                <input type="text" wire:model.live.debounce.300ms="gruposCondicionais.{{ $gIndex }}.regras.{{ $rIndex }}.valor" placeholder="Mín" class="w-full text-xs py-1.5 px-2 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                                                <input type="text" wire:model.live.debounce.300ms="gruposCondicionais.{{ $gIndex }}.regras.{{ $rIndex }}.valor_max" placeholder="Máx" class="w-full text-xs py-1.5 px-2 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                                            </div>
                                                        @else
                                                            <input type="text"
                                                                   wire:model.live.debounce.300ms="gruposCondicionais.{{ $gIndex }}.regras.{{ $rIndex }}.valor"
                                                                   placeholder="{{ in_array($opAtual, ['in', 'not_in']) ? 'Op1, Op2...' : 'Valor...' }}"
                                                                   class="w-full text-xs py-1.5 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                                        @endif
                                                    </div>
                                                @endif

                                                {{-- Resultado simples + Remover --}}
                                                <div class="sm:col-span-1 flex items-center justify-between sm:justify-end gap-2">
                                                    <span class="text-[11px] font-mono {{ $regraPassou ? 'text-emerald-700 dark:text-emerald-400 font-semibold' : 'text-gray-400' }}"
                                                          title="{{ $regraPassou ? 'Condição atendida no teste' : 'Condição não atendida no teste' }}">
                                                        {{ $regraPassou ? 'OK' : '—' }}
                                                    </span>
                                                    <button type="button" wire:click="removerRegraDoGrupo({{ $gIndex }}, {{ $rIndex }})" class="text-gray-400 hover:text-red-600 p-1 rounded transition" title="Remover condição">
                                                        <i class="ph ph-x text-sm"></i>
                                                    </button>
                                                </div>

                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- COLUNA DIREITA: SIMULADOR --}}
                    <div class="w-full lg:w-80 xl:w-96 bg-white dark:bg-gray-900 p-4 sm:p-5 flex flex-col justify-between lg:overflow-y-auto custom-scrollbar shrink-0">
                        <div class="space-y-4">
                            <div>
                                <h4 class="text-xs font-semibold text-gray-900 dark:text-white">
                                    Simulador de Teste
                                </h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    Informe valores de exemplo para conferir o comportamento do campo.
                                </p>
                            </div>

                            {{-- RESULTADO DIRETO --}}
                            <div class="px-3.5 py-2.5 rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex items-center justify-between">
                                <span class="text-xs text-gray-600 dark:text-gray-300">Resultado:</span>
                                <span class="text-xs font-semibold {{ $diagModal['visivel'] ? 'text-emerald-700 dark:text-emerald-400' : 'text-gray-600 dark:text-gray-400' }}">
                                    {{ $diagModal['visivel'] ? 'Visível' : 'Oculto' }}
                                </span>
                            </div>

                            {{-- INPUTS DE SIMULAÇÃO --}}
                            <div class="space-y-2.5">
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300 block">
                                    Valores simulados
                                </span>

                                @forelse($diagModal['campos_usados'] as $campoKey => $campoRotulo)
                                    <div class="space-y-1">
                                        <label class="block text-xs text-gray-600 dark:text-gray-400 truncate" title="{{ $campoRotulo }}">
                                            {{ $campoRotulo }}
                                        </label>
                                        <input type="text"
                                               wire:model.live.debounce.250ms="valoresSimulacaoCondicional.{{ $campoKey }}"
                                               placeholder="Digite um valor de teste..."
                                               class="w-full text-xs py-1.5 px-2.5 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                    </div>
                                @empty
                                    <div class="p-3.5 rounded-md border border-dashed border-gray-300 dark:border-gray-700 text-center text-xs text-gray-500">
                                        Selecione um campo nas regras para testar.
                                    </div>
                                @endforelse
                            </div>

                            {{-- EXPRESSÃO LÓGICA --}}
                            <div class="space-y-1">
                                <span class="text-xs text-gray-500 dark:text-gray-400 block">
                                    Expressão lógica
                                </span>
                                <div class="p-2.5 rounded-md bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-mono text-[11px] leading-relaxed break-words border border-gray-200 dark:border-gray-700">
                                    {{ $diagModal['expressao_humana'] }}
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-200 dark:border-gray-800 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-2 mt-6">
                            <button type="button" wire:click="$set('modalCondicoesAvancadasAberto', false)" class="px-3.5 py-2 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 text-xs font-medium text-gray-700 dark:text-gray-200 transition">
                                Cancelar
                            </button>
                            <button type="button" wire:click="confirmarCondicoesAvancadas" class="px-4 py-2 rounded-md bg-purpura-600 hover:bg-purpura-700 text-white text-xs font-semibold transition">
                                Aplicar Condições
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </template>
@endif