<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Central de Análise Manual" 
        icon="ph ph-magnifying-glass"
        badge="Revisão Pendente">
        <x-slot name="actions">
            <!-- Espaço para botões de filtro futuro -->
        </x-slot>
    </x-page-header>

    <div class="mb-4 bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-xl flex items-start gap-3 text-sm">
        <i class="ph-fill ph-warning-circle text-2xl mt-0.5"></i>
        <div>
            <p class="font-bold">Atenção da Secretaria</p>
            <p>Estes documentos falharam na validação automática da IA após 3 tentativas ou o robô ficou em dúvida. Analise a imagem manualmente e decida se o documento é válido.</p>
        </div>
    </div>

    <!-- Tabela de Pendências -->
    <x-table 
        :headers="$this->headers" 
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="false"
        modoExibicao="list">
        
        @forelse($registros as $doc)
            <tr class="hover:bg-gray-50 transition-colors duration-200">
                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-500">#{{ $doc->id }}</td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-purpura-100 text-purpura-600 flex items-center justify-center font-bold">
                            {{ substr($doc->inscricao->nome, 0, 1) }}
                        </div>
                        <div>
                            <p class="font-bold text-gray-900 text-sm">{{ $doc->inscricao->nome }}</p>
                            <p class="text-[11px] text-gray-500 font-mono mt-0.5">CPF: {{ $doc->inscricao->cpf }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <span class="font-bold text-gray-800 text-sm">{{ $doc->documentoExigido->nome }}</span>
                    <span class="block text-[11px] text-gray-500 mt-0.5">Envio: {{ $doc->updated_at->format('d/m/Y H:i') }}</span>
                </td>
                <td class="px-4 py-3 text-xs">
                    <span class="px-2 py-1 rounded bg-red-100 text-red-700 font-bold border border-red-200 flex items-center gap-1 w-max">
                        <i class="ph-bold ph-robot"></i> {{ $doc->tentativas_ia }} tentativas falhas
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <button wire:click="abrirAnalise({{ $doc->id }})" class="px-4 py-2 bg-indigo-500 hover:bg-indigo-600 text-white font-bold text-xs rounded shadow-sm transition flex items-center gap-2 ml-auto">
                        <i class="ph-bold ph-eye"></i> Analisar
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-12 text-center text-gray-500 text-sm">
                    <div class="w-16 h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-3 border border-green-200">
                        <i class="ph-fill ph-check-circle text-3xl text-green-500"></i>
                    </div>
                    <p class="font-bold text-gray-600">Fila Limpa!</p>
                    <p class="text-xs mt-1">Não há nenhum documento aguardando análise humana no momento.</p>
                </td>
            </tr>
        @endforelse
    </x-table>

    <!-- MODAL DE ANÁLISE -->
    @if($modalAnaliseAberto && $documentoSelecionado)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/80 backdrop-blur-sm" wire:click="$set('modalAnaliseAberto', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block w-full max-w-5xl px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-2xl sm:my-8 sm:align-middle sm:p-6">
                    
                    <div class="flex justify-between items-center mb-4 border-b pb-4">
                        <h3 class="text-xl font-black text-gray-900 flex items-center gap-2">
                            <i class="ph-bold ph-scan text-indigo-500"></i> Validação Manual de Documento
                        </h3>
                        <button wire:click="$set('modalAnaliseAberto', false)" class="text-gray-400 hover:text-red-500 transition"><i class="ph-bold ph-x text-2xl"></i></button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- Coluna 1: A Imagem -->
                        <div class="bg-gray-100 rounded-xl border border-gray-200 overflow-hidden flex items-center justify-center min-h-[400px]">
                            @if($imagemBase64)
                                <img src="data:{{ $imagemMime }};base64,{{ $imagemBase64 }}" class="max-w-full max-h-[60vh] object-contain shadow-md" alt="Documento do Candidato">
                            @else
                                <div class="text-gray-400 flex flex-col items-center">
                                    <i class="ph-fill ph-image-broken text-4xl mb-2"></i>
                                    <p>Arquivo não encontrado no servidor.</p>
                                </div>
                            @endif
                        </div>

                        <!-- Coluna 2: Dados e Ações -->
                        <div class="flex flex-col gap-4">
                            
                            <div class="bg-gray-50 border border-gray-200 p-4 rounded-xl shadow-sm">
                                <h4 class="text-[10px] uppercase tracking-wider font-bold text-gray-500 mb-2">Dados Informados na Ficha</h4>
                                <p class="text-sm text-gray-800 mb-1"><b>Nome:</b> {{ $documentoSelecionado->inscricao->nome }}</p>
                                <p class="text-sm text-gray-800 mb-1"><b>CPF:</b> {{ $documentoSelecionado->inscricao->cpf }}</p>
                                <p class="text-sm text-gray-800 mb-1"><b>Curso Vinculado:</b> {{ $documentoSelecionado->inscricao->curso->nome ?? 'N/A' }}</p>
                            </div>

                            <div class="bg-red-50 border border-red-200 p-4 rounded-xl shadow-sm">
                                <h4 class="text-[10px] uppercase tracking-wider font-bold text-red-600 mb-2 flex items-center gap-1"><i class="ph-bold ph-robot"></i> Parecer da IA (Por que falhou?)</h4>
                                <p class="text-xs text-red-800 italic leading-relaxed">
                                    "{{ $documentoSelecionado->log_ia['motivo_rejeicao'] ?? 'A IA não conseguiu identificar os dados ou o arquivo está muito ilegível.' }}"
                                </p>
                            </div>

                            <div class="mt-auto border-t pt-4">
                                <h4 class="text-sm font-bold text-gray-800 mb-3">Veredito da Secretaria</h4>
                                
                                <div x-data="{ acao: '' }">
                                    <div class="flex gap-3 mb-4">
                                        <button @click="acao = 'aprovar'" :class="acao === 'aprovar' ? 'ring-2 ring-offset-2 ring-green-500 bg-green-50 border-green-500' : 'bg-white border-gray-200 hover:bg-gray-50'" class="flex-1 py-3 px-4 border rounded-xl font-bold text-green-700 flex flex-col items-center justify-center gap-1 transition">
                                            <i class="ph-fill ph-check-circle text-2xl"></i> Aprovar (Legível)
                                        </button>
                                        <button @click="acao = 'reprovar'" :class="acao === 'reprovar' ? 'ring-2 ring-offset-2 ring-red-500 bg-red-50 border-red-500' : 'bg-white border-gray-200 hover:bg-gray-50'" class="flex-1 py-3 px-4 border rounded-xl font-bold text-red-700 flex flex-col items-center justify-center gap-1 transition">
                                            <i class="ph-fill ph-x-circle text-2xl"></i> Reprovar (Inválido)
                                        </button>
                                    </div>

                                    <!-- Form de Reprovação (Exige Motivo) -->
                                    <div x-show="acao === 'reprovar'" x-collapse class="mb-4">
                                        <label class="block text-xs font-bold text-gray-600 mb-1">Informe ao candidato o motivo da recusa:</label>
                                        <textarea wire:model="motivoReprovacao" rows="2" class="w-full text-sm rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500" placeholder="Ex: A foto está cortada, por favor envie uma imagem que mostre o documento inteiro."></textarea>
                                        @error('motivoReprovacao') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
                                        
                                        <button wire:click="reprovar" class="w-full mt-3 bg-red-600 hover:bg-red-700 text-white font-bold py-2 rounded-lg shadow-md transition flex items-center justify-center gap-2">
                                            <i class="ph-bold ph-paper-plane-tilt"></i> Confirmar Recusa e Reabrir Portal
                                        </button>
                                    </div>

                                    <!-- Form de Aprovação -->
                                    <div x-show="acao === 'aprovar'" x-collapse class="mb-4">
                                        <button wire:click="aprovar" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg shadow-md transition flex items-center justify-center gap-2">
                                            <i class="ph-bold ph-check"></i> Confirmar Aprovação do Documento
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    @endif

</div>