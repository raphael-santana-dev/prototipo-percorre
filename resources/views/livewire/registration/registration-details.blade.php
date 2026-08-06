<div class="p-6 max-w-7xl mx-auto font-sans">
    
    @if (session()->has('sucesso'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 font-bold rounded-lg shadow-sm flex items-center gap-2">
            <i class="ph-fill ph-check-circle text-xl"></i> {{ session('sucesso') }}
        </div>
    @endif

    {{-- CABEÇALHO E MUDANÇA DE STATUS --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <a href="{{ route('inscricoes.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 transition font-bold mb-1 flex items-center gap-1">
                <i class="ph ph-arrow-left"></i> Voltar para a Listagem
            </a>
            <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-3 mt-1">
                Ficha do Candidato
                
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
                <span class="{{ $corBg }} border text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">
                    {{ \App\Models\StatusInscricao::find($inscricao->status_inscricao_id)->nome ?? 'Sem Status' }}
                </span>
            </h2>
            <p class="text-sm text-gray-500 mt-1 flex items-center gap-1">
                <i class="ph ph-clock"></i> Inscrição iniciada em {{ $inscricao->created_at->format('d/m/Y \à\s H:i') }}
            </p>
        </div>
        
        {{-- CONTROLE DE STATUS COM APROVAÇÃO --}}
        <div class="flex items-center gap-3 bg-white p-3 rounded-xl border border-gray-200 shadow-sm">
            <label class="text-sm font-bold text-gray-700">Mover para:</label>
            <select wire:model="status_selecionado" class="border-gray-300 rounded-md text-sm font-medium focus:ring-brand-purple focus:border-brand-purple py-2 bg-gray-50">
                @foreach($todosStatus as $status)
                    <option value="{{ $status->id }}">{{ $status->nome }}</option>
                @endforeach
            </select>
            <button wire:click="atualizarStatus" class="px-5 py-2 bg-purpura-600 hover:bg-purpura-700 text-white font-bold text-sm rounded-lg shadow-sm transition flex items-center gap-2">
                <i class="ph-bold ph-arrows-clockwise"></i> Atualizar
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- COLUNA PRINCIPAL --}}
        <div class="lg:col-span-2 space-y-6">
            
            {{-- DADOS PESSOAIS --}}
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h3 class="text-lg font-extrabold text-gray-900 border-b border-gray-100 pb-3 mb-5 flex items-center gap-2">
                    <i class="ph-fill ph-user-circle text-purpura-500 text-xl"></i> Dados Pessoais
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-[11px] text-gray-400 uppercase font-bold tracking-wider">Nome Completo</p>
                        <p class="text-gray-900 font-bold text-base mt-1">{{ $inscricao->nome }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] text-gray-400 uppercase font-bold tracking-wider">Nome Social</p>
                        <p class="text-gray-900 font-medium text-base mt-1">{{ $inscricao->nome_social ?: 'Não possui' }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] text-gray-400 uppercase font-bold tracking-wider">CPF</p>
                        <p class="text-gray-900 font-medium text-base mt-1">{{ $inscricao->cpf }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] text-gray-400 uppercase font-bold tracking-wider">Data de Nascimento</p>
                        <p class="text-gray-900 font-medium text-base mt-1">
                            {{ $inscricao->data_nascimento ? \Carbon\Carbon::parse($inscricao->data_nascimento)->format('d/m/Y') : 'Não informada' }} 
                            @if($inscricao->data_nascimento)
                                <span class="text-purpura-600 font-bold text-sm ml-1">({{ \Carbon\Carbon::parse($inscricao->data_nascimento)->age }} anos)</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-[11px] text-gray-400 uppercase font-bold tracking-wider">E-mail de Contato</p>
                        <p class="text-gray-900 font-medium text-base mt-1">{{ $inscricao->email }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] text-gray-400 uppercase font-bold tracking-wider">Celular</p>
                        <p class="text-gray-900 font-medium text-base mt-1">{{ $inscricao->celular ?? 'Não informado' }}</p>
                    </div>
                    
                    <div class="col-span-1 md:col-span-2 pt-4 border-t border-gray-100 flex items-center gap-2">
                        <p class="text-[11px] text-gray-400 uppercase font-bold tracking-wider">PcD (Deficiência):</p>
                        @if($inscricao->possui_deficiencia === 'sim')
                            <p class="text-gray-900 font-bold text-sm bg-red-50 px-3 py-1 rounded text-red-700">Sim - {{ $inscricao->natureza_deficiencia }}</p>
                        @else
                            <p class="text-gray-900 font-medium text-sm bg-gray-100 px-3 py-1 rounded">Não</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- DADOS DINÂMICOS (RESPOSTAS DO FORMULÁRIO) --}}
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h3 class="text-lg font-extrabold text-gray-900 border-b border-gray-100 pb-3 mb-5 flex items-center gap-2">
                    <i class="ph-fill ph-list-dashes text-purpura-500 text-xl"></i> Informações Complementares
                </h3>
                
                @php
                    $dinamicos = is_string($inscricao->dados_dinamicos) ? json_decode($inscricao->dados_dinamicos, true) : ($inscricao->dados_dinamicos ?? []);
                @endphp

                @if(is_array($dinamicos) && count($dinamicos) > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($dinamicos as $chave => $valor)
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 hover:border-purpura-200 transition-colors">
                                <span class="text-[10px] text-purpura-600 uppercase font-bold tracking-wider">{{ str_replace('_', ' ', $chave) }}</span>
                                <p class="text-sm font-bold text-gray-900 mt-1 break-words">
                                    {{ !empty($valor) ? (is_array($valor) ? implode(', ', $valor) : $valor) : '-' }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-6 bg-gray-50 rounded-lg text-center border border-dashed border-gray-300">
                        <p class="text-gray-500 text-sm font-medium">Nenhum dado dinâmico complementar registrado.</p>
                    </div>
                @endif
            </div>

        </div>

        {{-- COLUNA LATERAL (Pontuação e Vínculos) --}}
        <div class="space-y-6">
            
            {{-- INTERESSE ACADÊMICO --}}
            <div class="bg-gradient-to-br from-indigo-50 to-purple-50 p-6 rounded-xl shadow-sm border border-indigo-100">
                <h3 class="text-lg font-extrabold text-indigo-900 border-b border-indigo-200/50 pb-3 mb-5">Interesse Acadêmico</h3>
                
                <div class="space-y-5">
                    <div>
                        <p class="text-[10px] text-indigo-500 uppercase font-bold tracking-wider">Ciclo Vinculado</p>
                        <p class="text-indigo-900 font-bold bg-white/60 px-3 py-1.5 rounded-md text-sm mt-1 border border-indigo-100/50 shadow-sm inline-block">
                            {{ $inscricao->ciclo->nome ?? 'Legado' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[10px] text-indigo-500 uppercase font-bold tracking-wider">Unidade</p>
                        <p class="text-indigo-900 font-bold text-base mt-1">{{ $inscricao->unidade->nome ?? 'Não informada' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-indigo-500 uppercase font-bold tracking-wider">Curso</p>
                        <p class="text-indigo-900 font-bold text-base mt-1">{{ $inscricao->curso->nome ?? 'Não informado' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-indigo-500 uppercase font-bold tracking-wider">Turno</p>
                        <p class="text-indigo-900 font-bold text-base mt-1">{{ $inscricao->turno->nome ?? 'Não informado' }}</p>
                    </div>
                </div>
            </div>

            {{-- AUDITORIA DA PONTUAÇÃO --}}
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-yellow-400 rounded-bl-full -z-0 opacity-10"></div>
                
                <div class="flex justify-between items-start border-b border-gray-100 pb-3 mb-5 relative z-10">
                    <h3 class="text-lg font-extrabold text-gray-900">Score Rating</h3>
                    <div class="text-center">
                        <span class="bg-gray-900 text-yellow-400 font-extrabold text-xl px-4 py-1.5 rounded-lg shadow-sm border border-gray-800">
                            {{ $inscricao->pontuacao_total ?? 0 }} <span class="text-xs text-gray-400">pts</span>
                        </span>
                    </div>
                </div>
                
                @if($inscricao->pontuacao_detalhes)
                    @php
                        $detalhes = is_string($inscricao->pontuacao_detalhes) ? json_decode($inscricao->pontuacao_detalhes, true) : $inscricao->pontuacao_detalhes;
                    @endphp
                    
                    @if(isset($detalhes['auditoria_detalhada']) && count($detalhes['auditoria_detalhada']) > 0)
                        <div class="space-y-3 mb-4">
                            <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider mb-2">Regras Atingidas:</p>
                            @foreach($detalhes['auditoria_detalhada'] as $info)
                                <div class="bg-white border border-gray-200 shadow-sm px-3 py-2.5 rounded-lg flex justify-between items-center group hover:border-yellow-400 transition-colors">
                                    <div class="max-w-[180px]">
                                        <span class="text-[10px] font-bold text-gray-900 uppercase truncate block">{{ str_replace('_', ' ', $info['campo_avaliado'] ?? 'Campo') }}</span>
                                        <p class="text-xs text-gray-500 mt-0.5 truncate" title="{{ $info['resposta_dada'] ?? '-' }}">Resp: <b>{{ $info['resposta_dada'] ?? '-' }}</b></p>
                                    </div>
                                    <span class="text-green-700 font-extrabold bg-green-50 px-2.5 py-1 rounded-md text-xs border border-green-200 shrink-0">
                                        +{{ $info['pontos_ganhos'] ?? 0 }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    
                    @if(isset($detalhes['motivo_auditoria']))
                        <div class="bg-gray-900 rounded-lg p-3 text-[11px] font-mono text-green-400 leading-tight border border-gray-800 mt-4">
                            <span class="text-gray-500">&gt;_ sys.log:</span><br>
                            {{ $detalhes['motivo_auditoria'] }}
                        </div>
                    @endif
                @else
                    <div class="p-4 bg-gray-50 rounded-lg text-center border border-dashed border-gray-300">
                        <i class="ph-fill ph-calculator text-2xl text-gray-400 mb-2"></i>
                        <p class="text-gray-500 text-sm font-medium leading-snug">Nenhuma pontuação vinculada.<br>O sistema não identificou regras ativas na data desta inscrição.</p>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>