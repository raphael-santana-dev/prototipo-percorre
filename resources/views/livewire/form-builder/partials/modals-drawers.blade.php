{{-- 1. NAVIGATION DRAWER: APARÊNCIA E CONFIGURAÇÕES DO FORMULÁRIO --}}
<template x-teleport="body">
    <div class="relative z-[999] font-sans" role="dialog" aria-modal="true" x-show="$wire.drawerFormSettingsAberto" x-cloak>
        <div x-show="$wire.drawerFormSettingsAberto" x-transition.opacity @click="$wire.set('drawerFormSettingsAberto', false)" class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity"></div>

        <div class="fixed inset-y-0 right-0 flex max-w-full pl-10 pointer-events-none">
            <div x-show="$wire.drawerFormSettingsAberto"
                 x-transition:enter="transform transition ease-out duration-250"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in duration-200"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="pointer-events-auto w-screen max-w-md h-[100dvh] flex flex-col">
                 
                <form wire:submit.prevent="salvarFormSettings" class="flex flex-col h-full bg-white dark:bg-gray-800 shadow-2xl border-l border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 shrink-0 flex items-center justify-between bg-white dark:bg-gray-900">
                        <h2 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="ph ph-sliders-horizontal text-base text-purpura-600"></i> Configurações e Aparência do Formulário
                        </h2>
                        <button type="button" @click="$wire.set('drawerFormSettingsAberto', false)" class="rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-white p-1.5 transition">
                            <i class="ph ph-x text-lg"></i>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-6 space-y-5 custom-scrollbar">
                        @if($contextoTipo === 'formulario')
                            <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-900/40">
                                <label class="block text-xs font-semibold text-gray-800 dark:text-gray-200 mb-1.5">URL Amigável (Link Público)</label>
                                <div class="flex items-center">
                                    <span class="bg-gray-200 dark:bg-gray-800 border border-r-0 border-gray-300 dark:border-gray-600 rounded-l-lg px-3 py-2 text-xs text-gray-600 dark:text-gray-300 font-mono">/f/</span>
                                    <input type="text" wire:model="slug" class="w-full text-xs !rounded-l-none" placeholder="meu-formulario">
                                </div>
                                @error('slug') <span class="text-xs text-red-600 font-medium mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        {{-- IMAGEM DE FUNDO DA PÁGINA --}}
                        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-900/40 space-y-3">
                            <label class="block text-xs font-semibold text-gray-800 dark:text-gray-200">Imagem de Fundo da Página</label>
                            <label class="flex flex-col items-center justify-center w-full h-24 border border-dashed border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <div class="flex flex-col items-center justify-center py-3">
                                    <i class="ph ph-upload-simple text-xl text-gray-400 mb-1"></i>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400"><span class="font-semibold text-gray-700 dark:text-gray-200">Clique para carregar</span> (PNG/JPG)</p>
                                </div>
                                <input type="file" wire:model="bg_image_upload" class="hidden" accept="image/*">
                            </label>

                            @if($bg_image_upload || !empty($formSettings['bg_image']))
                                <div class="grid grid-cols-2 gap-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                    <div class="col-span-2">
                                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Ajuste da Imagem</label>
                                        <select wire:model.live="formSettings.bg_size" class="w-full text-xs">
                                            <option value="cover">Preencher ecrã (Cover)</option>
                                            <option value="contain">Encaixar (Contain)</option>
                                            <option value="auto">Original</option>
                                            <option value="repeat">Repetir Mosaico</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Cor Sobreposição</label>
                                        <input type="color" wire:model.live="formSettings.bg_color" class="w-full h-8 p-0.5 bg-white rounded border border-gray-300 cursor-pointer">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Opacidade (0 a 1)</label>
                                        <input type="number" step="0.1" min="0" max="1" wire:model.live="formSettings.bg_opacity" class="w-full text-xs">
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- COR DO FORMULÁRIO (FUNDO E TEXTO DO CARTÃO) --}}
                        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-900/40 space-y-3">
                            <label class="block text-xs font-semibold text-gray-800 dark:text-gray-200">Cor do Formulário</label>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Fundo do Cartão</label>
                                    <input type="color" wire:model.live="formSettings.form_bg_color" class="w-full h-8 p-0.5 bg-white rounded border border-gray-300 cursor-pointer">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Texto Principal</label>
                                    <input type="color" wire:model.live="formSettings.form_text_color" class="w-full h-8 p-0.5 bg-white rounded border border-gray-300 cursor-pointer">
                                </div>
                            </div>
                        </div>

                        {{-- LAYOUT DO CARTÃO --}}
                        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-900/40 space-y-3.5">
                            <h4 class="text-xs font-semibold text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700 pb-2">Layout do Cartão</h4>
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Largura Central</label>
                                <select wire:model.live="formSettings.form_width" class="w-full text-xs">
                                    <option value="max-w-4xl">Largo (Padrão Grid)</option>
                                    <option value="max-w-2xl">Médio</option>
                                    <option value="max-w-lg">Estreito</option>
                                </select>
                            </div>
                            <label class="flex items-start gap-2.5 cursor-pointer">
                                <input type="checkbox" wire:model.live="formSettings.translucent_card" class="mt-0.5">
                                <div class="text-xs">
                                    <span class="font-semibold text-gray-900 dark:text-white block">Cartão Translúcido (Efeito Vidro)</span>
                                    <span class="text-[11px] text-gray-500">Fundo branco semi-transparente com blur.</span>
                                </div>
                            </label>
                        </div>

                        {{-- REGRAS DE NEGÓCIO --}}
                        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-900/40 space-y-3">
                            <h4 class="text-xs font-semibold text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700 pb-2">Regras de Negócio</h4>
                            <label class="flex items-start gap-2.5 cursor-pointer">
                                <input type="checkbox" wire:model.live="formSettings.use_vacancy_limit" class="mt-0.5">
                                <div class="text-xs">
                                    <span class="font-semibold text-gray-900 dark:text-white block">Ativar Trava de Vagas</span>
                                    <span class="text-[11px] text-gray-500">Oculta Unidades/Cursos/Turnos com vagas esgotadas.</span>
                                </div>
                            </label>
                        </div>

                        {{-- CAMPOS FIXOS (PROJETO CUSTOMIZADO) --}}
                        @if($contextoTipo === 'ciclo' && $isCicloCustomizado)
                            <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-900/40 space-y-3">
                                <div class="border-b border-gray-200 dark:border-gray-700 pb-2">
                                    <h4 class="text-xs font-semibold text-gray-800 dark:text-gray-200">Campos Fixos (Projeto Customizado)</h4>
                                    <p class="text-[11px] text-gray-500 mt-0.5">
                                        Escolha quais blocos padrão do sistema devem aparecer na 1ª Página deste projeto:
                                    </p>
                                </div>

                                <label class="flex items-start gap-2.5 cursor-pointer">
                                    <input type="checkbox" wire:model.live="formSettings.enable_fixed_personal" class="mt-0.5">
                                    <div class="text-xs">
                                        <span class="font-semibold text-gray-900 dark:text-white block">Exibir Dados Pessoais Fixos</span>
                                        <span class="text-[11px] text-gray-500">Nome, CPF, Nascimento, E-mail, Celular e PcD.</span>
                                    </div>
                                </label>

                                <label class="flex items-start gap-2.5 cursor-pointer">
                                    <input type="checkbox" wire:model.live="formSettings.enable_fixed_address" class="mt-0.5">
                                    <div class="text-xs">
                                        <span class="font-semibold text-gray-900 dark:text-white block">Exibir Endereço Fixo (CEP)</span>
                                        <span class="text-[11px] text-gray-500">CEP, Logradouro, Número, Bairro, Cidade, UF e Região.</span>
                                    </div>
                                </label>

                                <label class="flex items-start gap-2.5 cursor-pointer">
                                    <input type="checkbox" wire:model.live="formSettings.enable_fixed_course" class="mt-0.5">
                                    <div class="text-xs">
                                        <span class="font-semibold text-gray-900 dark:text-white block">Exibir Seleção de Curso (Unidade / Curso / Turno)</span>
                                        <span class="text-[11px] text-gray-500">Se desmarcado, oculta os selects nativos de Unidade, Curso e Turno.</span>
                                    </div>
                                </label>
                            </div>
                        @endif
                    </div>

                    <div class="p-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 shrink-0 flex justify-end gap-2">
                        <button type="button" @click="$wire.set('drawerFormSettingsAberto', false)" class="btn btn--secondary btn--small">Cancelar</button>
                        <button type="submit" class="btn btn--primary btn--small bg-purpura-600 hover:bg-purpura-700 border-none">
                            Guardar Aparência do Formulário
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

{{-- 2. MODAL DE GESTÃO DE FASES TEMPORAIS --}}
@if($modalFasesAberto)
    <x-modal
        title="Gerenciador de Fases e Liberação"
        subtitle="Agrupe páginas do formulário em fases, defina datas de liberação automática e configure disparos de e-mail."
        max-width="5xl"
        close-method="$set('modalFasesAberto', false)">

        <div class="space-y-4">
            <div class="flex justify-end">
                <button type="button" wire:click="adicionarFase" class="btn btn--secondary btn--small">
                    <i class="ph ph-plus"></i> Nova Fase
                </button>
            </div>

            <div class="space-y-3.5 max-h-[62vh] overflow-y-auto custom-scrollbar pr-1">
                @forelse($fasesConfig as $index => $fase)
                    <div wire:key="fase-item-{{ $index }}" class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/40 dark:bg-gray-900/40">
                        <div class="flex flex-col xl:flex-row gap-5">
                            
                            {{-- Nome e Páginas --}}
                            <div class="flex-1 space-y-3.5 border-b xl:border-b-0 xl:border-r border-gray-200 dark:border-gray-700 pb-4 xl:pb-0 xl:pr-5">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Nome da Fase <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model.live.debounce.300ms="fasesConfig.{{ $index }}.nome" placeholder="Ex: Fase 2 - Envio de Documentos" class="w-full text-xs">
                                    @error('fasesConfig.'.$index.'.nome') <span class="text-xs text-red-600 block mt-1">{{ $message }}</span> @enderror
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Quais páginas compõem esta fase? <span class="text-red-500">*</span></label>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($paginasDisponiveis as $pag)
                                            @php
                                                $paginasSelecionadas = array_map('strval', $fasesConfig[$index]['paginas'] ?? []);
                                                $estaMarcada = in_array((string) $pag, $paginasSelecionadas, true);
                                            @endphp
                                            <label class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border cursor-pointer text-xs font-medium transition-colors {{ $estaMarcada ? 'bg-gray-900 border-gray-900 text-white dark:bg-white dark:text-gray-900' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50' }}">
                                                <input type="checkbox" wire:model.live="fasesConfig.{{ $index }}.paginas" value="{{ (string) $pag }}" class="sr-only">
                                                <span>Página {{ $pag }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('fasesConfig.'.$index.'.paginas') <span class="text-xs text-red-600 block mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            {{-- Datas e Automação --}}
                            <div class="flex-1 space-y-3.5">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Abre em (Liberação) <span class="text-red-500">*</span></label>
                                        <input type="datetime-local" wire:model="fasesConfig.{{ $index }}.data_liberacao" class="w-full text-xs">
                                        @error('fasesConfig.'.$index.'.data_liberacao') <span class="text-xs text-red-600 block mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Encerra em (Opcional)</label>
                                        <input type="datetime-local" wire:model="fasesConfig.{{ $index }}.data_encerramento" class="w-full text-xs">
                                    </div>
                                </div>

                                <div class="p-3 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 space-y-2">
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                        E-mail Automático de Aviso de Liberação (Opcional)
                                    </label>
                                    <select wire:model="fasesConfig.{{ $index }}.email_template_id" class="w-full text-xs">
                                        <option value="">Nenhum e-mail de aviso...</option>
                                        @foreach($templatesDeEmail as $tId => $tNome)
                                            <option value="{{ $tId }}">{{ $tNome }}</option>
                                        @endforeach
                                    </select>
                                    <p class="text-[11px] text-gray-500 leading-tight">
                                        Se selecionado, o sistema disparará este template automaticamente na data de liberação para todos que iniciaram o preenchimento.
                                    </p>

                                    @php
                                        $slugFasePreview = \Illuminate\Support\Str::slug($fasesConfig[$index]['nome'] ?? 'nova_fase', '_');
                                        $prefixoGatilho = $contextoTipo === 'ciclo' ? 'inscricao.fase.' : 'formulario.fase.';
                                        $gatilhoEspecifico = $prefixoGatilho . ($slugFasePreview ?: 'nome_da_fase');
                                        $gatilhoGeral = $contextoTipo === 'ciclo' ? 'inscricao.fase_liberada' : 'formulario.fase_liberada';
                                    @endphp
                                    <div class="pt-2 border-t border-gray-100 dark:border-gray-700 flex flex-wrap items-center gap-1.5 text-[11px]">
                                        <span class="font-medium text-gray-600 dark:text-gray-400">Gatilhos de Automação:</span>
                                        <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded text-gray-800 dark:text-gray-200 font-mono text-[10px]">{{ $gatilhoEspecifico }}</code>
                                        <span class="text-gray-400">ou</span>
                                        <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded text-gray-600 dark:text-gray-300 font-mono text-[10px]">{{ $gatilhoGeral }}</code>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-end xl:pl-2 border-t xl:border-t-0 xl:border-l border-gray-200 dark:border-gray-700 pt-3 xl:pt-0">
                                <button type="button" wire:click="removerFase({{ $index }})" class="p-2 text-gray-400 hover:text-red-600 rounded-lg transition" title="Excluir Fase">
                                    <i class="ph ph-trash text-lg"></i>
                                </button>
                            </div>

                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center border border-dashed border-gray-200 dark:border-gray-700 rounded-lg text-gray-400 text-xs">
                        Nenhuma fase temporal configurada. Clique em "+ Nova Fase" acima para agendar liberações de páginas.
                    </div>
                @endforelse
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" wire:click="$set('modalFasesAberto', false)" class="btn btn--secondary btn--small">Cancelar</button>
                <button type="button" wire:click="salvarFases" class="btn btn--primary btn--small bg-purpura-600 hover:bg-purpura-700 border-none">
                    Salvar Fases
                </button>
            </div>
        </div>
    </x-modal>
@endif

{{-- 3. MODAL DE CONFIGURAÇÃO DE UNIDADES / CURSOS / TURNOS --}}
@if($modalConfigComboAberto)
    <x-modal
        title="Configurar Opções: Unidades, Cursos e Turnos"
        subtitle="Selecione as Unidades, Cursos e Turnos que estarão disponíveis neste conjunto de campos."
        max-width="4xl"
        close-method="$set('modalConfigComboAberto', false)">

        <div class="space-y-4">
            <div class="flex flex-col md:flex-row h-[380px] border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden divide-y md:divide-y-0 md:divide-x divide-gray-200 dark:divide-gray-700">
                <div class="flex-1 flex flex-col overflow-hidden">
                    <div class="p-2.5 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 flex justify-between text-[11px] font-medium text-gray-500 uppercase">
                        <span>1. Unidades Ofertadas</span>
                        <span>{{ count($comboUnidadesSelecionadas) }} sel.</span>
                    </div>
                    <div class="flex-1 overflow-y-auto p-2 space-y-1 custom-scrollbar">
                        @foreach($unidadesDb as $u)
                            <div wire:key="combo-uni-{{ $u->id }}" wire:click="setComboActiveUnidade({{ $u->id }})" class="flex items-center justify-between p-2 rounded cursor-pointer {{ $comboActiveUnidadeId == $u->id ? 'bg-gray-100 dark:bg-gray-700 font-semibold' : 'hover:bg-gray-50' }}">
                                <label class="flex items-center gap-2 cursor-pointer flex-1" wire:click.stop>
                                    <input type="checkbox" wire:model.live="comboUnidadesSelecionadas" value="{{ $u->id }}">
                                    <span class="text-xs text-gray-800 dark:text-gray-200">{{ $u->nome }}</span>
                                </label>
                                <i class="ph ph-caret-right text-xs text-gray-400"></i>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex-1 flex flex-col overflow-hidden">
                    <div class="p-2.5 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 flex justify-between text-[11px] font-medium text-gray-500 uppercase">
                        <span>2. Cursos da Unidade</span>
                        <span>{{ count($comboCursosSelecionados) }} sel.</span>
                    </div>
                    <div class="flex-1 overflow-y-auto p-2 space-y-1 custom-scrollbar">
                        @if($comboActiveUnidadeId)
                            @foreach($cursosDb->filter(fn($c) => $c->unidades->contains('id', $comboActiveUnidadeId)) as $c)
                                @php $chaveCursoUnidade = $comboActiveUnidadeId . '-' . $c->id; @endphp
                                <div wire:key="combo-cur-{{ $chaveCursoUnidade }}" wire:click="setComboActiveCurso({{ $c->id }})" class="flex items-center justify-between p-2 rounded cursor-pointer {{ $comboActiveCursoId == $c->id ? 'bg-gray-100 dark:bg-gray-700 font-semibold' : 'hover:bg-gray-50' }}">
                                    <label class="flex items-center gap-2 cursor-pointer flex-1" wire:click.stop>
                                        <input type="checkbox" wire:model.live="comboCursosSelecionados" value="{{ $chaveCursoUnidade }}">
                                        <span class="text-xs text-gray-800 dark:text-gray-200">{{ $c->nome }}</span>
                                    </label>
                                    <i class="ph ph-caret-right text-xs text-gray-400"></i>
                                </div>
                            @endforeach
                        @else
                            <div class="h-full flex items-center justify-center text-gray-400 text-xs">Selecione uma Unidade à esquerda</div>
                        @endif
                    </div>
                </div>

                <div class="flex-1 flex flex-col overflow-hidden">
                    <div class="p-2.5 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 flex justify-between text-[11px] font-medium text-gray-500 uppercase">
                        <span>3. Turnos Vinculados</span>
                        <span>{{ count($comboTurnosSelecionados) }} sel.</span>
                    </div>
                    <div class="flex-1 overflow-y-auto p-2 space-y-1 custom-scrollbar">
                        @if($comboActiveUnidadeId && $comboActiveCursoId)
                            @php
                                $cursoComboObj = $cursosDb->firstWhere('id', $comboActiveCursoId);
                                $turnosCombo = $cursoComboObj ? (method_exists($cursoComboObj, 'turnosNaUnidade') ? $cursoComboObj->turnosNaUnidade((int) $comboActiveUnidadeId) : $cursoComboObj->turnosVinculados) : collect();
                            @endphp
                            @foreach($turnosCombo as $t)
                                @php $chaveTurnoCompleta = $comboActiveUnidadeId . '-' . $comboActiveCursoId . '-' . $t->id; @endphp
                                <label wire:key="combo-tur-{{ $chaveTurnoCompleta }}" class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox" wire:model.live="comboTurnosSelecionados" value="{{ $chaveTurnoCompleta }}">
                                    <span class="text-xs text-gray-800 dark:text-gray-200">{{ $t->nome }}</span>
                                </label>
                            @endforeach
                        @else
                            <div class="h-full flex items-center justify-center text-gray-400 text-xs">Selecione um Curso ao centro</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between pt-3 border-t border-gray-200 dark:border-gray-700">
                <span class="text-xs text-gray-500">Se deixar tudo desmarcado, o conjunto listará todas as Unidades, Cursos e Turnos ativos.</span>
                <div class="flex gap-2">
                    <button type="button" wire:click="$set('modalConfigComboAberto', false)" class="btn btn--secondary btn--small">Cancelar</button>
                    <button type="button" wire:click="confirmarConfigComboAcademico" class="btn btn--primary btn--small bg-purpura-600 hover:bg-purpura-700 border-none">
                        Aplicar Opções ao Conjunto
                    </button>
                </div>
            </div>
        </div>
    </x-modal>
@endif

{{-- 4. POPUP DE SOLICITAÇÃO DE CONTROLE (LOCK) --}}
@if(!$isLockedByOther && $hasTransferRequest)
    <div class="fixed bottom-6 right-6 z-[999] max-w-sm w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl p-4 font-sans">
        <h4 class="text-xs font-bold text-gray-900 dark:text-white">Pedido de Edição</h4>
        <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">
            O utilizador <strong>{{ $requesterName }}</strong> abriu este formulário e está a solicitar o controlo para efetuar edições.
        </p>
        <div class="mt-3 flex gap-2 justify-end">
            <button type="button" wire:click="rejectTransfer" class="btn btn--secondary btn--small">Recusar e Continuar</button>
            <button type="button" wire:click="acceptTransfer" class="btn btn--primary btn--small bg-purpura-600 hover:bg-purpura-700 border-none">Aceitar e Sair</button>
        </div>
    </div>
@endif