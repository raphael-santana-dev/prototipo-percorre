<div class="p-6 max-w-4xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Disparo de Comunicado" 
        icon="ph ph-paper-plane-tilt"
        badge="">
        <x-slot name="actions">
            <a href="{{ route('comunicados.index') }}" class="px-4 py-2 text-sm font-bold text-gray-500 hover:text-gray-800 transition dark:text-gray-400 dark:hover:text-gray-200 flex items-center gap-2">
                <i class="ph-bold ph-arrow-left"></i> Histórico
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

    <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-200 mt-4">
        <form wire:submit.prevent="salvar" class="space-y-8">
            
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                    <i class="ph-fill ph-layout text-purpura-500"></i> Template Selecionado <span class="text-red-500">*</span>
                </label>
                <select wire:model="template_id" class="w-full rounded-lg border-gray-200 px-4 py-2.5 text-sm focus:ring-purpura-500 focus:border-purpura-500 transition shadow-sm bg-white">
                    <option value="">Selecione o layout do e-mail...</option>
                    @foreach($templates as $tpl)
                        <option value="{{ $tpl->id }}">{{ $tpl->nome }} (Assunto: {{ $tpl->assunto }})</option>
                    @endforeach
                </select>
                @error('template_id') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
            </div>

            <div class="pt-6 border-t border-gray-100">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-1.5">
                    <i class="ph-fill ph-users text-purpura-500"></i> Seleção de Destinatários
                </label>

                <div class="flex gap-6 mb-5 border-b border-gray-100 pb-1">
                    <button type="button" wire:click="$set('modo_selecao', 'manual')" class="pb-2 text-sm font-medium transition-colors border-b-2 {{ $modo_selecao === 'manual' ? 'border-gray-900 text-gray-900 font-bold' : 'border-transparent text-gray-400 hover:text-gray-700' }}">
                        Inserção Manual
                    </button>
                    <button type="button" wire:click="$set('modo_selecao', 'dinamico')" class="pb-2 text-sm font-medium transition-colors border-b-2 {{ $modo_selecao === 'dinamico' ? 'border-gray-900 text-gray-900 font-bold' : 'border-transparent text-gray-400 hover:text-gray-700' }}">
                        Filtro Dinâmico (Base de Dados)
                    </button>
                </div>

                @if($modo_selecao === 'manual')
                    <div x-data="emailTags(@entangle('destinatarios'))" class="space-y-2">
                        <p class="text-xs text-gray-400 font-medium">Digite o e-mail e aperte <kbd class="bg-gray-100 border border-gray-200 px-1 rounded text-gray-600">Enter</kbd> ou cole uma lista.</p>
                        
                        <div class="w-full min-h-[46px] border border-gray-200 rounded-lg p-2 flex flex-wrap gap-1.5 focus-within:ring-1 focus-within:ring-purpura-500 focus-within:border-purpura-500 bg-white transition shadow-sm">
                            <template x-for="(email, index) in emails" :key="index">
                                <span class="inline-flex items-center gap-1.5 bg-gray-50 text-gray-700 text-xs font-bold px-3 py-1 rounded border border-gray-200">
                                    <span x-text="email"></span>
                                    <button type="button" @click="remove(index)" class="text-gray-400 hover:text-red-500 transition"><i class="ph-bold ph-x"></i></button>
                                </span>
                            </template>
                            <input type="email" x-model="newEmail" @keydown.enter.prevent="add" @keydown.space.prevent="add" @paste="handlePaste" placeholder="adicionar@email.com..." class="flex-1 outline-none border-none focus:ring-0 min-w-[200px] text-sm py-1 bg-transparent text-gray-700">
                        </div>
                    </div>
                @endif

                @if($modo_selecao === 'dinamico')
                    <div class="space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Público Alvo</label>
                                <select wire:model.live="filtro_publico" class="w-full rounded-lg border-gray-200 px-4 py-2.5 text-sm focus:ring-purpura-500 focus:border-purpura-500 transition shadow-sm bg-white">
                                    <option value="">Selecione...</option>
                                    <option value="todos">Todos os Usuários Cadastrados</option>
                                    <option value="grupo">Usuários de um Grupo (Role)</option>
                                    <option value="unidade">Estudantes inscritos em uma Unidade</option>
                                    <option value="curso">Estudantes inscritos em um Curso</option>
                                </select>
                            </div>

                            @if($filtro_publico === 'grupo')
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Grupo (Role)</label>
                                    <select wire:model="filtro_role" class="w-full rounded-lg border-gray-200 px-4 py-2.5 text-sm focus:ring-purpura-500 focus:border-purpura-500 transition shadow-sm bg-white">
                                        <option value="">Selecione...</option>
                                        @foreach($rolesDisponiveis as $role)
                                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @elseif($filtro_publico === 'unidade')
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Unidade Alvo</label>
                                    <select wire:model="filtro_unidade" class="w-full rounded-lg border-gray-200 px-4 py-2.5 text-sm focus:ring-purpura-500 focus:border-purpura-500 transition shadow-sm bg-white">
                                        <option value="">Selecione...</option>
                                        @foreach($unidadesDisponiveis as $unidade)
                                            <option value="{{ $unidade->id }}">{{ $unidade->nome }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @elseif($filtro_publico === 'curso')
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Curso Alvo</label>
                                    <select wire:model="filtro_curso" class="w-full rounded-lg border-gray-200 px-4 py-2.5 text-sm focus:ring-purpura-500 focus:border-purpura-500 transition shadow-sm bg-white">
                                        <option value="">Selecione...</option>
                                        @foreach($cursosDisponiveis as $curso)
                                            <option value="{{ $curso->id }}">{{ $curso->nome }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
                @error('destinatarios') <span class="text-xs text-red-500 font-bold block mt-2">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                <div x-data="emailTags(@entangle('cc'))">
                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Cópia (CC) - Opcional</label>
                    <div class="w-full min-h-[42px] border border-gray-200 rounded-lg p-1.5 flex flex-wrap gap-1 focus-within:border-gray-400 bg-white transition shadow-sm">
                        <template x-for="(email, index) in emails" :key="index">
                            <span class="inline-flex items-center gap-1 bg-gray-50 text-gray-600 text-[11px] font-bold px-2 py-1 rounded border border-gray-200">
                                <span x-text="email"></span>
                                <button type="button" @click="remove(index)" class="text-gray-400 hover:text-red-500"><i class="ph-bold ph-x"></i></button>
                            </span>
                        </template>
                        <input type="text" x-model="newEmail" @keydown.enter.prevent="add" @keydown.space.prevent="add" @paste="handlePaste" placeholder="Add..." class="flex-1 outline-none border-none focus:ring-0 min-w-[100px] text-sm py-1 bg-transparent">
                    </div>
                </div>

                <div x-data="emailTags(@entangle('bcc'))">
                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Cópia Oculta (CCO) - Opcional</label>
                    <div class="w-full min-h-[42px] border border-gray-200 rounded-lg p-1.5 flex flex-wrap gap-1 focus-within:border-gray-400 bg-white transition shadow-sm">
                        <template x-for="(email, index) in emails" :key="index">
                            <span class="inline-flex items-center gap-1 bg-gray-50 text-gray-600 text-[11px] font-bold px-2 py-1 rounded border border-gray-200">
                                <span x-text="email"></span>
                                <button type="button" @click="remove(index)" class="text-gray-400 hover:text-red-500"><i class="ph-bold ph-x"></i></button>
                            </span>
                        </template>
                        <input type="text" x-model="newEmail" @keydown.enter.prevent="add" @keydown.space.prevent="add" @paste="handlePaste" placeholder="Add..." class="flex-1 outline-none border-none focus:ring-0 min-w-[100px] text-sm py-1 bg-transparent">
                    </div>
                </div>
            </div>

            <div class="pt-6 border-t border-gray-100">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                    <i class="ph-fill ph-paperclip text-purpura-500"></i> Anexos Adicionais
                </label>
                <div class="flex items-center w-full">
                    <label class="flex flex-col items-center justify-center w-full h-20 border border-dashed border-gray-300 bg-gray-50/50 hover:bg-gray-50 rounded-lg cursor-pointer transition">
                        <div class="flex flex-col items-center justify-center pt-4 pb-4">
                            <i class="ph ph-upload-simple text-xl text-gray-400 mb-1"></i>
                            <p class="text-[11px] text-gray-500 font-medium">Anexar ficheiros (Opcional)</p>
                        </div>
                        <input type="file" wire:model="anexos_upload" multiple class="hidden">
                    </label>
                </div>
                
                @if($anexos_upload)
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($anexos_upload as $file)
                            <div class="bg-gray-50 px-3 py-1.5 border border-gray-200 rounded-md text-xs font-bold text-gray-600 flex items-center gap-2">
                                <i class="ph-fill ph-file text-gray-400"></i> {{ $file->getClientOriginalName() }}
                            </div>
                        @endforeach
                    </div>
                @endif
                
                <div wire:loading wire:target="anexos_upload" class="mt-2 text-xs font-bold text-purpura-600">
                    <i class="ph ph-spinner animate-spin"></i> A anexar...
                </div>
                @error('anexos_upload.*') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
            </div>

            <div class="pt-6 border-t border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-6 items-end">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                        <i class="ph-fill ph-clock text-purpura-500"></i> Momento do Envio
                    </label>
                    <div class="flex gap-6">
                        <label class="inline-flex items-center cursor-pointer">
                            <input wire:model.live="tipo_envio" type="radio" value="imediato" class="form-radio text-purpura-600 focus:ring-purpura-500">
                            <span class="ml-2 text-sm font-medium text-gray-800">Imediato</span>
                        </label>
                        <label class="inline-flex items-center cursor-pointer">
                            <input wire:model.live="tipo_envio" type="radio" value="agendado" class="form-radio text-purpura-600 focus:ring-purpura-500">
                            <span class="ml-2 text-sm font-medium text-gray-800">Agendado</span>
                        </label>
                    </div>
                </div>

                @if($tipo_envio === 'agendado')
                    <div>
                        <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Data e Hora exata</label>
                        <input wire:model="data_agendamento" type="datetime-local" class="w-full rounded-lg border-gray-200 px-4 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500 shadow-sm bg-white">
                        @error('data_agendamento') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                    </div>
                @endif
            </div>

            <div class="flex justify-end gap-4 pt-8 mt-2 border-t border-gray-100">
                <a href="{{ route('comunicados.index') }}" class="px-5 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-800 transition">Cancelar</a>
                
                @if(feature('comunicado.criar') && (auth()->user()->hasRole('dev') || auth()->user()->can('comunicado.criar')))
                    @if($tipo_envio === 'agendado')
                        <button type="submit" class="px-6 py-2.5 text-sm font-bold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg shadow-sm hover:bg-gray-200 transition flex items-center gap-2">
                            <i class="ph-bold ph-calendar-plus text-lg"></i> Programar Envio
                        </button>
                    @else
                        <button type="submit" class="px-6 py-2.5 text-sm font-bold text-white rounded-lg shadow-sm bg-gray-900 hover:bg-black transition flex items-center gap-2">
                            <i class="ph-bold ph-paper-plane-tilt text-lg"></i> Disparar Agora
                        </button>
                    @endif
                @endif
            </div>
        </form>
    </div>
</div>