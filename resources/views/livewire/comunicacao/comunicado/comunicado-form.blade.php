<div class="p-6 max-w-[900px] mx-auto font-sans relative">
    
    <x-page-header 
        title="Configurar Disparo de E-mail" 
        icon="ph ph-paper-plane-tilt"
        badge="">
        <x-slot name="actions">
            <a href="{{ route('comunicados.index') }}" class="px-4 py-2 text-sm font-bold border rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 flex items-center gap-2">
                <i class="ph-bold ph-arrow-left"></i> Voltar para Histórico
            </a>
        </x-slot>
    </x-page-header>

    <!-- Script Alpine de Tags de Email -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('emailTags', (entangledArray) => ({
                emails: entangledArray,
                newEmail: '',
                add() {
                    let cleaned = this.newEmail.trim().toLowerCase();
                    if(cleaned && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(cleaned) && !this.emails.includes(cleaned)){
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
                        if(cleaned && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(cleaned) && !this.emails.includes(cleaned)){
                            this.emails.push(cleaned);
                        }
                    });
                }
            }))
        })
    </script>

    <div class="bg-white p-6 md:p-8 rounded-xl shadow-sm border border-gray-200">
        <form wire:submit.prevent="salvar" class="space-y-6">
            
            <!-- TEMPLATE -->
            <div>
                <label class="block text-sm font-bold text-gray-800 mb-1 flex items-center gap-2">
                    <i class="ph-fill ph-layout text-purpura-500"></i> Template Selecionado <span class="text-red-500">*</span>
                </label>
                <select wire:model="template_id" class="w-full rounded-md border-gray-300 px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500 shadow-sm">
                    <option value="">Selecione o layout do e-mail...</option>
                    @foreach($templates as $tpl)
                        <option value="{{ $tpl->id }}">{{ $tpl->nome }} (Assunto: {{ $tpl->assunto }})</option>
                    @endforeach
                </select>
                @error('template_id') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
            </div>

            <hr class="border-gray-100">

            <!-- SELEÇÃO DE DESTINATÁRIOS (INTELIGENTE) -->
            <div>
                <label class="block text-sm font-bold text-gray-800 mb-3 flex items-center gap-2">
                    <i class="ph-fill ph-users text-purpura-500"></i> Como deseja selecionar os destinatários?
                </label>

                <!-- TABS -->
                <div class="flex gap-4 mb-4 border-b border-gray-200">
                    <button type="button" wire:click="$set('modo_selecao', 'manual')" class="pb-2 text-sm font-bold transition-colors border-b-2 px-2 {{ $modo_selecao === 'manual' ? 'border-purpura-600 text-purpura-600' : 'border-transparent text-gray-400 hover:text-gray-700' }}">
                        1. Digitar / Colar E-mails
                    </button>
                    <button type="button" wire:click="$set('modo_selecao', 'dinamico')" class="pb-2 text-sm font-bold transition-colors border-b-2 px-2 {{ $modo_selecao === 'dinamico' ? 'border-purpura-600 text-purpura-600' : 'border-transparent text-gray-400 hover:text-gray-700' }}">
                        2. Buscar na Base de Dados (Automático)
                    </button>
                </div>

                <!-- CONTEÚDO MODO MANUAL -->
                @if($modo_selecao === 'manual')
                    <div x-data="emailTags(@entangle('destinatarios'))" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <p class="text-xs text-gray-600 mb-2 font-medium">Digite o e-mail e aperte <kbd class="bg-white px-1 rounded border shadow-sm">Enter</kbd> ou <b>cole uma lista do Excel/Word.</b></p>
                        
                        <div class="w-full min-h-[42px] border border-gray-300 rounded-md p-1.5 flex flex-wrap gap-1 focus-within:ring-1 focus-within:ring-purpura-500 focus-within:border-purpura-500 bg-white">
                            <template x-for="(email, index) in emails" :key="index">
                                <span class="inline-flex items-center gap-1 bg-purpura-50 text-purpura-700 text-xs font-bold px-2.5 py-1 rounded-full border border-purpura-200">
                                    <span x-text="email"></span>
                                    <button type="button" @click="remove(index)" class="hover:text-red-500 transition"><i class="ph-bold ph-x"></i></button>
                                </span>
                            </template>
                            <input type="email" x-model="newEmail" @keydown.enter.prevent="add" @keydown.space.prevent="add" @paste="handlePaste" placeholder="Adicionar e-mail..." class="flex-1 outline-none border-none focus:ring-0 min-w-[150px] text-sm py-1 bg-transparent">
                        </div>
                    </div>
                @endif

                <!-- CONTEÚDO MODO DINÂMICO -->
                @if($modo_selecao === 'dinamico')
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                        <p class="text-xs text-blue-700 mb-4 font-medium">O sistema fará a varredura e extrairá os e-mails do público escolhido no momento do envio.</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-blue-800 uppercase tracking-wider mb-1">Qual o Público Alvo?</label>
                                <select wire:model.live="filtro_publico" class="w-full rounded-md border-blue-300 px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500 bg-white">
                                    <option value="">Selecione...</option>
                                    <option value="todos">Todos os Usuários Cadastrados</option>
                                    <option value="grupo">Usuários de um Grupo de Acesso (Role)</option>
                                    <option value="unidade">Estudantes inscritos em uma Unidade</option>
                                    <option value="curso">Estudantes inscritos em um Curso</option>
                                </select>
                            </div>

                            <!-- Filtros Secundários Baseados na Seleção -->
                            @if($filtro_publico === 'grupo')
                                <div>
                                    <label class="block text-[11px] font-bold text-blue-800 uppercase tracking-wider mb-1">Qual Grupo (Role)?</label>
                                    <select wire:model="filtro_role" class="w-full rounded-md border-blue-300 px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500 bg-white">
                                        <option value="">Selecione o Grupo...</option>
                                        @foreach($rolesDisponiveis as $role)
                                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @elseif($filtro_publico === 'unidade')
                                <div>
                                    <label class="block text-[11px] font-bold text-blue-800 uppercase tracking-wider mb-1">Qual Unidade?</label>
                                    <select wire:model="filtro_unidade" class="w-full rounded-md border-blue-300 px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500 bg-white">
                                        <option value="">Selecione a Unidade...</option>
                                        @foreach($unidadesDisponiveis as $unidade)
                                            <option value="{{ $unidade->id }}">{{ $unidade->nome }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @elseif($filtro_publico === 'curso')
                                <div>
                                    <label class="block text-[11px] font-bold text-blue-800 uppercase tracking-wider mb-1">Qual Curso?</label>
                                    <select wire:model="filtro_curso" class="w-full rounded-md border-blue-300 px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500 bg-white">
                                        <option value="">Selecione o Curso...</option>
                                        @foreach($cursosDisponiveis as $curso)
                                            <option value="{{ $curso->id }}">{{ $curso->nome }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
                @error('destinatarios') <span class="text-xs text-red-500 font-bold block mt-2"><i class="ph-fill ph-warning-circle"></i> {{ $message }}</span> @enderror
            </div>

            <!-- CÓPIA (CC) E OCULTA (BCC) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                <div x-data="emailTags(@entangle('cc'))">
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Cópia (CC) - Opcional</label>
                    <div class="w-full min-h-[42px] border border-gray-300 rounded-md p-1.5 flex flex-wrap gap-1 focus-within:border-purpura-500 bg-white shadow-sm">
                        <template x-for="(email, index) in emails" :key="index">
                            <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-700 text-xs font-bold px-2 py-1 rounded border border-gray-200">
                                <span x-text="email"></span>
                                <button type="button" @click="remove(index)" class="hover:text-red-500"><i class="ph-bold ph-x"></i></button>
                            </span>
                        </template>
                        <input type="text" x-model="newEmail" @keydown.enter.prevent="add" @keydown.space.prevent="add" @paste="handlePaste" placeholder="Add..." class="flex-1 outline-none border-none focus:ring-0 min-w-[100px] text-sm py-1 bg-transparent">
                    </div>
                </div>

                <div x-data="emailTags(@entangle('bcc'))">
                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Cópia Oculta (CCO) - Opcional</label>
                    <div class="w-full min-h-[42px] border border-gray-300 rounded-md p-1.5 flex flex-wrap gap-1 focus-within:border-purpura-500 bg-white shadow-sm">
                        <template x-for="(email, index) in emails" :key="index">
                            <span class="inline-flex items-center gap-1 bg-gray-800 text-gray-200 text-xs font-bold px-2 py-1 rounded border border-gray-900">
                                <span x-text="email"></span>
                                <button type="button" @click="remove(index)" class="hover:text-red-400"><i class="ph-bold ph-x"></i></button>
                            </span>
                        </template>
                        <input type="text" x-model="newEmail" @keydown.enter.prevent="add" @keydown.space.prevent="add" @paste="handlePaste" placeholder="Add..." class="flex-1 outline-none border-none focus:ring-0 min-w-[100px] text-sm py-1 bg-transparent">
                    </div>
                </div>
            </div>

            <!-- ANEXOS -->
            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                <label class="block text-sm font-bold text-gray-800 mb-2 flex items-center gap-2">
                    <i class="ph-fill ph-paperclip text-purpura-500"></i> Arquivos Anexos (Opcional)
                </label>
                <div class="flex items-center justify-center w-full">
                    <label class="flex flex-col items-center justify-center w-full h-24 border-2 border-dashed border-gray-300 bg-white rounded-lg cursor-pointer hover:bg-gray-50 transition">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <i class="ph ph-upload-simple text-2xl text-gray-400 mb-1"></i>
                            <p class="text-[11px] text-gray-500 font-medium">Clique para selecionar arquivos</p>
                        </div>
                        <input type="file" wire:model="anexos_upload" multiple class="hidden">
                    </label>
                </div>
                
                @if($anexos_upload)
                    <div class="mt-3 space-y-1">
                        @foreach($anexos_upload as $file)
                            <div class="bg-white p-2 border border-gray-200 rounded text-xs font-bold text-gray-600 shadow-sm flex items-center gap-2">
                                <i class="ph-fill ph-file text-purpura-500"></i> {{ $file->getClientOriginalName() }}
                            </div>
                        @endforeach
                    </div>
                @endif
                
                <div wire:loading wire:target="anexos_upload" class="mt-2 text-xs font-bold text-purpura-600">
                    <i class="ph ph-spinner animate-spin"></i> Anexando...
                </div>
                @error('anexos_upload.*') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
            </div>

            <hr class="border-gray-100">

            <!-- AGENDAMENTO -->
            <div>
                <label class="block text-sm font-bold text-gray-800 mb-3 flex items-center gap-2">
                    <i class="ph-fill ph-clock text-purpura-500"></i> Momento do Envio
                </label>
                
                <div class="flex gap-6 mb-4">
                    <label class="inline-flex items-center">
                        <input wire:model.live="tipo_envio" type="radio" value="imediato" class="form-radio text-purpura-600 focus:ring-purpura-500">
                        <span class="ml-2 text-sm font-bold text-gray-700">Envio Imediato</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input wire:model.live="tipo_envio" type="radio" value="agendado" class="form-radio text-purpura-600 focus:ring-purpura-500">
                        <span class="ml-2 text-sm font-bold text-gray-700">Programar Agendamento</span>
                    </label>
                </div>

                @if($tipo_envio === 'agendado')
                    <div class="w-full md:w-1/3 bg-gray-50 border border-gray-200 p-3 rounded-lg">
                        <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1">Data e Hora exata</label>
                        <input wire:model="data_agendamento" type="datetime-local" class="w-full rounded-md border-gray-300 px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500 shadow-sm bg-white">
                        @error('data_agendamento') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                    </div>
                @endif
            </div>

            <!-- BOTÕES -->
            <div class="flex justify-end gap-3 pt-6 mt-6 border-t border-gray-100">
                <a href="{{ route('comunicados.index') }}" class="px-5 py-2.5 text-sm font-bold border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">Cancelar</a>
                
                @if(feature('comunicado.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('comunicado.criar')))
                    @if($tipo_envio === 'agendado')
                        <button type="submit" class="px-6 py-2.5 text-sm font-bold text-white rounded-lg shadow-sm bg-gray-800 hover:bg-gray-900 transition flex items-center gap-2">
                            <i class="ph-bold ph-calendar-plus text-lg"></i> Agendar Envio
                        </button>
                    @else
                        <button type="submit" class="px-6 py-2.5 text-sm font-bold text-white rounded-lg shadow-sm bg-green-600 hover:bg-green-700 transition flex items-center gap-2">
                            <i class="ph-bold ph-paper-plane-tilt text-lg"></i> Salvar e Enviar Agora
                        </button>
                    @endif
                @endif
            </div>
        </form>
    </div>
</div>