<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header title="Ciclos de Aprendizagem" icon="ph ph-books" badge="Gestão Educacional">
        <x-slot name="actions">
            <button wire:click="abrirModal" class="px-4 py-2 text-sm font-bold text-white bg-purpura-600 rounded-lg shadow-sm hover:bg-purpura-700 transition flex items-center gap-2">
                <i class="ph-bold ph-plus"></i> Novo Ciclo
            </button>
        </x-slot>
    </x-page-header>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-gray-900/80 border-b border-gray-200 dark:border-gray-700">
                <tr>
                    <th class="px-4 py-3 font-bold text-[10px] text-gray-500 uppercase tracking-wider">Ciclo</th>
                    <th class="px-4 py-3 font-bold text-[10px] text-gray-500 uppercase tracking-wider text-center">Fases (Workflow)</th>
                    <th class="px-4 py-3 font-bold text-[10px] text-gray-500 uppercase tracking-wider text-center">Período</th>
                    <th class="px-4 py-3 font-bold text-[10px] text-gray-500 uppercase tracking-wider text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($ciclos as $ciclo)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-bold text-gray-900">{{ $ciclo->nome }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2.5 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-full">
                                {{ $ciclo->fases->count() }} Fases
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-xs text-gray-500">
                            {{ $ciclo->data_inicio ? $ciclo->data_inicio->format('d/m/Y') : '-' }} a {{ $ciclo->data_fim ? $ciclo->data_fim->format('d/m/Y') : '-' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                
                                @php
                                    // Busca todos os formulários vinculados às fases deste ciclo
                                    $formsPreview = \App\Models\Formulario::whereHas('faseAprendizagem', function($q) use ($ciclo) {
                                        $q->where('ciclo_aprendizagem_id', $ciclo->id);
                                    })->get();
                                @endphp

                                <!-- Pré-visualização do(s) Formulário(s) -->
                                @if($formsPreview->count() === 1)
                                    <a href="{{ route('formularios.publico', ['id' => $formsPreview->first()->id, 'slug' => $formsPreview->first()->slug, 'preview' => 'true']) }}" target="_blank" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50" title="Pré-visualizar Formulário">
                                        <i class="text-lg ph ph-eye"></i>
                                    </a>
                                @elseif($formsPreview->count() > 1)
                                    <div x-data="{ open: false }" class="relative inline-block text-left">
                                        <button @click="open = !open" @click.outside="open = false" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50 flex items-center gap-0.5" title="Pré-visualizar Formulários">
                                            <i class="text-lg ph ph-eye"></i>
                                            <i class="ph-bold ph-caret-down text-[10px]"></i>
                                        </button>
                                        
                                        <div x-show="open" x-cloak x-transition class="absolute right-0 mt-1 w-56 bg-white border border-gray-200 rounded-lg shadow-xl z-50 overflow-hidden">
                                            <div class="px-3 py-2 border-b border-gray-100 bg-gray-50">
                                                <span class="text-[10px] font-bold text-gray-500 uppercase">Selecione a Fase</span>
                                            </div>
                                            @foreach($formsPreview as $fPreview)
                                                <a href="{{ route('formularios.publico', ['id' => $fPreview->id, 'slug' => $fPreview->slug, 'preview' => 'true']) }}" target="_blank" class="block px-4 py-2 text-xs font-bold text-gray-700 hover:bg-blue-50 hover:text-blue-700 border-b border-gray-100 last:border-0 transition truncate">
                                                    {{ $fPreview->titulo }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- Botão de Acompanhamento -->
                                <a href="{{ route('acompanhamento.index', $ciclo->id) }}" wire:navigate class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 text-xs font-bold rounded-lg transition shadow-sm">
                                    <i class="ph-bold ph-presentation-chart text-sm"></i>
                                    Acompanhar
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">Nenhum ciclo cadastrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- MODAL DE CRIAÇÃO -->
    @if($modalAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-gray-900 w-full max-w-2xl rounded-2xl shadow-2xl flex flex-col max-h-[90vh] overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-900">Novo Ciclo de Aprendizagem</h3>
                    <button wire:click="fecharModal" class="text-gray-400 hover:text-red-500"><i class="ph-bold ph-x text-xl"></i></button>
                </div>
                
                <div class="p-6 overflow-y-auto flex-1 custom-scrollbar space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-700 mb-1">Nome do Ciclo <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="nome" placeholder="Ex: Avaliação Semestral 2026.2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500">
                            @error('nome') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Data Início</label>
                            <input type="date" wire:model="data_inicio" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-purpura-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Data Fim</label>
                            <input type="date" wire:model="data_fim" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-purpura-500">
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-6">
                        <div class="flex justify-between items-end mb-4">
                            <div>
                                <h4 class="font-bold text-gray-800">Fases do Workflow</h4>
                                <p class="text-xs text-gray-500">Defina a ordem e quem deve responder cada etapa da avaliação.</p>
                            </div>
                            <button wire:click="adicionarFase" type="button" class="text-xs font-bold text-purpura-600 bg-purpura-50 px-3 py-1.5 rounded-md hover:bg-purpura-100 transition">
                                + Adicionar Fase
                            </button>
                        </div>

                        <div class="space-y-4">
                            @foreach($fases as $index => $fase)
                                <div class="p-4 border border-gray-200 rounded-lg bg-gray-50 flex gap-4 items-start">
                                    <div class="flex-1 space-y-3">
                                        <input type="text" wire:model="fases.{{ $index }}.nome" placeholder="Nome da Fase (Ex: Autoavaliação)" class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500">
                                        @error("fases.{$index}.nome") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                        
                                        <div class="pt-2">
                                            <p class="text-[10px] font-bold text-gray-500 uppercase mb-2">Quem deve preencher esta fase?</p>
                                            <div class="flex gap-4 flex-wrap">
                                                @foreach($opcoesRespondedores as $key => $label)
                                                    <label class="flex items-center gap-2 cursor-pointer bg-white px-2 py-1 rounded border border-gray-200 shadow-sm">
                                                        <input type="checkbox" wire:model="fases.{{ $index }}.respondedores.{{ $key }}" value="1" class="text-purpura-600 focus:ring-purpura-500 rounded border-gray-300">
                                                        <span class="text-xs font-bold text-gray-700">{{ $label }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            @error("fases.{$index}.respondedores") <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                    <button wire:click="removerFase({{ $index }})" type="button" class="text-gray-400 hover:text-red-500 transition mt-1" {{ count($fases) == 1 ? 'disabled' : '' }}>
                                        <i class="ph-bold ph-trash text-lg"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                
                <div class="p-5 border-t border-gray-100 bg-gray-50 flex justify-end gap-3">
                    <button wire:click="fecharModal" class="px-4 py-2 text-sm font-bold text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancelar</button>
                    <button wire:click="salvar" class="px-4 py-2 text-sm font-bold text-white bg-purpura-600 rounded-lg shadow-sm hover:bg-purpura-700 transition">Salvar Ciclo</button>
                </div>
            </div>
        </div>
    @endif
</div>