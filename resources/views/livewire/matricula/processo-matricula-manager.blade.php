<div class="p-6 mx-auto font-sans relative max-w-7xl" x-data="{ abaAtiva: $wire.entangle('abaAtiva') }">
    
    <x-page-header 
        title="Acompanhamento de Matrículas" 
        icon="ph ph-folder-user"
        badge="Portal de Validação">
    </x-page-header>

    {{-- NAVEGAÇÃO ENTRE ABAS --}}
    <div class="mb-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-t-xl px-4 pt-2 shadow-sm">
        <nav class="flex gap-4 -mb-px">
            <button type="button" 
                    @click="abaAtiva = 'dossies'" 
                    :class="abaAtiva === 'dossies' ? 'border-purpura-600 text-purpura-600 dark:text-purpura-400 dark:border-purpura-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400'"
                    class="py-3 px-3 border-b-2 font-bold text-xs flex items-center gap-2 transition-all">
                <i class="ph-bold ph-folder text-base"></i>
                <span>Dossiês de Alunos</span>
            </button>

            <button type="button" 
                    @click="abaAtiva = 'revisao'" 
                    :class="abaAtiva === 'revisao' ? 'border-red-600 text-red-600 dark:text-red-400 dark:border-red-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400'"
                    class="py-3 px-3 border-b-2 font-bold text-xs flex items-center gap-2 transition-all">
                <i class="ph-bold ph-warning-circle text-base"></i>
                <span>Revisão de IA Pendente</span>
                @if($totalRevisoes > 0)
                    <span class="px-2 py-0.5 text-[10px] rounded-full font-bold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                        {{ $totalRevisoes }}
                    </span>
                @endif
            </button>
        </nav>
    </div>

    {{-- BARRA DE FILTROS GLOBAL (APLICA NAS DUAS ABAS) --}}
    <div class="mb-4 bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
            <div class="md:col-span-2">
                <input type="text" wire:model.live.debounce.500ms="filtroBusca" placeholder="Buscar por ID, Nome ou CPF..." class="w-full rounded-lg border-gray-300 shadow-sm text-xs focus:ring-purpura-500 focus:border-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div>
                <select wire:model.live="filtroUnidade" class="w-full rounded-lg border-gray-300 shadow-sm text-xs focus:ring-purpura-500 focus:border-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Todas as Unidades</option>
                    @foreach($unidadesDb as $u) <option value="{{ $u->id }}">{{ $u->nome }}</option> @endforeach
                </select>
            </div>
            <div>
                <select wire:model.live="filtroCurso" class="w-full rounded-lg border-gray-300 shadow-sm text-xs focus:ring-purpura-500 focus:border-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Todos os Cursos</option>
                    @foreach($cursosDb as $c) <option value="{{ $c->id }}">{{ $c->nome }}</option> @endforeach
                </select>
            </div>
            <div>
                <select wire:model.live="filtroTurno" class="w-full rounded-lg border-gray-300 shadow-sm text-xs focus:ring-purpura-500 focus:border-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Todos os Turnos</option>
                    @foreach($turnosDb as $t) <option value="{{ $t->id }}">{{ $t->nome }}</option> @endforeach
                </select>
            </div>
            <div>
                <select wire:model.live="filtroEtapa" class="w-full rounded-lg border-gray-300 shadow-sm text-xs focus:ring-purpura-500 focus:border-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Todas as Etapas</option>
                    <option value="1">Passo 1 (Coleta)</option>
                    <option value="2">Passo 2 (Em Análise)</option>
                    <option value="3">Matriculado</option>
                </select>
            </div>
        </div>
        
        @if($filtroBusca !== '' || $filtroCurso !== '' || $filtroUnidade !== '' || $filtroTurno !== '' || $filtroEtapa !== '')
            <div class="flex justify-end mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <button wire:click="limparFiltros" class="px-4 py-1.5 text-xs font-bold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-1.5 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                    <i class="ph-bold ph-funnel-x"></i> Limpar Filtros
                </button>
            </div>
        @endif
    </div>

    {{-- ABA 1: DOSSIÊS NORMAIS --}}
    <div x-show="abaAtiva === 'dossies'" x-cloak class="space-y-4" wire:key="aba-dossies">
        <x-table
            wire:key="tabela-dossies"
            :headers="$this->headers"
            :registros="$registros"
            :ordenacaoCampo="$ordenacaoCampo"
            :ordenacaoDirecao="$ordenacaoDirecao"
            :permiteGrid="$permiteGrid"
            :modoExibicao="$modoExibicao">

            @forelse($registros as $inscricao)
                <tr class="bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors">
                    <td class="px-4 py-2.5 font-medium text-gray-500 dark:text-gray-400 text-xs whitespace-nowrap">
                        #{{ $inscricao->id }}
                    </td>
                    <td class="px-4 py-2.5 whitespace-nowrap">
                        <div class="font-bold text-gray-900 text-sm dark:text-white">{{ $inscricao->nome }}</div>
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                            <i class="ph-fill ph-graduation-cap text-purpura-500"></i> {{ $inscricao->curso->nome ?? 'Sem Curso' }}
                            <span class="ml-1"><i class="ph-fill ph-map-pin text-purpura-500"></i> {{ $inscricao->unidade->nome ?? 'N/A' }}</span>
                        </div>
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
    </div>

    {{-- ABA 2: REVISÃO DE INTELIGÊNCIA ARTIFICIAL --}}
    <div x-show="abaAtiva === 'revisao'" x-cloak class="space-y-4" wire:key="aba-revisao">
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-400 p-4 rounded-xl flex items-start gap-3 text-sm shadow-sm mb-4">
            <i class="ph-fill ph-warning-circle text-2xl mt-0.5"></i>
            <div>
                <p class="font-bold">Atenção da Secretaria</p>
                <p>Estes documentos falharam na validação automática da IA ou o robô ficou em dúvida. Clique na pasta para abrir o dossiê do aluno e julgar manualmente.</p>
            </div>
        </div>

        <x-table 
            wire:key="tabela-revisao"
            :headers="$this->headersRevisao" 
            :registros="$revisoes"
            :ordenacaoCampo="$ordenacaoCampoRevisao"
            :ordenacaoDirecao="$ordenacaoDirecaoRevisao"
            :permiteGrid="false"
            modoExibicao="lista">
            
            @forelse($revisoes as $doc)
                <tr class="bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors">
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
                        <button wire:click="abrirDossie({{ $doc->inscricao_id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-gray-600" title="Analisar Documento">
                            <i class="text-xl ph-bold ph-folder-open"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-12 text-center text-gray-500">Nenhum documento aguardando revisão de inteligência artificial.</td></tr>
            @endforelse

            <x-slot name="gridSlot"></x-slot>
        </x-table>
    </div>

    <!-- MODAL DO DOSSIÊ (COMPARTILHADO PELAS DUAS ABAS) -->
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
                                    @elseif($status === 'analise_manual' || $status === 'invalido_ia') <span class="px-2 py-0.5 text-[9px] font-bold rounded-full bg-yellow-50 text-yellow-700 border border-yellow-200 uppercase tracking-wider animate-pulse">Validar Manual</span>
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
                                    
                                    {{-- FEEDBACK DA IA (Aparece se tiver problema) --}}
                                    @if($status === 'analise_manual' || $status === 'invalido_ia')
                                        <div class="mt-2 mb-3 bg-red-50 p-3 rounded-lg border border-red-100 text-xs">
                                            <span class="font-bold text-red-700 block mb-1"><i class="ph-fill ph-robot"></i> Parecer da IA:</span>
                                            <span class="text-gray-700 font-mono">{{ $enviado->log_ia['motivo_rejeicao'] ?? 'Documento ilegível, descontextualizado ou divergente.' }}</span>
                                        </div>
                                    @endif

                                    {{-- BOTÕES DE AÇÃO COM INTELIGÊNCIA ALPINE --}}
                                    @if(!in_array($status, ['valido_ia', 'aprovado_manual']))
                                        <div x-data="{ subAcao: '' }" class="w-full mt-3 border-t border-gray-100 pt-3">
                                            
                                            {{-- BOTÕES INICIAIS --}}
                                            <div class="flex gap-2" x-show="subAcao === ''">
                                                <button @click="subAcao = 'aprovar'" class="flex-1 text-[10px] uppercase tracking-wider font-bold py-2 rounded-md bg-green-50 text-green-700 hover:bg-green-600 hover:text-white transition border border-green-200 flex items-center justify-center gap-1"><i class="ph-bold ph-check text-sm"></i> Aprovar</button>
                                                <button @click="subAcao = 'reprovar'" class="flex-1 text-[10px] uppercase tracking-wider font-bold py-2 rounded-md bg-red-50 text-red-700 hover:bg-red-600 hover:text-white transition border border-red-200 flex items-center justify-center gap-1"><i class="ph-bold ph-x text-sm"></i> Recusar</button>
                                            </div>

                                            {{-- SE CLICOU EM APROVAR --}}
                                            <div x-show="subAcao === 'aprovar'" x-cloak class="flex flex-col gap-2">
                                                <button wire:click="aprovarDocumento({{ $enviado->id }})" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 rounded-md shadow-sm text-xs uppercase tracking-wider transition">Confirmar Aprovação Segura</button>
                                                <button @click="subAcao = ''" class="text-xs text-gray-500 font-bold hover:underline text-center">Cancelar ação</button>
                                            </div>

                                            {{-- SE CLICOU EM REPROVAR (MOSTRA CAIXA DE TEXTO) --}}
                                            <div x-show="subAcao === 'reprovar'" x-cloak class="flex flex-col gap-2">
                                                <textarea wire:model="motivosReprovacao.{{ $enviado->id }}" rows="2" class="w-full text-xs rounded-md border-gray-300 focus:border-red-500 focus:ring-red-500 shadow-sm" placeholder="Escreva o motivo para orientar o candidato..."></textarea>
                                                @error('motivosReprovacao.'.$enviado->id) <span class="text-[10px] text-red-500 font-bold block leading-tight">{{ $message }}</span> @enderror
                                                <button wire:click="reprovarDocumento({{ $enviado->id }})" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 rounded-md shadow-sm text-xs uppercase tracking-wider transition">Confirmar Recusa e Excluir Arquivo</button>
                                                <button @click="subAcao = ''" class="text-xs text-gray-500 font-bold hover:underline text-center">Cancelar ação</button>
                                            </div>
                                        </div>
                                    @endif
                                @else
                                    <div class="py-8 bg-gray-50 border border-dashed border-gray-300 rounded-lg text-center mt-2">
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