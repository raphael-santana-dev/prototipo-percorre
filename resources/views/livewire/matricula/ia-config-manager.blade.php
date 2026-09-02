<div class="p-6 max-w-7xl mx-auto font-sans relative">
    <div class="mb-8">
        <h2 class="text-2xl font-black text-gray-900 flex items-center gap-2"><i class="ph-fill ph-robot text-purpura-500"></i> Motor de IA e Matrículas</h2>
        <p class="text-xs text-gray-500">Configure a Inteligência Artificial e defina quais documentos os alunos devem enviar no portal.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Bloco 1: Configuração da IA -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <h3 class="font-bold text-gray-800 dark:text-gray-200 mb-4 border-b border-gray-100 dark:border-gray-700 pb-2 flex items-center gap-2">
                <i class="ph-fill ph-cpu text-purpura-500"></i> Credenciais e Prompt (LLM)
            </h3>
            
            <form wire:submit.prevent="salvarConfiguracaoIa" class="space-y-5">
                <label class="flex items-center gap-3 mb-2 cursor-pointer group p-3 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 transition hover:bg-gray-100">
                    <input type="checkbox" wire:model="is_ativa" class="h-4 w-4 text-purpura-600 rounded border-gray-300 focus:ring-purpura-500">
                    <span class="text-sm font-bold text-gray-700 dark:text-gray-300">Ativar validação automática via IA para os candidatos</span>
                </label>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1 uppercase tracking-wider">Provedor de IA</label>
                        <select wire:model="provedor" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500">
                            <option value="gemini">Google Gemini (Recomendado)</option>
                            <option value="openai">ChatGPT (OpenAI)</option>
                            <option value="claude">Claude (Anthropic)</option>
                            <option value="deepseek">DeepSeek</option>
                            <option value="grok">Grok (xAI)</option>
                        </select>
                        @error('provedor') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1 uppercase tracking-wider">API Key Secreta</label>
                        <input type="password" wire:model="api_key" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500" placeholder="sk-...">
                        @error('api_key') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1 uppercase tracking-wider">Engenharia de Prompt (Regras de OCR)</label>
                    <textarea wire:model="prompt_documentos" rows="8" class="w-full text-[11px] rounded-lg border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500 text-gray-700 font-mono leading-relaxed bg-gray-50"></textarea>
                    @error('prompt_documentos') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="pt-2 border-t border-gray-100 dark:border-gray-700">
                    <button type="submit" class="w-full bg-purpura-600 hover:bg-purpura-700 text-white font-bold py-2.5 rounded-lg shadow-sm transition flex items-center justify-center gap-2">
                        <i class="ph-bold ph-floppy-disk"></i> Salvar Configurações da IA
                    </button>
                </div>
            </form>
        </div>

        <!-- Bloco 2: Documentos Exigidos por Ciclo -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col h-full">
            <h3 class="font-bold text-gray-800 dark:text-gray-200 mb-4 border-b border-gray-100 dark:border-gray-700 pb-2 flex items-center gap-2">
                <i class="ph-fill ph-files text-purpura-500"></i> Documentos Exigidos por Ciclo
            </h3>
            
            <form wire:submit.prevent="adicionarDocumento" class="grid grid-cols-12 gap-3 mb-6 bg-gray-50 dark:bg-gray-900 p-4 rounded-xl border border-gray-100 dark:border-gray-800">
                <div class="col-span-12 md:col-span-5">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">Vincular ao Ciclo</label>
                    <select wire:model="cicloSelecionado" class="w-full text-xs font-bold rounded border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Selecione...</option>
                        @foreach($ciclos as $c) <option value="{{ $c->id }}">{{ $c->nome }}</option> @endforeach
                    </select>
                </div>
                <div class="col-span-12 md:col-span-7">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">Nome do Documento</label>
                    <input type="text" wire:model="nomeDocumento" placeholder="Ex: RG, CNH, CPF..." class="w-full text-xs font-bold rounded border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="col-span-12 md:col-span-9">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-1">Instrução ao Candidato</label>
                    <input type="text" wire:model="descricaoDocumento" placeholder="Ex: Envie frente e verso legível..." class="w-full text-xs rounded border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="col-span-12 md:col-span-3 flex items-end">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded shadow-sm text-xs transition flex items-center justify-center gap-1">
                        <i class="ph-bold ph-plus"></i> Adicionar
                    </button>
                </div>
            </form>

            <div class="flex-1 overflow-y-auto max-h-[400px] custom-scrollbar border border-gray-200 dark:border-gray-700 rounded-lg">
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
    </div>
</div>