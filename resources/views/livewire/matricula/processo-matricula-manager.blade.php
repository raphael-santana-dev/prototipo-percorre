<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Acompanhamento de Matrículas" 
        icon="ph ph-folder-user"
        badge="Portal de Envio">
        <x-slot name="filters" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-1">
                <input type="text" wire:model.live.debounce.500ms="termoBusca" placeholder="Buscar por nome ou CPF..." class="w-full px-3 py-2 text-sm border-gray-300 rounded-lg shadow-sm focus:ring-purpura-500 focus:border-purpura-500">
            </div>
        </x-slot>
    </x-page-header>

    <!-- TABELA DE ACOMPANHAMENTO -->
    <x-table 
        :headers="$this->headers" 
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="false"
        modoExibicao="list">
        
        @forelse($registros as $inscricao)
            <tr class="hover:bg-gray-50 transition-colors duration-200">
                <td class="px-4 py-3 text-sm font-medium text-gray-500">#{{ $inscricao->id }}</td>
                <td class="px-4 py-3">
                    <p class="font-bold text-gray-900 text-sm">{{ $inscricao->nome }}</p>
                    <p class="text-[11px] text-gray-500 mt-0.5"><i class="ph-fill ph-graduation-cap text-purpura-500"></i> {{ $inscricao->curso->nome ?? 'Sem Curso' }}</p>
                </td>
                
                <td class="px-4 py-3 text-center">
                    @php
                        // Cálculos rápidos para a barra de progresso
                        $totalExigido = \App\Modules\Matricula\Domain\Models\DocumentoExigido::where('ciclo_id', $inscricao->ciclo_id)->where('is_obrigatorio', true)->count();
                        
                        $aprovados = \App\Modules\Matricula\Domain\Models\DocumentoMatricula::where('inscricao_id', $inscricao->id)
                            ->whereIn('status_analise', ['valido_ia', 'aprovado_manual'])
                            ->whereHas('documentoExigido', function($q) { $q->where('is_obrigatorio', true); })
                            ->count();
                            
                        $porcentagem = $totalExigido > 0 ? ($aprovados / $totalExigido) * 100 : 100;
                    @endphp

                    <div class="flex flex-col items-center justify-center w-full max-w-[150px] mx-auto">
                        <div class="flex justify-between w-full text-[10px] font-bold text-gray-500 mb-1">
                            <span>{{ $aprovados }} de {{ $totalExigido }} docs</span>
                            <span>{{ number_format($porcentagem, 0) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                            <div class="h-2.5 rounded-full transition-all duration-500 {{ $porcentagem == 100 ? 'bg-green-500' : 'bg-purpura-500' }}" style="width: {{ $porcentagem }}%"></div>
                        </div>
                    </div>
                </td>
                
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-1 text-[11px] font-bold rounded border {{ $inscricao->etapa_atual === 'Matriculado' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-yellow-50 text-yellow-700 border-yellow-200' }}">
                        {{ $inscricao->etapa_atual ?? 'Coletando Documentos' }}
                    </span>
                </td>
                
                <td class="px-4 py-3 text-right">
                    <button wire:click="abrirDossie({{ $inscricao->id }})" class="px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 hover:text-purpura-600 text-gray-700 font-bold text-xs rounded-lg shadow-sm transition flex items-center gap-2 ml-auto">
                        <i class="ph-bold ph-folder-open text-base"></i> Abrir Dossiê
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-12 text-center text-gray-500">
                    <i class="ph-fill ph-folder-dashed text-4xl mb-2 text-gray-300 block"></i>
                    Nenhum candidato em processo de matrícula no momento.
                </td>
            </tr>
        @endforelse
    </x-table>

    <!-- MODAL DO DOSSIÊ DO CANDIDATO -->
    @if($modalDossieAberto && $inscricaoSelecionada)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/80 backdrop-blur-sm" wire:click="$set('modalDossieAberto', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block w-full max-w-4xl px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-gray-50 rounded-xl shadow-2xl sm:my-8 sm:align-middle sm:p-6">
                    
                    <div class="flex justify-between items-center mb-6 border-b border-gray-200 pb-4">
                        <div>
                            <h3 class="text-xl font-black text-gray-900 flex items-center gap-2">
                                <i class="ph-fill ph-folder-user text-purpura-500"></i> Dossiê de Matrícula
                            </h3>
                            <p class="text-sm text-gray-600 font-medium mt-1">{{ $inscricaoSelecionada->nome }} (CPF: {{ $inscricaoSelecionada->cpf }})</p>
                        </div>
                        <button wire:click="$set('modalDossieAberto', false)" class="text-gray-400 hover:text-red-500 transition"><i class="ph-bold ph-x text-2xl"></i></button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[60vh] overflow-y-auto custom-scrollbar pr-2">
                        @foreach($documentosExigidos as $docReq)
                            @php
                                $enviado = $documentosEnviados->get($docReq->id);
                                $status = $enviado ? $enviado->status_analise : 'pendente';
                            @endphp

                            <div class="bg-white border rounded-xl p-4 shadow-sm {{ $status === 'valido_ia' || $status === 'aprovado_manual' ? 'border-green-200' : 'border-gray-200' }}">
                                <div class="flex justify-between items-start mb-2">
                                    <h4 class="font-bold text-gray-800 text-sm flex items-center gap-1.5">
                                        {{ $docReq->nome }}
                                        @if($docReq->is_obrigatorio) <span class="text-[9px] bg-red-100 text-red-700 px-1.5 py-0.5 rounded uppercase">Obrigatório</span> @endif
                                    </h4>
                                    
                                    <!-- BADGES DE STATUS -->
                                    @if($status === 'valido_ia')
                                        <span class="text-[10px] bg-green-100 text-green-700 font-bold px-2 py-1 rounded-full"><i class="ph-bold ph-robot"></i> IA Aprovou</span>
                                    @elseif($status === 'aprovado_manual')
                                        <span class="text-[10px] bg-green-100 text-green-700 font-bold px-2 py-1 rounded-full"><i class="ph-bold ph-user"></i> Sec. Aprovou</span>
                                    @elseif($status === 'analise_manual' || $status === 'invalido_ia')
                                        <span class="text-[10px] bg-yellow-100 text-yellow-700 font-bold px-2 py-1 rounded-full"><i class="ph-bold ph-warning"></i> Validar Manualmente</span>
                                    @else
                                        <span class="text-[10px] bg-gray-100 text-gray-500 font-bold px-2 py-1 rounded-full">Pendente</span>
                                    @endif
                                </div>

                                <!-- AÇÕES DA SECRETARIA -->
                                @if($enviado)
                                    <div class="mt-3 flex gap-2">
                                        <!-- O botão de visualizar a imagem abre em nova aba convertendo via rota segura se necessário, ou link direto se storage publico. -->
                                        <button wire:click="aprovarDocumento({{ $enviado->id }})" class="flex-1 text-[11px] font-bold py-1.5 rounded bg-green-50 text-green-700 hover:bg-green-600 hover:text-white transition border border-green-200 flex items-center justify-center gap-1">
                                            <i class="ph-bold ph-check"></i> Aprovar
                                        </button>
                                        <button wire:click="reprovarDocumento({{ $enviado->id }})" class="flex-1 text-[11px] font-bold py-1.5 rounded bg-red-50 text-red-700 hover:bg-red-600 hover:text-white transition border border-red-200 flex items-center justify-center gap-1">
                                            <i class="ph-bold ph-x"></i> Recusar
                                        </button>
                                    </div>
                                    @if(in_array($status, ['analise_manual', 'invalido_ia']))
                                        <p class="text-[10px] text-red-600 mt-2 font-medium bg-red-50 p-1.5 rounded">Motivo IA: {{ $enviado->log_ia['motivo_rejeicao'] ?? 'Documento não reconhecido.' }}</p>
                                    @endif
                                @else
                                    <div class="mt-4 p-3 bg-gray-50 border border-gray-100 rounded text-center">
                                        <p class="text-xs text-gray-400">O candidato ainda não enviou este arquivo.</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>