@if($modalCondicoesAvancadasAberto)
    @php
        $diagModal       = $this->diagnosticoSimulacaoCondicional;
        $camposParaCond  = $this->camposDisponiveisParaCondicao;
        $operadoresLista = $this->operadoresCondicionais;
    @endphp
    <template x-teleport="body">
        <div class="fixed inset-0 z-[999] flex items-center justify-center bg-gray-900/60 backdrop-blur-xs p-4 overflow-y-auto font-sans"
             @keydown.escape.window="$wire.set('modalCondicoesAvancadasAberto', false)"
             role="dialog"
             aria-modal="true">
            
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-5xl border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col max-h-[90vh]">
                
                {{-- CABEÇALHO SÓBRIO --}}
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between bg-white dark:bg-gray-900 shrink-0">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                                Regras de Visibilidade do Campo
                            </h3>
                            <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 text-xs font-medium">
                                {{ strip_tags($label) ?: ($name ?: 'Novo Campo') }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Defina quando este campo deve ser exibido combinando grupos lógicos E (AND) e OU (OR).
                        </p>
                    </div>

                    <button type="button" wire:click="$set('modalCondicoesAvancadasAberto', false)" class="text-gray-400 hover:text-gray-700 dark:hover:text-white p-1.5 rounded-lg transition">
                        <i class="ph ph-x text-lg"></i>
                    </button>
                </div>

                {{-- CORPO EM 2 COLUNAS --}}
                <div class="flex-1 overflow-hidden flex flex-col lg:flex-row divide-y lg:divide-y-0 lg:divide-x divide-gray-200 dark:divide-gray-800">
                    
                    {{-- COLUNA ESQUERDA: GRUPOS DE CONDIÇÕES --}}
                    <div class="flex-1 overflow-y-auto p-6 space-y-4 custom-scrollbar bg-gray-50/50 dark:bg-gray-950">
                        
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                Grupos de Condições ({{ count($gruposCondicionais) }})
                            </span>
                            <div class="flex items-center gap-1.5">
                                <button type="button" wire:click="adicionarGrupoCondicional('AND')" class="btn btn--secondary btn--small !h-8">
                                    + Grupo E (AND)
                                </button>
                                <button type="button" wire:click="adicionarGrupoCondicional('OR')" class="btn btn--secondary btn--small !h-8">
                                    + Grupo OU (OR)
                                </button>
                            </div>
                        </div>

                        @foreach($gruposCondicionais as $gIndex => $grupo)
                            @php
                                $grupoPassou = $diagModal['grupos_status'][$gIndex] ?? false;
                            @endphp

                            {{-- SELETOR DE CONECTOR ENTRE GRUPOS --}}
                            @if($gIndex > 0)
                                <div class="flex items-center gap-3 my-2">
                                    <div class="flex-1 h-px bg-gray-200 dark:bg-gray-800"></div>
                                    <div class="inline-flex items-center bg-white dark:bg-gray-900 p-0.5 rounded-lg border border-gray-300 dark:border-gray-700 text-xs">
                                        <button type="button"
                                                wire:click="$set('gruposCondicionais.{{ $gIndex }}.conector', 'AND')"
                                                class="px-2.5 py-1 rounded-md font-medium transition {{ ($grupo['conector'] ?? 'AND') === 'AND' ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'text-gray-500 hover:text-gray-800' }}">
                                            E (AND)
                                        </button>
                                        <button type="button"
                                                wire:click="$set('gruposCondicionais.{{ $gIndex }}.conector', 'OR')"
                                                class="px-2.5 py-1 rounded-md font-medium transition {{ ($grupo['conector'] ?? 'AND') === 'OR' ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'text-gray-500 hover:text-gray-800' }}">
                                            OU (OR)
                                        </button>
                                    </div>
                                    <div class="flex-1 h-px bg-gray-200 dark:bg-gray-800"></div>
                                </div>
                            @endif

                            {{-- BLOCO DO GRUPO --}}
                            <div wire:key="modal-grp-{{ $grupo['id'] ?? $gIndex }}"
                                 class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
                                
                                <div class="px-4 py-2.5 bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-800 flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-2.5">
                                        <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">
                                            Grupo #{{ $gIndex + 1 }}
                                        </span>
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-medium border {{ $grupoPassou ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700' }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $grupoPassou ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                            {{ $grupoPassou ? 'Atendido no teste' : 'Não atendido' }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-1.5">
                                        <button type="button" wire:click="adicionarRegraNoGrupo({{ $gIndex }}, 'AND')" class="px-2.5 py-1 bg-white dark:bg-gray-900 hover:bg-gray-50 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 rounded text-xs font-medium transition">
                                            + Condição E
                                        </button>
                                        <button type="button" wire:click="adicionarRegraNoGrupo({{ $gIndex }}, 'OR')" class="px-2.5 py-1 bg-white dark:bg-gray-900 hover:bg-gray-50 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 rounded text-xs font-medium transition">
                                            + Condição OU
                                        </button>
                                        @if(count($gruposCondicionais) > 1)
                                            <button type="button" wire:click="removerGrupoCondicional({{ $gIndex }})" class="p-1 text-gray-400 hover:text-red-600 transition ml-1" title="Remover grupo">
                                                <i class="ph ph-trash text-sm"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                <div class="p-4 divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach($grupo['regras'] ?? [] as $rIndex => $regra)
                                        @php
                                            $regraPassou  = $diagModal['regras_status']["{$gIndex}_{$rIndex}"] ?? false;
                                            $opAtual      = $regra['operador'] ?? '=';
                                            $ocultarValor = in_array($opAtual, ['empty', 'not_empty'], true);
                                        @endphp

                                        <div wire:key="modal-rule-{{ $regra['id'] ?? $rIndex }}" class="py-2.5 first:pt-0 last:pb-0">
                                            <div class="grid grid-cols-1 xl:grid-cols-12 gap-2 items-center">
                                                
                                                {{-- Conector da Linha --}}
                                                <div class="xl:col-span-2">
                                                    @if($rIndex === 0)
                                                        <span class="inline-flex items-center justify-center w-full py-1.5 px-2 rounded bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 text-xs font-medium border border-gray-200 dark:border-gray-700">
                                                            Quando
                                                        </span>
                                                    @else
                                                        <select wire:model.live="gruposCondicionais.{{ $gIndex }}.regras.{{ $rIndex }}.conector" class="w-full text-xs font-medium py-1.5">
                                                            <option value="AND">E (AND)</option>
                                                            <option value="OR">OU (OR)</option>
                                                        </select>
                                                    @endif
                                                </div>

                                                {{-- Campo de Referência --}}
                                                <div class="xl:col-span-4">
                                                    <select wire:model.live="gruposCondicionais.{{ $gIndex }}.regras.{{ $rIndex }}.campo" class="w-full text-xs py-1.5">
                                                        <option value="">Selecione o campo...</option>
                                                        @foreach($camposParaCond as $cKey => $cNome)
                                                            <option value="{{ $cKey }}">{{ $cNome }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                {{-- Operador --}}
                                                <div class="{{ $ocultarValor ? 'xl:col-span-5' : 'xl:col-span-3' }}">
                                                    <select wire:model.live="gruposCondicionais.{{ $gIndex }}.regras.{{ $rIndex }}.operador" class="w-full text-xs py-1.5">
                                                        @foreach($operadoresLista as $opK => $opLabel)
                                                            <option value="{{ $opK }}">{{ $opLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                {{-- Valor Esperado --}}
                                                @if(!$ocultarValor)
                                                    <div class="xl:col-span-2">
                                                        @if($opAtual === 'between')
                                                            <div class="grid grid-cols-2 gap-1">
                                                                <input type="text" wire:model.live.debounce.300ms="gruposCondicionais.{{ $gIndex }}.regras.{{ $rIndex }}.valor" placeholder="Mín" class="w-full text-xs py-1.5 px-2">
                                                                <input type="text" wire:model.live.debounce.300ms="gruposCondicionais.{{ $gIndex }}.regras.{{ $rIndex }}.valor_max" placeholder="Máx" class="w-full text-xs py-1.5 px-2">
                                                            </div>
                                                        @else
                                                            <input type="text"
                                                                   wire:model.live.debounce.300ms="gruposCondicionais.{{ $gIndex }}.regras.{{ $rIndex }}.valor"
                                                                   placeholder="{{ in_array($opAtual, ['in', 'not_in']) ? 'Op1, Op2...' : 'Valor...' }}"
                                                                   class="w-full text-xs py-1.5">
                                                        @endif
                                                    </div>
                                                @endif

                                                {{-- Indicador Discreto + Remover --}}
                                                <div class="xl:col-span-1 flex items-center justify-end gap-2">
                                                    <span class="w-2 h-2 rounded-full shrink-0 {{ $regraPassou ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-600' }}"
                                                          title="{{ $regraPassou ? 'Condição atendida no teste' : 'Condição não atendida no teste' }}"></span>
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

                    {{-- COLUNA DIREITA: SIMULADOR DE TESTE SÓBRIO --}}
                    <div class="w-full lg:w-80 xl:w-96 bg-white dark:bg-gray-900 p-6 flex flex-col justify-between overflow-y-auto custom-scrollbar shrink-0">
                        <div class="space-y-5">
                            <div>
                                <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Simulador de Teste
                                </h4>
                                <p class="text-xs text-gray-500 mt-1">
                                    Teste valores abaixo para validar se o campo ficará visível ou oculto para o candidato.
                                </p>
                            </div>

                            {{-- RESULTADO DO TESTE (SÓBRIO, SEM CAIXA VERMELHA GIGANTE) --}}
                            <div class="p-3.5 rounded-lg border flex items-center justify-between {{ $diagModal['visivel'] ? 'bg-emerald-50/60 border-emerald-200 text-emerald-900 dark:bg-emerald-950/20 dark:border-emerald-800 dark:text-emerald-200' : 'bg-gray-50 border-gray-200 text-gray-800 dark:bg-gray-800/60 dark:border-gray-700 dark:text-gray-200' }}">
                                <div class="flex items-center gap-2.5">
                                    <i class="ph {{ $diagModal['visivel'] ? 'ph-eye text-emerald-600' : 'ph-eye-slash text-gray-500' }} text-lg"></i>
                                    <div>
                                        <span class="text-[11px] text-gray-500 dark:text-gray-400 block">Estado resultante no teste</span>
                                        <span class="text-xs font-bold block">
                                            {{ $diagModal['visivel'] ? 'Campo Visível' : 'Campo Oculto' }}
                                        </span>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 text-[10px] font-mono font-semibold rounded border {{ $diagModal['visivel'] ? 'bg-white text-emerald-700 border-emerald-200' : 'bg-white text-gray-600 border-gray-200 dark:bg-gray-900 dark:text-gray-400 dark:border-gray-700' }}">
                                    {{ $diagModal['visivel'] ? 'TRUE' : 'FALSE' }}
                                </span>
                            </div>

                            {{-- INPUTS DE SIMULAÇÃO --}}
                            <div class="space-y-2.5">
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 block">
                                    Valores de Teste
                                </span>

                                @forelse($diagModal['campos_usados'] as $campoKey => $campoRotulo)
                                    <div class="space-y-1">
                                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400 truncate" title="{{ $campoRotulo }}">
                                            {{ $campoRotulo }}
                                        </label>
                                        <input type="text"
                                               wire:model.live.debounce.250ms="valoresSimulacaoCondicional.{{ $campoKey }}"
                                               placeholder="Digite um valor de teste..."
                                               class="w-full text-xs py-1.5 px-2.5">
                                    </div>
                                @empty
                                    <div class="p-4 rounded-lg border border-dashed border-gray-200 dark:border-gray-700 text-center text-xs text-gray-400">
                                        Selecione um campo nas regras ao lado para testar diferentes respostas.
                                    </div>
                                @endforelse
                            </div>

                            {{-- EXPRESSÃO LÓGICA RESULTANTE --}}
                            <div class="space-y-1">
                                <span class="text-[11px] font-medium text-gray-500 block">
                                    Expressão lógica:
                                </span>
                                <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-mono text-[11px] leading-relaxed break-words border border-gray-200 dark:border-gray-700">
                                    {{ $diagModal['expressao_humana'] }}
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-200 dark:border-gray-800 flex items-center justify-end gap-2 mt-6">
                            <button type="button" wire:click="$set('modalCondicoesAvancadasAberto', false)" class="btn btn--secondary btn--small">
                                Cancelar
                            </button>
                            <button type="button" wire:click="confirmarCondicoesAvancadas" class="btn btn--primary btn--small bg-purpura-600 hover:bg-purpura-700 border-none">
                                Aplicar Condições
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </template>
@endif