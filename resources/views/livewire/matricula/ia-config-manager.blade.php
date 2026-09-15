<div class="p-6 max-w-7xl mx-auto font-sans relative" x-data="{ abaAtiva: @entangle('abaAtiva') }">
    <div class="mb-6">
        <h2 class="text-2xl font-black text-gray-900 dark:text-white flex items-center gap-2">
            <i class="ph-fill ph-robot text-purpura-500"></i> Motor de IA e Matrículas
        </h2>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Configure múltiplos provedores de Inteligência Artificial e defina os documentos exigidos.</p>
    </div>

    <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl px-2 pt-2 shadow-sm border border-gray-200 dark:border-gray-700">
        <nav class="flex flex-wrap gap-2 -mb-px">
            <button @click="abaAtiva = 'config'" :class="abaAtiva === 'config' ? 'border-purpura-600 text-purpura-600 dark:text-purpura-400 dark:border-purpura-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400'" class="py-3 px-4 border-b-2 font-bold text-xs flex items-center gap-2 transition-all">
                <i class="ph-bold ph-sliders text-base"></i> Configurações do Motor (LLM)
            </button>
            <button @click="abaAtiva = 'provedores'" :class="abaAtiva === 'provedores' ? 'border-purpura-600 text-purpura-600 dark:text-purpura-400 dark:border-purpura-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400'" class="py-3 px-4 border-b-2 font-bold text-xs flex items-center gap-2 transition-all">
                <i class="ph-bold ph-cpu text-base"></i> Provedores e Modelos
            </button>
            <button @click="abaAtiva = 'documentos'" :class="abaAtiva === 'documentos' ? 'border-purpura-600 text-purpura-600 dark:text-purpura-400 dark:border-purpura-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400'" class="py-3 px-4 border-b-2 font-bold text-xs flex items-center gap-2 transition-all">
                <i class="ph-bold ph-files text-base"></i> Documentos Exigidos
            </button>
        </nav>
    </div>

    <!-- ABA 1: CONFIGURAÇÕES -->
    <div x-show="abaAtiva === 'config'" x-cloak class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm max-w-4xl" wire:key="aba-config">
        <h3 class="font-bold text-gray-800 dark:text-gray-200 mb-4 border-b border-gray-100 dark:border-gray-700 pb-2 flex items-center gap-2">
            <i class="ph-fill ph-check-circle text-purpura-500"></i> Validação Ativa
        </h3>
        
        <form wire:submit.prevent="salvarConfiguracaoIa" class="space-y-6">
            <label class="flex items-center gap-3 cursor-pointer group p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 transition hover:border-purpura-300">
                <input type="checkbox" wire:model="is_ativa" class="h-5 w-5 text-purpura-600 rounded border-gray-300 focus:ring-purpura-500">
                <div class="flex flex-col">
                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100">Ativar validação automática via IA</span>
                    <span class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">Se desativado, todos os documentos irão direto para a fila manual da Secretaria.</span>
                </div>
            </label>

            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1 uppercase tracking-wider">Qual modelo fará as leituras (OCR)?</label>
                <select wire:model="ai_model_id" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-purpura-500 focus:border-purpura-500 py-2.5 font-semibold">
                    <option value="">-- Selecione o Modelo Ativo --</option>
                    @foreach($provedoresBd as $prov)
                        @if($prov->modelos->count() > 0)
                            <optgroup label="{{ $prov->nome }} ({{ ucfirst(str_replace('_', ' ', $prov->driver)) }})">
                                @foreach($prov->modelos as $mod)
                                    <option value="{{ $mod->id }}">{{ $mod->nome }} ({{ $mod->codigo }})</option>
                                @endforeach
                            </optgroup>
                        @endif
                    @endforeach
                </select>
                @error('ai_model_id') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
            </div>
            
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1 uppercase tracking-wider flex justify-between">
                    <span>Engenharia de Prompt Mestre</span>
                </label>
                <textarea wire:model="prompt_documentos" rows="8" class="w-full text-[11px] rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm focus:ring-purpura-500 focus:border-purpura-500 text-gray-700 dark:text-gray-300 font-mono leading-relaxed bg-gray-50"></textarea>
                @error('prompt_documentos') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
            </div>

            <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                <button type="submit" class="px-8 bg-purpura-600 hover:bg-purpura-700 text-white font-bold py-3 rounded-lg shadow-sm transition flex items-center justify-center gap-2">
                    <i class="ph-bold ph-floppy-disk text-lg"></i> Salvar e Aplicar
                </button>
            </div>
        </form>
    </div>

    <!-- ABA 2: PROVEDORES E MODELOS -->
    <div x-show="abaAtiva === 'provedores'" x-cloak class="grid grid-cols-1 lg:grid-cols-2 gap-6" wire:key="aba-provedores">
        <!-- Lista de Provedores -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col">
            <div class="flex justify-between items-center mb-4 border-b border-gray-100 dark:border-gray-700 pb-3">
                <h3 class="font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                    <i class="ph-fill ph-hard-drives text-purpura-500"></i> Provedores Cadastrados
                </h3>
                <button wire:click="abrirModalProvider" class="text-[10px] font-bold uppercase bg-purpura-50 text-purpura-600 dark:bg-purpura-900/30 dark:text-purpura-400 px-3 py-1.5 rounded-lg hover:bg-purpura-100 transition shadow-sm flex items-center gap-1">
                    <i class="ph-bold ph-plus"></i> Novo
                </button>
            </div>
            
            <div class="space-y-3 flex-1 overflow-y-auto custom-scrollbar">
                @forelse($provedoresBd as $prov)
                    <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-purpura-300 transition flex items-center justify-between">
                        <div class="overflow-hidden">
                            <span class="block text-sm font-bold text-gray-900 dark:text-white">{{ $prov->nome }}</span>
                            <span class="block text-[10px] text-gray-500 font-mono mt-0.5 truncate">{{ $prov->api_url ?? 'URL Nativa' }}</span>
                            <span class="inline-block mt-1.5 px-2 py-0.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-[9px] font-bold rounded uppercase">
                                Driver: {{ str_replace('_', ' ', $prov->driver) }}
                            </span>
                        </div>
                        <div class="flex items-center gap-1 shrink-0 ml-3">
                            <button wire:click="abrirModalProvider({{ $prov->id }})" class="p-1.5 text-gray-400 hover:text-blue-500 rounded transition bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-600 shadow-sm"><i class="ph-bold ph-pencil-simple text-sm"></i></button>
                            <button wire:click="excluirProvider({{ $prov->id }})" class="p-1.5 text-gray-400 hover:text-red-500 rounded transition bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-600 shadow-sm" onclick="confirm('Excluir provedor e todos os seus modelos?') || event.stopImmediatePropagation()"><i class="ph-bold ph-trash text-sm"></i></button>
                        </div>
                    </div>
                @empty
                    <div class="text-center p-6 text-gray-400 italic text-sm border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg">Nenhum provedor cadastrado.</div>
                @endforelse
            </div>
        </div>

        <!-- Lista de Modelos -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col">
            <div class="flex justify-between items-center mb-4 border-b border-gray-100 dark:border-gray-700 pb-3">
                <h3 class="font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                    <i class="ph-fill ph-brain text-purpura-500"></i> Modelos Disponíveis
                </h3>
                <button wire:click="abrirModalModel" class="text-[10px] font-bold uppercase bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 px-3 py-1.5 rounded-lg hover:bg-blue-100 transition shadow-sm flex items-center gap-1">
                    <i class="ph-bold ph-plus"></i> Novo
                </button>
            </div>
            
            <div class="space-y-3 flex-1 overflow-y-auto custom-scrollbar">
                @forelse($modelosBd as $mod)
                    <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-300 transition flex items-center justify-between">
                        <div class="overflow-hidden">
                            <span class="block text-xs font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider mb-1">{{ $mod->provider->nome ?? 'Sem Vínculo' }}</span>
                            <span class="block text-sm font-bold text-gray-900 dark:text-white">{{ $mod->nome }}</span>
                            <span class="block text-[10px] text-gray-500 font-mono mt-0.5 truncate">{{ $mod->codigo }}</span>
                        </div>
                        <div class="flex items-center gap-1 shrink-0 ml-3">
                            <button wire:click="abrirModalModel({{ $mod->id }})" class="p-1.5 text-gray-400 hover:text-blue-500 rounded transition bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-600 shadow-sm"><i class="ph-bold ph-pencil-simple text-sm"></i></button>
                            <button wire:click="excluirModel({{ $mod->id }})" class="p-1.5 text-gray-400 hover:text-red-500 rounded transition bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-600 shadow-sm"><i class="ph-bold ph-trash text-sm"></i></button>
                        </div>
                    </div>
                @empty
                    <div class="text-center p-6 text-gray-400 italic text-sm border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg">Nenhum modelo cadastrado.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- ABA 3: DOCUMENTOS (Legado) -->
    <div x-show="abaAtiva === 'documentos'" x-cloak class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col max-w-4xl" wire:key="aba-documentos">
        <h3 class="font-bold text-gray-800 dark:text-gray-200 mb-4 border-b border-gray-100 dark:border-gray-700 pb-2 flex items-center gap-2">
            <i class="ph-fill ph-files text-purpura-500"></i> Documentos Exigidos por Ciclo
        </h3>
        
        <form wire:submit.prevent="adicionarDocumento" class="grid grid-cols-12 gap-3 mb-6 bg-gray-50 dark:bg-gray-900 p-4 rounded-xl border border-gray-100 dark:border-gray-800">
            <div class="col-span-12 md:col-span-5">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">Vincular ao Ciclo</label>
                <select wire:model="cicloSelecionado" class="w-full text-xs font-bold rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Selecione...</option>
                    @foreach($ciclos as $c) <option value="{{ $c->id }}">{{ $c->nome }}</option> @endforeach
                </select>
            </div>
            <div class="col-span-12 md:col-span-7">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">Nome do Documento</label>
                <input type="text" wire:model="nomeDocumento" placeholder="Ex: RG, CNH, CPF..." class="w-full text-xs font-bold rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="col-span-12 md:col-span-9">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">Instrução ao Candidato</label>
                <input type="text" wire:model="descricaoDocumento" placeholder="Ex: Envie frente e verso legível..." class="w-full text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="col-span-12 md:col-span-3 flex items-end">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded shadow-sm text-xs transition flex items-center justify-center gap-1">
                    <i class="ph-bold ph-plus"></i> Adicionar
                </button>
            </div>
        </form>

        <div class="flex-1 overflow-y-auto max-h-[500px] custom-scrollbar border border-gray-200 dark:border-gray-700 rounded-lg">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-100 dark:bg-gray-800 sticky top-0 border-b border-gray-200 dark:border-gray-700 z-10">
                    <tr>
                        <th class="p-3 text-[10px] font-bold uppercase tracking-wider text-gray-500">Documento & Ciclo</th>
                        <th class="p-3 text-[10px] font-bold uppercase tracking-wider text-gray-500 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-900">
                    @forelse($documentosAtuais as $doc)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <td class="p-3">
                                <span class="font-bold text-gray-800 dark:text-gray-200 block">{{ $doc->nome }}</span>
                                <span class="text-[10px] font-medium text-gray-500 mt-0.5 block flex items-center gap-1">
                                    <i class="ph-fill ph-arrows-clockwise text-purpura-500"></i> {{ $doc->ciclo->nome }}
                                </span>
                            </td>
                            <td class="p-3 text-right">
                                <button wire:click="excluirDocumento({{ $doc->id }})" class="text-red-500 hover:bg-red-50 hover:text-red-600 p-1.5 rounded transition" title="Remover Exigência">
                                    <i class="ph-bold ph-trash text-lg"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="p-8 text-center text-gray-400 italic text-sm">
                                <i class="ph-fill ph-files text-3xl mb-2 text-gray-300 block"></i>
                                Nenhum documento exigido cadastrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL PROVEDOR -->
    @if($modalProviderAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 border-b border-gray-100 dark:border-gray-700 pb-2">
                    <i class="ph-fill ph-hard-drives text-purpura-500"></i> Provedor
                </h3>
                
                <form wire:submit.prevent="salvarProvider" class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nome de Exibição <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="provider_nome" placeholder="Ex: OpenAI" class="w-full text-xs font-bold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 focus:ring-purpura-500">
                        @error('provider_nome') <span class="text-[10px] text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Driver de Conexão <span class="text-red-500">*</span></label>
                        <select wire:model="provider_driver" class="w-full text-xs font-bold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 focus:ring-purpura-500">
                            <option value="openai_compatible">OpenAI Compatible (ChatGPT, Grok, DeepSeek...)</option>
                            <option value="gemini">Google Gemini Nativo</option>
                        </select>
                        @error('provider_driver') <span class="text-[10px] text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">API Base URL (Opcional)</label>
                        <input type="text" wire:model="provider_api_url" placeholder="https://api.openai.com/v1" class="w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 focus:ring-purpura-500">
                        <span class="text-[9px] text-gray-400 mt-0.5 block leading-tight">Deixe vazio para usar a URL oficial do Driver. Para Grok use: https://api.x.ai/v1</span>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">API Key / Token Secreto</label>
                        <input type="password" wire:model="provider_api_key" placeholder="sk-..." class="w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 focus:ring-purpura-500">
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-gray-100 dark:border-gray-700 mt-4">
                        <button type="button" wire:click="$set('modalProviderAberto', false)" class="px-4 py-2 border rounded-lg text-xs font-bold text-gray-600 hover:bg-gray-50">Cancelar</button>
                        <button type="submit" class="px-5 py-2 bg-purpura-600 text-white rounded-lg text-xs font-bold hover:bg-purpura-700 shadow-sm">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- MODAL MODELO -->
    @if($modalModelAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 border-b border-gray-100 dark:border-gray-700 pb-2">
                    <i class="ph-fill ph-brain text-purpura-500"></i> Modelo
                </h3>
                
                <form wire:submit.prevent="salvarModel" class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Provedor Vinculado <span class="text-red-500">*</span></label>
                        <select wire:model="model_ai_provider_id" class="w-full text-xs font-bold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 focus:ring-purpura-500">
                            <option value="">Selecione...</option>
                            @foreach($provedoresBd as $p) <option value="{{ $p->id }}">{{ $p->nome }}</option> @endforeach
                        </select>
                        @error('model_ai_provider_id') <span class="text-[10px] text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nome do Modelo <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="model_nome" placeholder="Ex: GPT-4o Mini" class="w-full text-xs font-bold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 focus:ring-purpura-500">
                        @error('model_nome') <span class="text-[10px] text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Código Oficial (API) <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="model_codigo" placeholder="Ex: gpt-4o-mini" class="w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-2 focus:ring-purpura-500">
                        @error('model_codigo') <span class="text-[10px] text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-gray-100 dark:border-gray-700 mt-4">
                        <button type="button" wire:click="$set('modalModelAberto', false)" class="px-4 py-2 border rounded-lg text-xs font-bold text-gray-600 hover:bg-gray-50">Cancelar</button>
                        <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-lg text-xs font-bold hover:bg-blue-700 shadow-sm">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>