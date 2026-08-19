<div class="p-6 max-w-[1400px] mx-auto font-sans">

    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => '#'], 
        ['label' => 'Inscrições', 'url' => route('inscricoes.index') ?? '#'], 
        ['label' => 'Ficha do Candidato', 'url' => '#']
    ]" />

    <x-details-card 
        title="Ficha do Candidato" 
        subtitle="Inscrição iniciada em {{ $inscricao->created_at->format('d/m/Y \à\s H:i') }}"
        backUrl="{{ route('inscricoes.index') ?? '#' }}"
        backLabel="Voltar à Lista"
        avatarInitials="{{ strtoupper(substr($inscricao->nome, 0, 2)) }}"
        itemName="{{ $inscricao->nome }}"
        itemDescription="CPF: {{ $inscricao->cpf }} • {{ $inscricao->email }}">
        
        <x-slot name="badge">
            <div class="flex flex-col sm:flex-row items-end sm:items-center gap-3">
                @php
                    $nomeStatusAtual = strtolower(\App\Models\StatusInscricao::find($inscricao->status_inscricao_id)->nome ?? 'Pendente');
                    $corBg = match($nomeStatusAtual) {
                        'aprovado' => 'bg-green-100 text-green-800 border-green-200',
                        'reprovado' => 'bg-red-100 text-red-800 border-red-200',
                        'lead' => 'bg-blue-100 text-blue-800 border-blue-200',
                        'incompleto' => 'bg-gray-100 text-gray-800 border-gray-200',
                        default => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                    };
                @endphp
                <span class="{{ $corBg }} border text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">
                    {{ \App\Models\StatusInscricao::find($inscricao->status_inscricao_id)->nome ?? 'Sem Status' }}
                </span>

                {{-- CONTROLE DE STATUS RÁPIDO --}}
                <div class="flex items-center gap-1.5 bg-gray-50 p-1 rounded-lg border border-gray-200 shadow-sm">
                    <select wire:model="status_selecionado" class="border-none bg-transparent rounded-md text-[11px] font-bold text-gray-700 focus:ring-0 py-1 pl-2 pr-6 cursor-pointer hover:bg-gray-100 transition-colors">
                        @foreach($todosStatus as $status)
                            <option value="{{ $status->id }}">{{ $status->nome }}</option>
                        @endforeach
                    </select>
                    <button wire:click="atualizarStatus" class="px-2.5 py-1 bg-purpura-600 hover:bg-purpura-700 text-white font-bold text-[10px] uppercase tracking-wider rounded transition-colors shadow-sm">
                        Mover
                    </button>
                </div>
            </div>
        </x-slot>

        <!-- Slot Inferior (Grid de Metadados Principais) -->
        <div>
            <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Nascimento (Idade)</span>
            <span class="block text-sm font-bold text-gray-900 mt-1">
                {{ $inscricao->data_nascimento ? \Carbon\Carbon::parse($inscricao->data_nascimento)->format('d/m/Y') : 'Não informada' }} 
                @if($inscricao->data_nascimento)
                    <span class="text-purpura-600 ml-1">({{ \Carbon\Carbon::parse($inscricao->data_nascimento)->age }} anos)</span>
                @endif
            </span>
        </div>
        <div>
            <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Celular / Telefone</span>
            <span class="block text-sm font-bold text-gray-900 mt-1">{{ $inscricao->celular ?? 'Não informado' }}</span>
        </div>
        <div>
            <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Unidade Escolhida</span>
            <span class="block text-sm font-bold text-gray-900 mt-1">{{ $inscricao->unidade->nome ?? 'Não informada' }}</span>
        </div>
        <div>
            <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Curso & Turno</span>
            <span class="block text-sm font-bold text-gray-900 mt-1">
                {{ $inscricao->curso->nome ?? 'Não informado' }}
                <span class="text-[10px] font-bold text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded border border-gray-200 ml-1">{{ $inscricao->turno->nome ?? '-' }}</span>
            </span>
        </div>
    </x-details-card>

    <!-- Grelha Inferior (Colunas) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        
        <!-- ========================================== -->
        <!-- COLUNA ESQUERDA (Tamanho Duplo): Respostas -->
        <!-- ========================================== -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Painel Extra: Nome Social e PcD -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h3 class="text-xs font-bold tracking-wider text-gray-500 uppercase flex items-center gap-2 mb-4 border-b border-gray-100 pb-2">
                    <i class="ph-fill ph-identification-card text-lg text-purpura-500"></i> Informações Adicionais
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                        <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Nome Social</span>
                        <span class="block text-sm font-bold text-gray-900">{{ $inscricao->nome_social ?: 'Não possui' }}</span>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                        <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">PcD (Deficiência)</span>
                        @if($inscricao->possui_deficiencia === 'sim')
                            <span class="inline-block mt-0.5 px-2 py-0.5 bg-red-50 text-red-700 font-bold text-xs rounded border border-red-200">Sim - {{ $inscricao->natureza_deficiencia }}</span>
                        @else
                            <span class="block text-sm font-bold text-gray-900">Não declarada</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Dados Dinâmicos do Formulário -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h3 class="text-xs font-bold tracking-wider text-gray-500 uppercase flex items-center gap-2 mb-4 border-b border-gray-100 pb-2">
                    <i class="ph-fill ph-list-dashes text-lg text-purpura-500"></i> Questionário Complementar
                </h3>
                
                @php
                    $dinamicos = is_string($inscricao->dados_dinamicos) ? json_decode($inscricao->dados_dinamicos, true) : ($inscricao->dados_dinamicos ?? []);
                @endphp

                @if(is_array($dinamicos) && count($dinamicos) > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($dinamicos as $chave => $valor)
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 hover:border-purpura-200 transition-colors">
                                <span class="block text-[10px] text-purpura-600 uppercase font-bold tracking-wider mb-1">{{ str_replace('_', ' ', $chave) }}</span>
                                <span class="block text-sm font-bold text-gray-900 break-words">
                                    {{ !empty($valor) ? (is_array($valor) ? implode(', ', $valor) : $valor) : '-' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 bg-gray-50 rounded-lg text-center border border-dashed border-gray-300">
                        <i class="ph-fill ph-text-align-center text-3xl text-gray-300 mb-2"></i>
                        <p class="text-gray-500 text-sm font-medium">Nenhum dado de formulário extra registrado para este aluno.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- ========================================== -->
        <!-- COLUNA DIREITA: Auditoria de Pontuação     -->
        <!-- ========================================== -->
        <div class="lg:col-span-1 space-y-6">
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-yellow-400 rounded-bl-full -z-0 opacity-10"></div>
                
                <div class="p-6 relative z-10">
                    <div class="flex justify-between items-start border-b border-gray-100 pb-4 mb-4">
                        <h3 class="text-xs font-bold tracking-wider text-gray-500 uppercase flex items-center gap-2">
                            <i class="ph-fill ph-calculator text-lg text-yellow-500"></i> Score Rating
                        </h3>
                        <div class="text-center">
                            <span class="bg-gray-900 text-yellow-400 font-extrabold text-xl px-3 py-1 rounded-lg shadow-sm border border-gray-800">
                                {{ $inscricao->pontuacao_total ?? 0 }} <span class="text-[10px] text-gray-400">pts</span>
                            </span>
                        </div>
                    </div>
                    
                    @if($inscricao->pontuacao_detalhes)
                        @php
                            $detalhes = is_string($inscricao->pontuacao_detalhes) ? json_decode($inscricao->pontuacao_detalhes, true) : $inscricao->pontuacao_detalhes;
                        @endphp
                        
                        @if(isset($detalhes['auditoria_detalhada']) && count($detalhes['auditoria_detalhada']) > 0)
                            <div class="space-y-3 mb-4">
                                <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider mb-2">Regras Atendidas / Cálculos:</p>
                                @foreach($detalhes['auditoria_detalhada'] as $info)
                                    <div class="bg-white border border-gray-200 shadow-sm p-3 rounded-lg flex justify-between items-center group hover:border-yellow-400 transition-colors">
                                        <div class="flex-1 pr-3">
                                            <span class="text-[10px] font-bold text-gray-900 uppercase block">{{ str_replace('_', ' ', $info['campo_avaliado'] ?? 'Regra Padrão') }}</span>
                                            
                                            <!-- Se for regra especial, destaca o texto -->
                                            @if(isset($info['tipo_regra']) && $info['tipo_regra'] === 'especial')
                                                <p class="text-[10px] text-indigo-600 font-bold mt-1 leading-tight">{{ $info['condicao'] ?? 'Bônus/Multiplicador aplicado' }}</p>
                                            @else
                                                <p class="text-xs text-gray-500 mt-0.5 truncate" title="{{ $info['resposta_dada'] ?? '-' }}">Resp: <b>{{ $info['resposta_dada'] ?? '-' }}</b></p>
                                            @endif
                                        </div>
                                        <span class="text-green-700 font-extrabold bg-green-50 px-2 py-1 rounded-md text-[11px] border border-green-200 shrink-0">
                                            +{{ $info['pontos_ganhos'] ?? 0 }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        
                        @if(isset($detalhes['motivo_auditoria']))
                            <div class="bg-gray-900 rounded-lg p-3 text-[10px] font-mono text-green-400 leading-relaxed border border-gray-800 mt-4 break-words">
                                <span class="text-gray-500">&gt;_ sys.log:</span><br>
                                {{ $detalhes['motivo_auditoria'] }}
                            </div>
                        @endif
                    @else
                        <div class="py-10 text-center">
                            <i class="ph-fill ph-calculator text-3xl text-gray-200 mb-2"></i>
                            <p class="text-gray-400 text-xs font-bold leading-snug">Nenhuma pontuação vinculada.<br>Cálculos não foram realizados.</p>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Metadata do Ciclo (Info rápida) -->
            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-5 shadow-sm text-center">
                <span class="block text-[10px] font-bold text-indigo-400 uppercase tracking-wider mb-1">Ciclo Operacional</span>
                <span class="block text-sm font-bold text-indigo-900">{{ $inscricao->ciclo->nome ?? 'Formulário Legado' }}</span>
            </div>

        </div>
    </div>
</div>