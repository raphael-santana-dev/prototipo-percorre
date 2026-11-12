<div class="w-full relative">
    
    <x-page-header 
        title="Novo Disparo de Comunicado" 
        subtitle="Selecione um template, defina a audiência e programe o envio em lote"
        icon="ph ph-paper-plane-tilt"
        badge=""
        :breadcrumbs="$breadcrumbs">
        <x-slot name="actions">
            <a href="{{ route('comunicados.index') }}" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
                <i class="ph ph-clock-counter-clockwise"></i> Histórico de Disparos
            </a>
        </x-slot>
    </x-page-header>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('emailTags', (entangledArray) => ({
                emails: entangledArray,
                newEmail: '',
                add() {
                    let cleaned = this.newEmail.trim().toLowerCase();
                    if (cleaned && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(cleaned) && !this.emails.includes(cleaned)) {
                        this.emails.push(cleaned);
                    }
                    this.newEmail = '';
                },
                remove(index) {
                    this.emails.splice(index, 1);
                },
                handlePaste(e) {
                    e.preventDefault();
                    let pasteData = (e.clipboardData || window.clipboardData).getData('text');
                    let rawEmails = pasteData.split(/[,;\s\n]+/);
                    rawEmails.forEach(mail => {
                        let cleaned = mail.trim().toLowerCase();
                        if (cleaned && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(cleaned) && !this.emails.includes(cleaned)) {
                            this.emails.push(cleaned);
                        }
                    });
                }
            }))
        })
    </script>

    <form wire:submit.prevent="salvar" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start mt-4">
        
        <!-- COLUNA ESQUERDA: CONFIGURAÇÃO DO DISPARO -->
        <div class="lg:col-span-7 xl:col-span-8 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg divide-y divide-gray-200 dark:divide-gray-700">
            
            <!-- SEÇÃO 1: ESCOLHA DO TEMPLATE -->
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">1. Template da Mensagem <span class="text-red-500">*</span></h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Modelo de comunicação que será enviado aos destinatários.</p>
                    </div>
                    <a href="{{ route('templates.create') }}" target="_blank" class="text-xs font-medium text-purpura-600 hover:underline">
                        + Criar template
                    </a>
                </div>

                <div>
                    <select wire:model.live="template_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-3.5 py-2 text-sm focus:ring-1 focus:ring-purpura-500 focus:border-purpura-500">
                        <option value="">Selecione o layout do comunicado...</option>
                        @foreach($templates as $tpl)
                            <option value="{{ $tpl->id }}">
                                [{{ strtoupper($tpl->canal ?? 'EMAIL') }}] {{ $tpl->nome }} — Assunto: {{ \Illuminate\Support\Str::limit($tpl->assunto, 45) }}
                            </option>
                        @endforeach
                    </select>
                    @error('template_id') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- SEÇÃO 2: PÚBLICO E DESTINATÁRIOS -->
            <div class="p-6 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">2. Público e Destinatários</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Insira os e-mails manualmente ou filtre a base cadastrada.</p>
                    </div>

                    <div class="inline-flex items-center bg-gray-100 dark:bg-gray-900 p-1 rounded-lg border border-gray-200 dark:border-gray-700">
                        <button type="button" wire:click="$set('modo_selecao', 'manual')" class="px-3 py-1 rounded-md text-xs font-medium transition {{ $modo_selecao === 'manual' ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-2xs' : 'text-gray-500 hover:text-gray-800' }}">
                            Lista Manual
                        </button>
                        <button type="button" wire:click="$set('modo_selecao', 'dinamico')" class="px-3 py-1 rounded-md text-xs font-medium transition {{ $modo_selecao === 'dinamico' ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-2xs' : 'text-gray-500 hover:text-gray-800' }}">
                            Filtro da Base
                        </button>
                    </div>
                </div>

                @if($modo_selecao === 'manual')
                    <div x-data="emailTags(@entangle('destinatarios').live)" class="space-y-2">
                        <div class="flex items-center justify-between">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Digite o e-mail e pressione <kbd class="bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 px-1.5 py-0.5 rounded text-[11px] font-mono">Enter</kbd> ou cole uma lista.
                            </p>
                            <button type="button" x-show="emails.length > 0" @click="emails = []" class="text-xs font-medium text-red-600 hover:underline">
                                Limpar lista
                            </button>
                        </div>
                        
                        <div class="w-full min-h-[48px] border border-gray-300 dark:border-gray-600 rounded-lg p-2 flex flex-wrap gap-1.5 focus-within:ring-1 focus-within:ring-purpura-500 focus-within:border-purpura-500 bg-white dark:bg-gray-900 transition">
                            <template x-for="(email, index) in emails" :key="index">
                                <span class="inline-flex items-center gap-1.5 bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 text-xs font-medium px-2 py-0.5 rounded border border-gray-200 dark:border-gray-700">
                                    <span x-text="email"></span>
                                    <button type="button" @click="remove(index)" class="text-gray-400 hover:text-red-600 transition"><i class="ph ph-x"></i></button>
                                </span>
                            </template>
                            <input type="email" x-model="newEmail" @keydown.enter.prevent="add" @keydown.space.prevent="add" @blur="add" @paste="handlePaste" placeholder="Digite ou cole e-mails aqui..." class="flex-1 outline-none border-none focus:ring-0 min-w-[200px] text-xs py-1 bg-transparent text-gray-800 dark:text-white">
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Segmento de Público</label>
                            <select wire:model.live="filtro_publico" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-3 py-2 text-xs">
                                <option value="">Escolha o segmento...</option>
                                <option value="todos">Todos os Usuários Cadastrados</option>
                                <option value="grupo">Usuários de um Perfil (Role)</option>
                                <option value="unidade">Candidatos inscritos em uma Unidade</option>
                                <option value="curso">Candidatos inscritos em um Curso</option>
                            </select>
                        </div>

                        @if($filtro_publico === 'grupo')
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Perfil (Role)</label>
                                <select wire:model.live="filtro_role" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-3 py-2 text-xs">
                                    <option value="">Selecione o grupo...</option>
                                    @foreach($rolesDisponiveis as $role)
                                        <option value="{{ $role->name }}">{{ strtoupper($role->name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @elseif($filtro_publico === 'unidade')
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Unidade Alvo</label>
                                <select wire:model.live="filtro_unidade" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-3 py-2 text-xs">
                                    <option value="">Selecione a unidade...</option>
                                    @foreach($unidadesDisponiveis as $unidade)
                                        <option value="{{ $unidade->id }}">{{ $unidade->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @elseif($filtro_publico === 'curso')
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Curso Alvo</label>
                                <select wire:model.live="filtro_curso" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-3 py-2 text-xs">
                                    <option value="">Selecione o curso...</option>
                                    @foreach($cursosDisponiveis as $curso)
                                        <option value="{{ $curso->id }}">{{ $curso->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                @endif

                @error('destinatarios') <span class="text-xs text-red-600 block">{{ $message }}</span> @enderror

                <!-- CÓPIA (CC) E CÓPIA OCULTA (CCO) -->
                <div x-data="{ mostrarCopias: false }" class="pt-2 border-t border-gray-100 dark:border-gray-700">
                    <button type="button" @click="mostrarCopias = !mostrarCopias" class="text-xs font-medium text-gray-500 hover:text-gray-900 dark:hover:text-white flex items-center gap-1 transition">
                        <i class="ph" :class="mostrarCopias ? 'ph-caret-up' : 'ph-plus'"></i>
                        <span>Adicionar Cópia (CC) ou Cópia Oculta (CCO)</span>
                    </button>

                    <div x-show="mostrarCopias" x-collapse x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
                        <div x-data="emailTags(@entangle('cc'))">
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">Cópia (CC)</label>
                            <div class="w-full min-h-[38px] border border-gray-300 dark:border-gray-600 rounded-lg p-1.5 flex flex-wrap gap-1 bg-white dark:bg-gray-900">
                                <template x-for="(email, index) in emails" :key="index">
                                    <span class="inline-flex items-center gap-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-[11px] px-2 py-0.5 rounded border border-gray-200 dark:border-gray-700">
                                        <span x-text="email"></span>
                                        <button type="button" @click="remove(index)" class="text-gray-400 hover:text-red-500"><i class="ph ph-x"></i></button>
                                    </span>
                                </template>
                                <input type="text" x-model="newEmail" @keydown.enter.prevent="add" @keydown.space.prevent="add" @blur="add" @paste="handlePaste" placeholder="Adicionar CC..." class="flex-1 outline-none border-none focus:ring-0 min-w-[100px] text-xs py-0.5 bg-transparent dark:text-white">
                            </div>
                        </div>

                        <div x-data="emailTags(@entangle('bcc'))">
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">Cópia Oculta (CCO)</label>
                            <div class="w-full min-h-[38px] border border-gray-300 dark:border-gray-600 rounded-lg p-1.5 flex flex-wrap gap-1 bg-white dark:bg-gray-900">
                                <template x-for="(email, index) in emails" :key="index">
                                    <span class="inline-flex items-center gap-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-[11px] px-2 py-0.5 rounded border border-gray-200 dark:border-gray-700">
                                        <span x-text="email"></span>
                                        <button type="button" @click="remove(index)" class="text-gray-400 hover:text-red-500"><i class="ph ph-x"></i></button>
                                    </span>
                                </template>
                                <input type="text" x-model="newEmail" @keydown.enter.prevent="add" @keydown.space.prevent="add" @blur="add" @paste="handlePaste" placeholder="Adicionar CCO..." class="flex-1 outline-none border-none focus:ring-0 min-w-[100px] text-xs py-0.5 bg-transparent dark:text-white">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SEÇÃO 3: ANEXOS E AGENDAMENTO -->
            <div class="p-6 space-y-5">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">3. Anexos e Agendamento</h3>

                    <label class="flex flex-col items-center justify-center w-full h-20 border border-dashed border-gray-300 dark:border-gray-600 bg-gray-50/50 dark:bg-gray-900/40 hover:bg-gray-50 rounded-lg cursor-pointer transition">
                        <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300 font-medium">
                            <i class="ph ph-paperclip text-base text-gray-400"></i>
                            <span>Anexar arquivos ao comunicado (PDF, Imagens ou Documentos até 10MB)</span>
                        </div>
                        <input type="file" wire:model="anexos_upload" multiple class="hidden">
                    </label>

                    @if($anexos_upload)
                        <div class="mt-2.5 flex flex-wrap gap-1.5">
                            @foreach($anexos_upload as $idx => $file)
                                <div class="bg-gray-100 dark:bg-gray-900 px-2.5 py-1 border border-gray-200 dark:border-gray-700 rounded text-xs font-medium text-gray-700 dark:text-gray-200 flex items-center gap-1.5">
                                    <span class="truncate max-w-[200px]">{{ $file->getClientOriginalName() }}</span>
                                    <button type="button" wire:click="removerAnexoUpload({{ $idx }})" class="text-gray-400 hover:text-red-600">
                                        <i class="ph ph-x"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div wire:loading wire:target="anexos_upload" class="mt-2 text-xs text-gray-500 flex items-center gap-1.5">
                        <i class="ph ph-spinner animate-spin"></i> Carregando anexo(s)...
                    </div>
                    @error('anexos_upload.*') <span class="text-xs text-red-600 block mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="pt-4 border-t border-gray-100 dark:border-gray-700 space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="flex items-start gap-2.5 p-3 rounded-lg border cursor-pointer transition {{ $tipo_envio === 'imediato' ? 'border-gray-900 bg-gray-50 dark:border-gray-300 dark:bg-gray-900' : 'border-gray-200 dark:border-gray-700' }}">
                            <input wire:model.live="tipo_envio" type="radio" value="imediato" class="mt-0.5 text-gray-900 focus:ring-gray-900">
                            <div>
                                <span class="text-xs font-semibold text-gray-900 dark:text-white block">Disparo Imediato</span>
                                <span class="text-[11px] text-gray-500">Envia para a fila de processamento agora.</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-2.5 p-3 rounded-lg border cursor-pointer transition {{ $tipo_envio === 'agendado' ? 'border-gray-900 bg-gray-50 dark:border-gray-300 dark:bg-gray-900' : 'border-gray-200 dark:border-gray-700' }}">
                            <input wire:model.live="tipo_envio" type="radio" value="agendado" class="mt-0.5 text-gray-900 focus:ring-gray-900">
                            <div>
                                <span class="text-xs font-semibold text-gray-900 dark:text-white block">Programar Data e Hora</span>
                                <span class="text-[11px] text-gray-500">Dispara no horário agendado.</span>
                            </div>
                        </label>
                    </div>

                    @if($tipo_envio === 'agendado')
                        <div class="p-3.5 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Data e Hora Programada <span class="text-red-500">*</span></label>
                            <input wire:model="data_agendamento" type="datetime-local" class="w-full sm:w-64 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-3 py-1.5 text-xs">
                            @error('data_agendamento') <span class="text-xs text-red-600 block mt-1">{{ $message }}</span> @enderror
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <a href="{{ route('comunicados.index') }}" class="btn btn--secondary btn--medium">Cancelar</a>
                    
                    @if(feature('comunicado.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('comunicado.criar')))
                        <button type="submit" wire:loading.attr="disabled" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none">
                            {{ $tipo_envio === 'agendado' ? 'Confirmar Agendamento' : 'Disparar Comunicado' }}
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- COLUNA DIREITA: ALCANCE E PREVIEW -->
        <div class="lg:col-span-5 xl:col-span-4 space-y-4 sticky top-6">
            
            <!-- RESUMO DE ALCANCE -->
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <span class="text-xs text-gray-500 dark:text-gray-400 block">Alcance Estimado</span>
                    <div class="text-xl font-bold text-gray-900 dark:text-white mt-0.5">
                        {{ $this->totalDestinatariosEstimado }} <span class="text-xs font-normal text-gray-500">destinatário(s)</span>
                    </div>
                </div>
                <span class="px-2.5 py-1 text-[11px] font-medium rounded bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                    {{ $modo_selecao === 'manual' ? 'Lista Manual' : 'Filtro da Base' }}
                </span>
            </div>

            <!-- PREVIEW DO TEMPLATE -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">
                        Pré-visualização
                    </span>
                    @if($this->templateSelecionado)
                        <span class="px-2 py-0.5 text-[10px] font-semibold uppercase rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                            {{ $this->templateSelecionado->canal ?? 'email' }}
                        </span>
                    @endif
                </div>

                @if($this->templateSelecionado)
                    <div class="p-4 space-y-3">
                        <div>
                            <span class="text-[11px] text-gray-400 block">Assunto</span>
                            <p class="text-xs font-semibold text-gray-900 dark:text-white">{{ $this->templateSelecionado->assunto }}</p>
                        </div>
                        <div class="p-3.5 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 prose prose-sm max-w-none text-xs text-gray-700 dark:text-gray-200 max-h-[360px] overflow-y-auto custom-scrollbar">
                            {!! $this->templateSelecionado->corpo !!}
                        </div>
                    </div>
                @else
                    <div class="p-8 text-center text-gray-400">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Nenhum template selecionado</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">Selecione um modelo ao lado para pré-visualizar.</p>
                    </div>
                @endif
            </div>
        </div>

    </form>
</div>