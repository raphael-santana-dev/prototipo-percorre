<div class="p-6 mx-auto font-sans relative max-w-7xl">
    
    <x-page-header 
        title="Central de Análise Manual" 
        icon="ph ph-magnifying-glass"
        badge="Revisão Pendente">
    </x-page-header>

    <div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-xl flex items-start gap-3 text-sm shadow-sm">
        <i class="ph-fill ph-warning-circle text-2xl mt-0.5 text-yellow-600"></i>
        <div>
            <p class="font-bold">Atenção da Secretaria</p>
            <p>Estes documentos falharam na validação automática da IA após 3 tentativas ou o robô ficou em dúvida. Analise as imagens manualmente.</p>
        </div>
    </div>

    <x-table 
        :headers="$this->headers" 
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid"
        :modoExibicao="$modoExibicao">
        
        @forelse($registros as $doc)
            <tr wire:key="linha-manual-{{ $doc->id }}" class="bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors">
                <td class="px-4 py-2.5 font-medium text-gray-500 text-xs whitespace-nowrap text-center">#{{ $doc->id }}</td>
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="font-bold text-gray-900 text-sm">{{ $doc->inscricao->nome }}</div>
                    <div class="text-[11px] text-gray-500 mt-0.5">CPF: {{ $doc->inscricao->cpf }}</div>
                </td>
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <span class="font-bold text-gray-700 text-sm block">{{ $doc->documentoExigido->nome }}</span>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mt-0.5 block">Envio: {{ $doc->updated_at->format('d/m/y H:i') }}</span>
                </td>
                <td class="px-4 py-2.5 text-center whitespace-nowrap">
                    <span class="px-2 py-0.5 rounded-full bg-red-50 text-red-700 font-bold border border-red-200 inline-flex items-center gap-1 text-[10px] uppercase tracking-wider">
                        <i class="ph-bold ph-robot"></i> {{ $doc->tentativas_ia }} Falhas
                    </span>
                </td>
                <td class="px-4 py-2.5 text-right whitespace-nowrap">
                    <button wire:click="abrirAnalise({{ $doc->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-gray-600" title="Analisar Documento">
                        <i class="text-xl ph-bold ph-eye"></i>
                    </button>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-12 text-center text-gray-500">Nenhum documento pendente de análise manual.</td></tr>
        @endforelse

        <x-slot name="gridSlot">
            @foreach($registros as $doc)
                <div class="flex flex-col p-4 bg-white border border-gray-100 shadow-sm rounded-xl hover:shadow-md transition-shadow">
                    <div class="flex justify-between mb-2">
                        <span class="text-xs font-medium text-gray-500">#{{ $doc->id }}</span>
                        <button wire:click="abrirAnalise({{ $doc->id }})" class="text-indigo-600 hover:text-indigo-800"><i class="text-xl ph-bold ph-eye"></i></button>
                    </div>
                    <h4 class="text-sm font-bold text-gray-900 truncate">{{ $doc->inscricao->nome }}</h4>
                    <p class="text-[11px] font-bold text-gray-500 mt-1 uppercase tracking-wider">{{ $doc->documentoExigido->nome }}</p>
                </div>
            @endforeach
        </x-slot>
    </x-table>

    <!-- MODAL DE ANÁLISE -->
    @if($modalAnaliseAberto && $documentoSelecionado)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-gray-50 rounded-xl shadow-2xl w-full max-w-5xl overflow-hidden flex flex-col max-h-[90vh]">
                
                <div class="bg-white p-5 border-b border-gray-200 flex justify-between items-center shrink-0">
                    <h3 class="text-lg font-black text-gray-900 flex items-center gap-2">
                        <i class="ph-bold ph-scan text-indigo-500"></i> Validação Manual de Documento
                    </h3>
                    <button wire:click="$set('modalAnaliseAberto', false)" class="text-gray-400 hover:text-red-500 transition"><i class="ph-bold ph-x text-2xl"></i></button>
                </div>

                <div class="p-6 overflow-y-auto custom-scrollbar flex-1">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <div class="bg-white p-2 rounded-xl shadow-sm border border-gray-200 flex items-center justify-center min-h-[400px]">
                            @if($imagemBase64)
                                <img src="data:{{ $imagemMime }};base64,{{ $imagemBase64 }}" class="max-w-full max-h-[60vh] object-contain rounded-lg" alt="Documento">
                            @else
                                <div class="text-gray-400 text-center">
                                    <i class="ph-fill ph-image-broken text-4xl mb-2"></i>
                                    <p class="text-sm font-bold">Arquivo Inacessível</p>
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-col gap-4">
                            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
                                <h3 class="text-[10px] font-bold tracking-wider text-gray-500 uppercase flex items-center gap-2 mb-3 border-b border-gray-100 pb-2">
                                    <i class="ph-fill ph-identification-card text-lg text-purpura-500"></i> Ficha do Candidato
                                </h3>
                                <p class="text-sm text-gray-800 mb-1"><b>Nome:</b> {{ $documentoSelecionado->inscricao->nome }}</p>
                                <p class="text-sm text-gray-800 mb-1"><b>CPF:</b> {{ $documentoSelecionado->inscricao->cpf }}</p>
                                <p class="text-sm text-gray-800 mb-1"><b>Curso:</b> {{ $documentoSelecionado->inscricao->curso->nome ?? 'N/A' }}</p>
                            </div>

                            <div class="bg-white p-5 rounded-xl shadow-sm border border-red-200">
                                <h3 class="text-[10px] font-bold tracking-wider text-red-600 uppercase flex items-center gap-2 mb-2 border-b border-red-100 pb-2">
                                    <i class="ph-fill ph-robot text-lg text-red-500"></i> Parecer da Inteligência Artificial
                                </h3>
                                <p class="text-xs text-gray-600 leading-relaxed font-mono bg-red-50 p-3 rounded-lg border border-red-100">
                                    > {{ $documentoSelecionado->log_ia['motivo_rejeicao'] ?? 'A IA não conseguiu ler.' }}
                                </p>
                            </div>

                            <div class="mt-auto bg-white p-5 rounded-xl shadow-sm border border-gray-200" x-data="{ acao: '' }">
                                <h3 class="text-[10px] font-bold tracking-wider text-gray-500 uppercase flex items-center gap-2 mb-3">Veredito da Secretaria</h3>
                                
                                <div class="flex gap-3 mb-4">
                                    <button @click="acao = 'aprovar'" :class="acao === 'aprovar' ? 'ring-2 ring-offset-2 ring-green-500 bg-green-50 border-green-500' : 'bg-gray-50 border-gray-200 hover:bg-gray-100'" class="flex-1 py-3 px-4 border rounded-lg font-bold text-green-700 flex flex-col items-center gap-1 transition text-xs uppercase tracking-wider">
                                        <i class="ph-fill ph-check-circle text-2xl"></i> Aprovar
                                    </button>
                                    <button @click="acao = 'reprovar'" :class="acao === 'reprovar' ? 'ring-2 ring-offset-2 ring-red-500 bg-red-50 border-red-500' : 'bg-gray-50 border-gray-200 hover:bg-gray-100'" class="flex-1 py-3 px-4 border rounded-lg font-bold text-red-700 flex flex-col items-center gap-1 transition text-xs uppercase tracking-wider">
                                        <i class="ph-fill ph-x-circle text-2xl"></i> Reprovar
                                    </button>
                                </div>

                                <div x-show="acao === 'reprovar'" x-collapse x-cloak>
                                    <textarea wire:model="motivoReprovacao" rows="2" class="w-full text-sm rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500 shadow-sm" placeholder="Motivo para o candidato corrigir..."></textarea>
                                    @error('motivoReprovacao') <span class="text-[10px] text-red-500 font-bold block mt-1 uppercase">{{ $message }}</span> @enderror
                                    <button wire:click="reprovar" class="w-full mt-3 bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 rounded-lg shadow-sm transition text-xs uppercase tracking-wider">
                                        Confirmar Recusa
                                    </button>
                                </div>

                                <div x-show="acao === 'aprovar'" x-collapse x-cloak>
                                    <button wire:click="aprovar" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 rounded-lg shadow-sm transition text-xs uppercase tracking-wider">
                                        Confirmar Aprovação
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>