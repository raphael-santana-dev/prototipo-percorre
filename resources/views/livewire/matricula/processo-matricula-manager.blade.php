<div class="p-6 mx-auto font-sans relative max-w-7xl">
    
    <x-page-header 
        title="Acompanhamento de Matrículas" 
        icon="ph ph-folder-user"
        badge="Portal de Envio">
        <x-slot name="filters" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-1">
                <label class="flex items-center gap-1 mb-1 text-[10px] font-bold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                    <i class="ph-bold ph-magnifying-glass text-purpura-500"></i> Buscar Candidato
                </label>
                <input type="text" wire:model.live.debounce.500ms="termoBusca" placeholder="Nome ou CPF..." class="w-full px-3 py-2 text-sm border-gray-300 rounded-md shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-purpura-500 focus:border-purpura-500">
            </div>
        </x-slot>
    </x-page-header>

    <x-table
        :headers="$this->headers"
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid"
        :modoExibicao="$modoExibicao">

        @forelse($registros as $inscricao)
            <tr wire:key="linha-acomp-{{ $inscricao->id }}" class="bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors">
                
                <td class="px-4 py-2.5 font-medium text-gray-500 dark:text-gray-400 text-xs whitespace-nowrap">
                    #{{ $inscricao->id }}
                </td>
                
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <div class="font-bold text-gray-900 text-sm dark:text-white">{{ $inscricao->nome }}</div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5"><i class="ph-fill ph-graduation-cap text-purpura-500"></i> {{ $inscricao->curso->nome ?? 'Sem Curso' }}</div>
                </td>
                
                <td class="px-4 py-2.5 text-center whitespace-nowrap">
                    @php
                        $totalExigido = \App\Modules\Matricula\Domain\Models\DocumentoExigido::where('ciclo_id', $inscricao->ciclo_id)->where('is_obrigatorio', true)->count();
                        $aprovados = \App\Modules\Matricula\Domain\Models\DocumentoMatricula::where('inscricao_id', $inscricao->id)
                            ->whereIn('status_analise', ['valido_ia', 'aprovado_manual'])
                            ->whereHas('documentoExigido', function($q) { $q->where('is_obrigatorio', true); })->count();
                        $porcentagem = $totalExigido > 0 ? ($aprovados / $totalExigido) * 100 : 100;
                    @endphp
                    <div class="flex flex-col items-center justify-center w-full max-w-[120px] mx-auto">
                        <div class="flex justify-between w-full text-[10px] font-bold text-gray-500 mb-1">
                            <span>{{ $aprovados }}/{{ $totalExigido }} docs</span>
                            <span>{{ number_format($porcentagem, 0) }}%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                            <div class="h-1.5 rounded-full {{ $porcentagem == 100 ? 'bg-green-500' : 'bg-purpura-500' }}" style="width: {{ $porcentagem }}%"></div>
                        </div>
                    </div>
                </td>
                
                <td class="px-4 py-2.5 text-center whitespace-nowrap">
                    <span class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider rounded-full border {{ $inscricao->etapa_atual >= 3 ? 'bg-green-50 text-green-700 border-green-200' : 'bg-yellow-50 text-yellow-700 border-yellow-200' }}">
                        {{ $inscricao->etapa_atual >= 3 ? 'Matriculado' : ($inscricao->etapa_atual == 2 ? 'Em Análise Manual' : 'Coletando Documentos') }}
                    </span>
                </td>
                
                <td class="px-4 py-2.5 text-right whitespace-nowrap">
                    <button wire:click="abrirDossie({{ $inscricao->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Abrir Dossiê">
                        <i class="text-xl ph-bold ph-folder-open"></i>
                    </button>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">Nenhum candidato localizado.</td></tr>   
        @endforelse

        <x-slot name="gridSlot">
            @foreach($registros as $inscricao)
                <div class="flex flex-col p-4 bg-white border border-gray-100 shadow-sm rounded-xl hover:shadow-md transition-shadow">
                    <div class="flex justify-between mb-2">
                        <span class="text-xs font-medium text-gray-500">#{{ $inscricao->id }}</span>
                        <button wire:click="abrirDossie({{ $inscricao->id }})" class="text-purpura-600 hover:text-purpura-800"><i class="text-xl ph-bold ph-folder-open"></i></button>
                    </div>
                    <h4 class="text-sm font-bold text-gray-900 truncate">{{ $inscricao->nome }}</h4>
                    <p class="text-xs text-gray-500 truncate mb-3"><i class="ph-fill ph-graduation-cap"></i> {{ $inscricao->curso->nome ?? 'N/A' }}</p>
                </div>
            @endforeach
        </x-slot>
    </x-table>

    <!-- MODAL DO DOSSIÊ (Padrão RegistrationDetails) -->
    @if($modalDossieAberto && $inscricaoSelecionada)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-gray-50 rounded-xl shadow-2xl w-full max-w-5xl overflow-hidden flex flex-col max-h-[90vh]">
                
                <div class="bg-white p-5 border-b border-gray-200 flex justify-between items-center shrink-0">
                    <div>
                        <h3 class="text-lg font-black text-gray-900 flex items-center gap-2">
                            <i class="ph-fill ph-folder-user text-purpura-500"></i> Dossiê de Matrícula
                        </h3>
                        <p class="text-xs text-gray-500 font-medium mt-0.5">{{ $inscricaoSelecionada->nome }} • CPF: {{ $inscricaoSelecionada->cpf }}</p>
                    </div>
                    <button wire:click="$set('modalDossieAberto', false)" class="text-gray-400 hover:text-red-500 transition"><i class="ph-bold ph-x text-2xl"></i></button>
                </div>

                <div class="p-6 overflow-y-auto custom-scrollbar flex-1">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($documentosExigidos as $docReq)
                            @php
                                $enviado = $documentosEnviados->get($docReq->id);
                                $status = $enviado ? $enviado->status_analise : 'pendente';
                                $imgBase64 = null;
                                if ($enviado && \Illuminate\Support\Facades\Storage::disk('local')->exists($enviado->arquivo_caminho)) {
                                    $path = \Illuminate\Support\Facades\Storage::disk('local')->path($enviado->arquivo_caminho);
                                    $imgBase64 = 'data:' . mime_content_type($path) . ';base64,' . base64_encode(file_get_contents($path));
                                }
                            @endphp

                            <div class="bg-white p-5 rounded-xl shadow-sm border {{ $status === 'valido_ia' || $status === 'aprovado_manual' ? 'border-green-200' : 'border-gray-200' }}">
                                <div class="flex justify-between items-start border-b border-gray-100 pb-3 mb-3">
                                    <h4 class="font-bold text-gray-900 text-sm">{{ $docReq->nome }}</h4>
                                    
                                    @if($status === 'valido_ia') <span class="px-2 py-0.5 text-[9px] font-bold rounded-full bg-green-50 text-green-700 border border-green-200 uppercase tracking-wider">IA Aprovou</span>
                                    @elseif($status === 'aprovado_manual') <span class="px-2 py-0.5 text-[9px] font-bold rounded-full bg-green-50 text-green-700 border border-green-200 uppercase tracking-wider">Sec. Aprovou</span>
                                    @elseif($status === 'analise_manual' || $status === 'invalido_ia') <span class="px-2 py-0.5 text-[9px] font-bold rounded-full bg-yellow-50 text-yellow-700 border border-yellow-200 uppercase tracking-wider">Validar Manual</span>
                                    @else <span class="px-2 py-0.5 text-[9px] font-bold rounded-full bg-gray-50 text-gray-500 border border-gray-200 uppercase tracking-wider">Pendente</span>
                                    @endif
                                </div>

                                @if($enviado)
                                    <div class="bg-gray-50 rounded-lg border border-gray-200 flex items-center justify-center h-40 relative overflow-hidden mb-3">
                                        @if($imgBase64)
                                            <a href="{{ $imgBase64 }}" target="_blank" title="Clique para ampliar" class="w-full h-full flex items-center justify-center hover:opacity-90 transition cursor-zoom-in">
                                                <img src="{{ $imgBase64 }}" class="max-h-full object-contain">
                                            </a>
                                        @else
                                            <span class="text-xs text-gray-400"><i class="ph-fill ph-image-broken text-2xl mb-1 block"></i> Indisponível</span>
                                        @endif
                                    </div>
                                    <div class="flex gap-2">
                                        <button wire:click="aprovarDocumento({{ $enviado->id }})" class="flex-1 text-[10px] uppercase tracking-wider font-bold py-2 rounded-md bg-green-50 text-green-700 hover:bg-green-600 hover:text-white transition border border-green-200 flex items-center justify-center gap-1"><i class="ph-bold ph-check text-sm"></i> Aprovar</button>
                                        <button wire:click="reprovarDocumento({{ $enviado->id }})" class="flex-1 text-[10px] uppercase tracking-wider font-bold py-2 rounded-md bg-red-50 text-red-700 hover:bg-red-600 hover:text-white transition border border-red-200 flex items-center justify-center gap-1"><i class="ph-bold ph-x text-sm"></i> Recusar</button>
                                    </div>
                                @else
                                    <div class="py-8 bg-gray-50 border border-dashed border-gray-300 rounded-lg text-center">
                                        <p class="text-xs font-medium text-gray-500">Candidato ainda não enviou.</p>
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