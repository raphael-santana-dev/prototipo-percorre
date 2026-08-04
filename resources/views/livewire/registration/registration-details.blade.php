<div class="p-6 mx-auto font-sans max-w-7xl space-y-6">
    <div class="flex items-center gap-4 mb-4">
        <a href="{{ route('inscricoes.index') }}" class="p-2 text-gray-500 transition-colors bg-white border border-gray-200 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">
            <i class="text-xl ph ph-arrow-left"></i>
        </a>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
            Detalhes da Inscrição #{{ $inscricao->id }}
        </h2>
    </div>

    <!-- 1. CABEÇALHO (Master Card) -->
    <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700 relative">
        <div class="absolute top-0 w-full h-32 bg-gradient-to-r from-purpura-600 to-indigo-600"></div>
        
        <div class="relative px-6 pt-24 pb-6 sm:px-8">
            <div class="flex flex-col md:flex-row items-end md:items-center gap-6">
                
                <div class="flex items-center justify-center w-24 h-24 bg-white border-4 border-white rounded-2xl shadow-lg dark:bg-gray-900 dark:border-gray-800 shrink-0">
                    <i class="text-4xl ph ph-user-focus text-purpura-500"></i>
                </div>
                
                <div class="flex-1 w-full flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ $inscricao->nome }}</h1>
                        <p class="text-gray-500 dark:text-gray-400 mt-1 font-medium">
                            <i class="ph ph-identification-card"></i> {{ $inscricao->cpf }} &nbsp;•&nbsp; 
                            <i class="ph ph-envelope-simple"></i> {{ $inscricao->email }}
                        </p>
                    </div>
                    
                    <!-- Box de Alteração de Status -->
                    <div class="bg-gray-50 dark:bg-gray-900 p-3 rounded-lg border border-gray-100 dark:border-gray-700 flex flex-col gap-2 min-w-[250px]">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Status Atual</span>
                            <span class="px-2.5 py-1 text-xs font-bold rounded-md uppercase 
                                @if($inscricao->statusInscricao && $inscricao->statusInscricao->nome === 'Aprovado') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                                @elseif($inscricao->statusInscricao && $inscricao->statusInscricao->nome === 'Reprovado') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                @else bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200 @endif">
                                {{ $inscricao->statusInscricao->nome ?? 'Pendente' }}
                            </span>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-600 my-1"></div>
                        <div class="flex flex-wrap gap-1 mt-1 justify-end">
                            @foreach($statusInscricoesDb as $status)
                                @if($status->id !== $inscricao->status_inscricao_id)
                                    <button wire:click="alterarStatus({{ $status->id }})" class="px-2 py-1 text-[10px] font-bold text-gray-600 bg-white border border-gray-300 rounded hover:bg-purpura-50 hover:text-purpura-600 hover:border-purpura-300 transition-colors dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:text-purpura-400">
                                        {{ $status->nome }}
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- 2. GRID DE INFORMAÇÕES -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Coluna Esquerda (Dados Pessoais e Endereço) -->
        <div class="lg:col-span-1 space-y-6">
            
            <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-6 dark:bg-gray-800 dark:border-gray-700">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4 border-b border-gray-100 pb-2 dark:border-gray-700 flex items-center gap-2">
                    <i class="ph ph-identification-badge text-indigo-500"></i> Dados Pessoais
                </h3>
                <ul class="space-y-3">
                    <li class="flex flex-col">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Idade / Nascimento</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-200">{{ \Carbon\Carbon::parse($inscricao->data_nascimento)->age }} anos ({{ \Carbon\Carbon::parse($inscricao->data_nascimento)->format('d/m/Y') }})</span>
                    </li>
                    <li class="flex flex-col">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Celular</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-200">{{ $inscricao->celular ?: 'Não informado' }}</span>
                    </li>
                    <li class="flex flex-col">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Nome Social</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-200">{{ $inscricao->possui_nome_social === 'sim' ? $inscricao->nome_social : 'Não possui' }}</span>
                    </li>
                    <li class="flex flex-col">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Pessoa com Deficiência (PcD)</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-200">{{ $inscricao->possui_deficiencia === 'sim' ? $inscricao->natureza_deficiencia : 'Não' }}</span>
                    </li>
                </ul>
            </div>

            <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-6 dark:bg-gray-800 dark:border-gray-700">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4 border-b border-gray-100 pb-2 dark:border-gray-700 flex items-center gap-2">
                    <i class="ph ph-map-pin text-ponkan-500"></i> Endereço
                </h3>
                <div class="text-sm font-medium text-gray-900 dark:text-gray-200 leading-relaxed">
                    {{ $inscricao->logradouro ?: 'Rua não informada' }}, {{ $inscricao->numero ?: 'S/N' }}<br>
                    @if($inscricao->complemento) {{ $inscricao->complemento }}<br> @endif
                    {{ $inscricao->bairro ?: 'Bairro não informado' }}<br>
                    {{ $inscricao->cidade ?: 'Cidade' }} - {{ $inscricao->estado ?: 'UF' }}<br>
                    CEP: {{ $inscricao->cep ?: '00000-000' }}
                </div>
            </div>

        </div>

        <!-- Coluna Direita (Interesse, Campos Dinâmicos e Auditoria) -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Interesse Acadêmico -->
            <div class="bg-blue-50 border border-blue-100 shadow-sm rounded-xl p-6 dark:bg-blue-900/20 dark:border-blue-800">
                <h3 class="font-bold text-blue-900 dark:text-blue-300 mb-4 border-b border-blue-200 pb-2 dark:border-blue-800 flex items-center gap-2">
                    <i class="ph ph-student text-blue-500"></i> Interesse Acadêmico
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <span class="text-xs font-bold text-blue-500 dark:text-blue-400 uppercase">Processo / Ciclo</span>
                        <p class="text-sm font-bold text-blue-900 dark:text-blue-200">{{ $inscricao->ciclo->nome ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-blue-500 dark:text-blue-400 uppercase">Curso e Turno</span>
                        <p class="text-sm font-bold text-blue-900 dark:text-blue-200">{{ $inscricao->curso->nome ?? '-' }} ({{ $inscricao->turno->nome ?? '-' }})</p>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-blue-500 dark:text-blue-400 uppercase">Unidade Base</span>
                        <p class="text-sm font-bold text-blue-900 dark:text-blue-200">{{ $inscricao->unidade->nome ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <!-- Dados Dinâmicos -->
            @if($inscricao->dados_dinamicos && count($inscricao->dados_dinamicos) > 0)
                <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-6 dark:bg-gray-800 dark:border-gray-700">
                    <h3 class="font-bold text-gray-900 dark:text-white mb-4 border-b border-gray-100 pb-2 dark:border-gray-700 flex items-center gap-2">
                        <i class="ph ph-list-dashes text-emerald-500"></i> Respostas do Formulário
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($inscricao->dados_dinamicos as $chave => $valor)
                            <div class="bg-gray-50 dark:bg-gray-900/50 p-3 rounded-lg border border-gray-100 dark:border-gray-700">
                                <span class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">{{ str_replace('_', ' ', $chave) }}</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-200">{{ empty($valor) ? 'Não respondido' : (is_array($valor) ? implode(', ', $valor) : $valor) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Auditoria de Pontuação -->
            @if($inscricao->pontuacao_detalhes)
                @php $detalhes = json_decode($inscricao->pontuacao_detalhes, true); @endphp
                
                <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-6 dark:bg-gray-800 dark:border-gray-700">
                    <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-2 dark:border-gray-700">
                        <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="ph ph-chart-bar text-amber-500"></i> Auditoria de Pontuação
                        </h3>
                        <span class="bg-amber-100 text-amber-700 font-bold px-3 py-1 rounded-full text-sm dark:bg-amber-900/30 dark:text-amber-400">
                            Total: {{ $inscricao->pontuacao_total }} pts
                        </span>
                    </div>

                    @if(isset($detalhes['auditoria_detalhada']) && count($detalhes['auditoria_detalhada']) > 0)
                        <div class="space-y-3">
                            @foreach($detalhes['auditoria_detalhada'] as $campo => $info)
                                <div class="bg-gray-50 dark:bg-gray-900/50 px-4 py-3 rounded-lg border border-gray-100 dark:border-gray-700 flex justify-between items-center">
                                    <div>
                                        <span class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase">{{ str_replace('_', ' ', $campo) }}</span>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            Resposta: <b class="dark:text-gray-300">{{ $info['resposta_dada'] }}</b> &nbsp;|&nbsp; Regra atingida: {{ $info['condicao'] }}
                                        </p>
                                    </div>
                                    <b class="text-emerald-600 dark:text-emerald-400 text-lg">+{{ $info['pontos_ganhos'] }}</b>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 text-center py-4">O candidato não pontuou em nenhuma regra configurada neste ciclo.</p>
                    @endif
                </div>
            @endif

        </div>
    </div>
    
    {{-- TOAST PARA A TELA DE DETALHES --}}
    <div x-data="{ show: false, msg: '' }" @sucesso.window="show = true; msg = $event.detail.msg; setTimeout(() => show = false, 3500)" x-show="show" x-cloak class="fixed bottom-8 right-8 bg-green-600 text-white px-6 py-4 rounded-xl shadow-2xl z-[200] flex items-center gap-3 font-bold">
        <i class="text-2xl ph ph-check-circle text-white"></i>
        <span x-text="msg"></span>
    </div>
</div>