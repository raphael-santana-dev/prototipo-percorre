<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Novo Disparo de Comunicado" 
        subtitle="Selecione um template, defina a audiência e programe o envio em lote"
        icon="ph ph-paper-plane-tilt"
        badge=""
        :breadcrumbs="$breadcrumbs">
        <x-slot name="actions">
            <a href="{{ route('comunicados.index') }}" class="btn btn--secondary btn--medium bg-white dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700">
                <i class="ph-bold ph-clock-counter-clockwise"></i> Histórico de Disparos
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
        
        <!-- COLUNA ESQUERDA (8 COLUNAS): CONFIGURAÇÃO DO DISPARO -->
        <div class="lg:col-span-7 xl:col-span-8 space-y-6">
            
            <!-- CARD 1: ESCOLHA DO TEMPLATE -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-gray-800 dark:text-white flex items-center gap-2">
                        <i class="ph-fill ph-layout text-purpura-600 text-base"></i> 1. Template da Mensagem <span class="text-red-500">*</span>
                    </h3>
                    <a href="{{ route('templates.create') }}" target="_blank" class="text-xs font-bold text-purpura-600 hover:underline flex items-center gap-1">
                        <i class="ph-bold ph-plus"></i> Criar Template
                    </a>
                </div>

                <div>
                    <select wire:model.live="template_id" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-4 py-2.5 text-sm font-semibold focus:ring-purpura-500 focus:border-purpura-500 shadow-2xs">
                        <option value="">-- Selecione o layout do comunicado --</option>
                        @foreach($templates as $tpl)
                            <option value="{{ $tpl->id }}">
                                [{{ strtoupper($tpl->canal ?? 'EMAIL') }}] {{ $tpl->nome }} — Assunto: {{ \Illuminate\Support\Str::limit($tpl->assunto, 45) }}
                            </option>
                        @endforeach
                    </select>
                    @error('template_id') <span class="text-xs text-red-500 font-bold block mt-1.5">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- CARD 2: PÚBLICO E DESTINATÁRIOS -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 space-y-5">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 dark:border-gray-700 pb-3">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-gray-800 dark:text-white flex items-center gap-2">
                        <i class="ph-fill ph-users-three text-purpura-600 text-base"></i> 2. Público e Destinatários
                    </h3>

                    <div class="flex items-center bg-gray-100 dark:bg-gray-900 p-1 rounded-xl border border-gray-200 dark:border-gray-700">
                        <button type="button" wire:click="$set('modo_selecao', 'manual')" class="px-3 py-1 rounded-lg text-xs font-bold transition {{ $modo_selecao === 'manual' ? 'bg-white dark:bg-gray-800 text-purpura-600 shadow-2xs' : 'text-gray-500 hover:text-gray-800' }}">
                            <i class="ph-bold ph-keyboard mr-1"></i> Lista Manual
                        </button>
                        <button type="button" wire:click="$set('modo_selecao', 'dinamico')" class="px-3 py-1 rounded-lg text-xs font-bold transition {{ $modo_selecao === 'dinamico' ? 'bg-white dark:bg-gray-800 text-purpura-600 shadow-2xs' : 'text-gray-500 hover:text-gray-800' }}">
                            <i class="ph-bold ph-funnel mr-1"></i> Filtro da Base de Dados
                        </button>
                    </div>
                </div>

                @if($modo_selecao === 'manual')
                    <div x-data="emailTags(@entangle('destinatarios').live)" class="space-y-2">
                        <div class="flex items-center justify-between">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Digite o e-mail e pressione <kbd class="bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 px-1.5 py-0.5 rounded text-[11px] font-mono">Enter</kbd> ou cole uma lista separada por vírgula/quebra de linha.
                            </p>
                            <button type="button" x-show="emails.length > 0" @click="emails = []" class="text-[11px] font-bold text-red-500 hover:underline">
                                Limpar lista
                            </button>
                        </div>
                        
                        <div class="w-full min-h-[54px] border border-gray-300 dark:border-gray-600 rounded-xl p-2.5 flex flex-wrap gap-1.5 focus-within:ring-2 focus-within:ring-purpura-500/20 focus-within:border-purpura-500 bg-gray-50/40 dark:bg-gray-900 transition">
                            <template x-for="(email, index) in emails" :key="index">
                                <span class="inline-flex items-center gap-1.5 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 text-xs font-bold px-2.5 py-1 rounded-lg border border-gray-200 dark:border-gray-700 shadow-2xs">
                                    <i class="ph-fill ph-envelope-simple text-purpura-500"></i>
                                    <span x-text="email"></span>
                                    <button type="button" @click="remove(index)" class="text-gray-400 hover:text-red-500 transition"><i class="ph-bold ph-x"></i></button>
                                </span>
                            </template>
                            <input type="email" x-model="newEmail" @keydown.enter.prevent="add" @keydown.space.prevent="add" @blur="add" @paste="handlePaste" placeholder="Digite ou cole e-mails aqui..." class="flex-1 outline-none border-none focus:ring-0 min-w-[220px] text-sm py-1 bg-transparent text-gray-800 dark:text-white">
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 rounded-xl bg-purpura-50/40 dark:bg-purpura-900/10 border border-purpura-200/70 dark:border-purpura-800/60">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">Segmento de Público</label>
                            <select wire:model.live="filtro_publico" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-3.5 py-2 text-sm font-semibold focus:ring-purpura-500">
                                <option value="">-- Escolha o segmento --</option>
                                <option value="todos">Todos os Usuários Cadastrados</option>
                                <option value="grupo">Usuários de um Perfil (Role)</option>
                                <option value="unidade">Candidatos inscritos em uma Unidade</option>
                                <option value="curso">Candidatos inscritos em um Curso</option>
                            </select>
                        </div>

                        @if($filtro_publico === 'grupo')
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">Perfil (Role)</label>
                                <select wire:model.live="filtro_role" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-3.5 py-2 text-sm font-semibold focus:ring-purpura-500">
                                    <option value="">Selecione o grupo...</option>
                                    @foreach($rolesDisponiveis as $role)
                                        <option value="{{ $role->name }}">{{ strtoupper($role->name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @elseif($filtro_publico === 'unidade')
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">Unidade Alvo</label>
                                <select wire:model.live="filtro_unidade" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-3.5 py-2 text-sm font-semibold focus:ring-purpura-500">
                                    <option value="">Selecione a unidade...</option>
                                    @foreach($unidadesDisponiveis as $unidade)
                                        <option value="{{ $unidade->id }}">{{ $unidade->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @elseif($filtro_publico === 'curso')
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">Curso Alvo</label>
                                <select wire:model.live="filtro_curso" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-3.5 py-2 text-sm font-semibold focus:ring-purpura-500">
                                    <option value="">Selecione o curso...</option>
                                    @foreach($cursosDisponiveis as $curso)
                                        <option value="{{ $curso->id }}">{{ $curso->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                @endif

                @error('destinatarios') <span class="text-xs text-red-500 font-bold block">{{ $message }}</span> @enderror

                <!-- CÓPIA (CC) E CÓPIA OCULTA (CCO) COLAPSÁVEIS -->
                <div x-data="{ mostrarCopias: false }" class="pt-2 border-t border-gray-100 dark:border-gray-700">
                    <button type="button" @click="mostrarCopias = !mostrarCopias" class="text-xs font-bold text-gray-500 hover:text-purpura-600 flex items-center gap-1 transition">
                        <i class="ph-bold" :class="mostrarCopias ? 'ph-caret-up' : 'ph-plus'"></i>
                        <span>Adicionar Cópia (CC) ou Cópia Oculta (CCO)</span>
                    </button>

                    <div x-show="mostrarCopias" x-collapse x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
                        <div x-data="emailTags(@entangle('cc'))">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Cópia (CC)</label>
                            <div class="w-full min-h-[40px] border border-gray-200 dark:border-gray-700 rounded-xl p-1.5 flex flex-wrap gap-1 bg-white dark:bg-gray-900">
                                <template x-for="(email, index) in emails" :key="index">
                                    <span class="inline-flex items-center gap-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-[11px] font-bold px-2 py-0.5 rounded border border-gray-200 dark:border-gray-700">
                                        <span x-text="email"></span>
                                        <button type="button" @click="remove(index)" class="text-gray-400 hover:text-red-500"><i class="ph-bold ph-x"></i></button>
                                    </span>
                                </template>
                                <input type="text" x-model="newEmail" @keydown.enter.prevent="add" @keydown.space.prevent="add" @blur="add" @paste="handlePaste" placeholder="Adicionar CC..." class="flex-1 outline-none border-none focus:ring-0 min-w-[100px] text-xs py-1 bg-transparent dark:text-white">
                            </div>
                        </div>

                        <div x-data="emailTags(@entangle('bcc'))">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Cópia Oculta (CCO)</label>
                            <div class="w-full min-h-[40px] border border-gray-200 dark:border-gray-700 rounded-xl p-1.5 flex flex-wrap gap-1 bg-white dark:bg-gray-900">
                                <template x-for="(email, index) in emails" :key="index">
                                    <span class="inline-flex items-center gap-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-[11px] font-bold px-2 py-0.5 rounded border border-gray-200 dark:border-gray-700">
                                        <span x-text="email"></span>
                                        <button type="button" @click="remove(index)" class="text-gray-400 hover:text-red-500"><i class="ph-bold ph-x"></i></button>
                                    </span>
                                </template>
                                <input type="text" x-model="newEmail" @keydown.enter.prevent="add" @keydown.space.prevent="add" @blur="add" @paste="handlePaste" placeholder="Adicionar CCO..." class="flex-1 outline-none border-none focus:ring-0 min-w-[100px] text-xs py-1 bg-transparent dark:text-white">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CARD 3: ANEXOS E AGENDAMENTO -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 space-y-6">
                <div>
                    <label class="block text-xs font-extrabold text-gray-800 dark:text-white uppercase tracking-wider mb-2.5 flex items-center gap-2">
                        <i class="ph-fill ph-paperclip text-purpura-600 text-base"></i> 3. Anexos e Momento do Envio
                    </label>

                    <label class="flex flex-col items-center justify-center w-full h-24 border-2 border-dashed border-gray-300 dark:border-gray-600 bg-gray-50/50 dark:bg-gray-900/40 hover:bg-purpura-50/30 hover:border-purpura-400 rounded-xl cursor-pointer transition">
                        <div class="flex flex-col items-center justify-center py-3">
                            <i class="ph-bold ph-cloud-arrow-up text-2xl text-purpura-500 mb-1"></i>
                            <p class="text-xs text-gray-600 dark:text-gray-300 font-bold">Clique para anexar arquivos ao comunicado (Opcional)</p>
                            <span class="text-[10px] text-gray-400">PDF, Imagens ou Documentos até 10MB</span>
                        </div>
                        <input type="file" wire:model="anexos_upload" multiple class="hidden">
                    </label>

                    @if($anexos_upload)
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($anexos_upload as $idx => $file)
                                <div class="bg-purpura-50 dark:bg-purpura-900/30 px-3 py-1.5 border border-purpura-200 dark:border-purpura-800 rounded-lg text-xs font-bold text-purpura-800 dark:text-purpura-200 flex items-center gap-2">
                                    <i class="ph-fill ph-file-text text-purpura-600"></i>
                                    <span class="truncate max-w-[200px]">{{ $file->getClientOriginalName() }}</span>
                                    <button type="button" wire:click="removerAnexoUpload({{ $idx }})" class="text-purpura-400 hover:text-red-500">
                                        <i class="ph-bold ph-x"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div wire:loading wire:target="anexos_upload" class="mt-2 text-xs font-bold text-purpura-600 flex items-center gap-1.5">
                        <i class="ph-bold ph-spinner animate-spin"></i> Carregando anexo(s)...
                    </div>
                    @error('anexos_upload.*') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="pt-4 border-t border-gray-100 dark:border-gray-700 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <label class="flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition {{ $tipo_envio === 'imediato' ? 'border-purpura-500 bg-purpura-50/40 dark:bg-purpura-900/20 ring-1 ring-purpura-500' : 'border-gray-200 dark:border-gray-700' }}">
                            <input wire:model.live="tipo_envio" type="radio" value="imediato" class="mt-0.5 text-purpura-600 focus:ring-purpura-500">
                            <div>
                                <span class="text-xs font-extrabold text-gray-900 dark:text-white block">Disparo Imediato</span>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400">Envia para a fila de processamento agora mesmo.</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition {{ $tipo_envio === 'agendado' ? 'border-purpura-500 bg-purpura-50/40 dark:bg-purpura-900/20 ring-1 ring-purpura-500' : 'border-gray-200 dark:border-gray-700' }}">
                            <input wire:model.live="tipo_envio" type="radio" value="agendado" class="mt-0.5 text-purpura-600 focus:ring-purpura-500">
                            <div>
                                <span class="text-xs font-extrabold text-gray-900 dark:text-white block">Programar Data e Hora</span>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400">O sistema disparará automaticamente no horário escolhido.</span>
                            </div>
                        </label>
                    </div>

                    @if($tipo_envio === 'agendado')
                        <div class="p-4 rounded-xl bg-amber-50/60 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                            <label class="block text-xs font-bold text-amber-900 dark:text-amber-300 uppercase tracking-wider mb-1">Data e Hora Programada <span class="text-red-500">*</span></label>
                            <input wire:model="data_agendamento" type="datetime-local" class="w-full sm:w-64 rounded-lg border-amber-300 dark:border-amber-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-sm font-bold focus:ring-amber-500">
                            @error('data_agendamento') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <a href="{{ route('comunicados.index') }}" class="btn btn--secondary btn--medium">Cancelar</a>
                    
                    @if(feature('comunicado.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('comunicado.criar')))
                        <button type="submit" wire:loading.attr="disabled" class="btn btn--primary btn--medium bg-purpura-600 hover:bg-purpura-700 border-none shadow-sm flex items-center gap-2">
                            <i class="ph-bold {{ $tipo_envio === 'agendado' ? 'ph-calendar-plus' : 'ph-paper-plane-tilt' }} text-base"></i>
                            <span>{{ $tipo_envio === 'agendado' ? 'Confirmar Agendamento' : 'Disparar Comunicado Agora' }}</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- COLUNA DIREITA (4 COLUNAS): RESUMO DE ALCANCE + PREVIEW DO TEMPLATE -->
        <div class="lg:col-span-5 xl:col-span-4 space-y-5 sticky top-6">
            
            <!-- CARD DE ALCANCE ESTIMADO -->
            <div class="bg-gray-900 text-white p-5 rounded-2xl shadow-md flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-purpura-300 block">Alcance Estimado</span>
                    <div class="text-2xl font-black mt-0.5">{{ $this->totalDestinatariosEstimado }} <span class="text-xs font-semibold text-gray-300">destinatário(s)</span></div>
                    <p class="text-[11px] text-gray-400 mt-0.5">
                        Modo: <strong>{{ $modo_selecao === 'manual' ? 'Inserção Manual' : 'Filtro da Base de Dados' }}</strong>
                    </p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center text-purpura-300">
                    <i class="ph-fill ph-users-three text-2xl"></i>
                </div>
            </div>

            <!-- PREVIEW DO TEMPLATE -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-5 py-3.5 border-b border-gray-100 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-900/50 flex items-center justify-between">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-gray-700 dark:text-gray-200 flex items-center gap-1.5">
                        <i class="ph-fill ph-eye text-purpura-600 text-base"></i> Preview da Mensagem
                    </span>
                    @if($this->templateSelecionado)
                        <span class="px-2 py-0.5 text-[10px] font-extrabold uppercase rounded bg-purpura-50 text-purpura-700 border border-purpura-200">
                            {{ $this->templateSelecionado->canal ?? 'email' }}
                        </span>
                    @endif
                </div>

                @if($this->templateSelecionado)
                    <div class="p-5 space-y-3">
                        <div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase block">Assunto:</span>
                            <p class="text-xs font-extrabold text-gray-900 dark:text-white">{{ $this->templateSelecionado->assunto }}</p>
                        </div>
                        <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 prose prose-sm max-w-none text-xs text-gray-700 dark:text-gray-200 max-h-[360px] overflow-y-auto custom-scrollbar">
                            {!! $this->templateSelecionado->corpo !!}
                        </div>
                    </div>
                @else
                    <div class="p-10 text-center text-gray-400">
                        <i class="ph-duotone ph-paper-plane-tilt text-4xl mb-2 block mx-auto"></i>
                        <p class="text-xs font-bold text-gray-600 dark:text-gray-300">Selecione um template</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">A pré-visualização da mensagem aparecerá aqui.</p>
                    </div>
                @endif
            </div>
        </div>

    </form>
</div>