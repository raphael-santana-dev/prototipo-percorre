<div class="p-6 max-w-[1400px] mx-auto font-sans"
     x-data="{ emailModal: @entangle('modalEmailAberto') }" 
     x-effect="document.body.classList.toggle('overflow-hidden', emailModal)">

    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => '#'], 
        ['label' => 'Inscrições', 'url' => route('inscricoes.index') ?? '#'], 
        ['label' => 'Ficha do Candidato', 'url' => '#']
    ]" />

    <x-details-card 
        title="Ficha do Candidato" 
        subtitle="Inscrição iniciada em {{ $this->getDataInscricao()->format('d/m/Y \à\s H:i') }}"
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
                        'aprovado' => 'tag--filled tag--pistache',
                        'reprovado' => 'tag--filled tag--pitaya',
                        'lead' => 'tag--filled tag--purpura',
                        'incompleto' => 'tag--filled tag--neutral',
                        default => 'tag--filled tag--ponkan',
                    };
                @endphp
                <span class="tag tag--small {{ $corBg }} border-0 uppercase">
                    {{ \App\Models\StatusInscricao::find($inscricao->status_inscricao_id)->nome ?? 'Sem Status' }}
                </span>

                @if(feature('inscricao.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('inscricao.editar')))
                    <div class="flex items-center gap-1.5 bg-gray-50 p-1 rounded-lg border border-gray-200">
                        <select wire:model="status_selecionado" class="border-none bg-transparent rounded-md text-[11px] font-bold text-gray-700 focus:ring-0 py-1 pl-2 pr-6 cursor-pointer hover:bg-gray-100 transition-colors shadow-none">
                            @foreach($todosStatus as $status)
                                <option value="{{ $status->id }}">{{ $status->nome }}</option>
                            @endforeach
                        </select>
                        <button wire:click="atualizarStatus" class="btn btn--primary btn--small !h-[28px] !px-3 !text-[10px] uppercase">
                            Mover
                        </button>
                    </div>
                @endif
            </div>
        </x-slot>

        <div>
            <span class="block t-label-12-semibold text-gray-500 uppercase tracking-wider mb-1">Nascimento (Idade)</span>
            <span class="block text-sm font-bold text-gray-900 mt-1">
                {{ $inscricao->data_nascimento ? \Carbon\Carbon::parse($inscricao->data_nascimento)->format('d/m/Y') : 'Não informada' }} 
                @if($inscricao->data_nascimento)
                    <span class="text-purpura-600 ml-1">({{ \Carbon\Carbon::parse($inscricao->data_nascimento)->age }} anos)</span>
                @endif
            </span>
        </div>
        <div>
            <span class="block t-label-12-semibold text-gray-500 uppercase tracking-wider mb-1">Celular / Telefone</span>
            <span class="block text-sm font-bold text-gray-900 mt-1">{{ $inscricao->celular ?? 'Não informado' }}</span>
        </div>
        <div>
            <span class="block t-label-12-semibold text-gray-500 uppercase tracking-wider mb-1">Oferta / Situação</span>
            <span class="block text-sm font-bold text-gray-900 mt-1">
                @if($inscricao->deseja_informar)
                    <span class="text-ponkan-600 font-bold"><i class="ph-fill ph-clock"></i> Lista de Espera</span>
                @else
                    {{ $inscricao->unidade->nome ?? 'Não informada' }}
                @endif
            </span>
        </div>
        <div>
            <span class="block t-label-12-semibold text-gray-500 uppercase tracking-wider mb-1">Última Atualização</span>
            <span class="block text-sm font-bold text-gray-900 mt-1">{{ $this->getDataAtualizacao()->format('d/m/Y \à\s H:i') }}</span>
        </div>
    </x-details-card>

    {{-- MENU DE NAVEGAÇÃO EM ABAS --}}
    <div class="mt-8 border-b border-gray-200 dark:border-gray-700 flex gap-6 px-2 overflow-x-auto no-scrollbar">
        <button wire:click="setAba('geral')" class="pb-3 text-sm font-bold transition-colors border-b-2 whitespace-nowrap {{ $abaAtiva === 'geral' ? 'border-purpura-600 text-purpura-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">Geral</button>
        <button wire:click="setAba('movimentacao')" class="pb-3 text-sm font-bold transition-colors border-b-2 whitespace-nowrap {{ $abaAtiva === 'movimentacao' ? 'border-purpura-600 text-purpura-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">Movimentação</button>
        <button wire:click="setAba('comunicacao')" class="pb-3 text-sm font-bold transition-colors border-b-2 whitespace-nowrap {{ $abaAtiva === 'comunicacao' ? 'border-purpura-600 text-purpura-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">Comunicação</button>
    </div>

    {{-- CONTEÚDO DAS ABAS --}}
    @if($abaAtiva === 'geral')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6 animate-fade-in-down">
            <div class="lg:col-span-2">
                <div class="card p-6 md:p-8 space-y-10">
                    <section>
                        <div class="flex justify-between items-center mb-5 border-b border-gray-100 pb-2">
                            <h3 class="t-label-12-semibold tracking-wider text-gray-500 uppercase flex items-center gap-2">
                                <i class="ph-fill ph-student text-lg text-purpura-500"></i> Dados da Inscrição
                            </h3>
                            <span class="tag tag--small tag--outline tag--neutral">Origem: {{ ucfirst($inscricao->origem ?? 'Não informada') }}</span>
                        </div>
                        
                        <dl class="grid grid-cols-1 md:grid-cols-3 gap-y-6 gap-x-4">
                            @if($inscricao->deseja_informar)
                                <div class="md:col-span-3">
                                    <div class="bg-ponkan-50 border border-ponkan-200 text-ponkan-900 p-4 rounded-lg flex items-start gap-3 shadow-sm">
                                        <i class="ph-fill ph-warning-circle text-2xl text-ponkan-500 mt-0.5"></i>
                                        <div>
                                            <h4 class="font-bold text-sm">Lista de Espera (Interesse Registrado)</h4>
                                            <p class="text-xs mt-1 text-ponkan-800">Este candidato não encontrou vagas no momento da inscrição e registrou interesse para ser avisado futuramente.</p>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <dt class="t-label-12-semibold text-gray-400 uppercase tracking-wider mb-1">Unidade de Interesse</dt>
                                    <dd class="text-sm font-bold text-gray-900">{{ $inscricao->unidade_interesse_id ? (\App\Modules\Unidade\Domain\Models\Unidade::find($inscricao->unidade_interesse_id)?->nome ?? 'N/A') : 'Não informada' }}</dd>
                                </div>
                                <div>
                                    <dt class="t-label-12-semibold text-gray-400 uppercase tracking-wider mb-1">Curso de Interesse</dt>
                                    <dd class="text-sm font-bold text-gray-900">{{ $inscricao->curso_interesse_id ? (\App\Models\Curso::find($inscricao->curso_interesse_id)?->nome ?? 'N/A') : 'Não informado' }}</dd>
                                </div>
                                <div>
                                    <dt class="t-label-12-semibold text-gray-400 uppercase tracking-wider mb-1">Turno de Interesse</dt>
                                    <dd class="text-sm font-bold text-gray-900">{{ $inscricao->turno_interesse_id ? (\App\Modules\Turno\Domain\Models\Turno::find($inscricao->turno_interesse_id)?->nome ?? 'N/A') : 'Não informado' }}</dd>
                                </div>
                            @else
                                <div>
                                    <dt class="t-label-12-semibold text-gray-400 uppercase tracking-wider mb-1">Unidade Selecionada</dt>
                                    <dd class="text-sm font-bold text-gray-900">{{ $inscricao->unidade->nome ?? 'Não informada' }}</dd>
                                </div>
                                <div>
                                    <dt class="t-label-12-semibold text-gray-400 uppercase tracking-wider mb-1">Curso Selecionado</dt>
                                    <dd class="text-sm font-bold text-gray-900">{{ $inscricao->curso->nome ?? 'Não informado' }}</dd>
                                </div>
                                <div>
                                    <dt class="t-label-12-semibold text-gray-400 uppercase tracking-wider mb-1">Turno Selecionado</dt>
                                    <dd class="text-sm font-bold text-gray-900">{{ $inscricao->turno->nome ?? 'Não informado' }}</dd>
                                </div>
                            @endif
                        </dl>
                    </section>
                    <section>
                        <h3 class="t-label-12-semibold tracking-wider text-gray-500 uppercase flex items-center gap-2 mb-5 border-b border-gray-100 pb-2">
                            <i class="ph-fill ph-map-pin text-lg text-purpura-500"></i> Endereço Completo
                        </h3>
                        
                        <dl class="grid grid-cols-1 md:grid-cols-3 gap-y-6 gap-x-4">
                            <div class="md:col-span-3">
                                <dt class="t-label-12-semibold text-gray-400 uppercase tracking-wider mb-1">Logradouro</dt>
                                <dd class="text-sm font-bold text-gray-900">{{ $inscricao->logradouro ?? '-' }}, {{ $inscricao->numero ?? 'S/N' }} {{ $inscricao->complemento ? ' - ' . $inscricao->complemento : '' }}</dd>
                            </div>
                            <div>
                                <dt class="t-label-12-semibold text-gray-400 uppercase tracking-wider mb-1">Bairro</dt>
                                <dd class="text-sm font-bold text-gray-900">{{ $inscricao->bairro ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="t-label-12-semibold text-gray-400 uppercase tracking-wider mb-1">Cidade / UF</dt>
                                <dd class="text-sm font-bold text-gray-900">{{ $inscricao->cidade ?? '-' }} / {{ $inscricao->estado ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="t-label-12-semibold text-gray-400 uppercase tracking-wider mb-1">CEP & Região</dt>
                                <dd class="text-sm font-bold text-gray-900">{{ $inscricao->cep ?? '-' }} {{ $inscricao->regiao ? '(' . ucfirst($inscricao->regiao) . ')' : '' }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section>
                        <h3 class="t-label-12-semibold tracking-wider text-gray-500 uppercase flex items-center gap-2 mb-5 border-b border-gray-100 pb-2">
                            <i class="ph-fill ph-identification-card text-lg text-purpura-500"></i> Informações Adicionais
                        </h3>
                        
                        <dl class="grid grid-cols-1 md:grid-cols-3 gap-y-6 gap-x-4">
                            <div>
                                <dt class="t-label-12-semibold text-gray-400 uppercase tracking-wider mb-1">Nome Social</dt>
                                <dd class="text-sm font-bold text-gray-900">{{ $inscricao->nome_social ?: 'Não possui' }}</dd>
                            </div>
                            <div>
                                <dt class="t-label-12-semibold text-gray-400 uppercase tracking-wider mb-1">PcD (Deficiência)</dt>
                                <dd class="text-sm font-bold text-gray-900">
                                    @if(strtolower($inscricao->possui_deficiencia ?? 'nao') === 'sim')
                                        <span class="tag tag--small tag--filled tag--pitaya mt-1">Sim - {{ $inscricao->natureza_deficiencia }}</span>
                                    @else
                                        Não declarada
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="t-label-12-semibold text-gray-400 uppercase tracking-wider mb-1">Autoriza Uso de Imagem/Dados</dt>
                                <dd class="text-sm font-bold text-gray-900">{{ $inscricao->autorizacao_uso_infos ? 'Sim' : 'Não' }}</dd>
                            </div>
                            <div>
                                <dt class="t-label-12-semibold text-gray-400 uppercase tracking-wider mb-1">Deseja Receber Informações</dt>
                                <dd class="text-sm font-bold text-gray-900">{{ $inscricao->receber_informacoes ? 'Sim' : 'Não' }}</dd>
                            </div>
                            @if($inscricao->token_matricula)
                                <div class="md:col-span-2">
                                    <dt class="t-label-12-semibold text-gray-400 uppercase tracking-wider mb-1">Token de Matrícula</dt>
                                    <dd class="text-sm font-bold text-gray-900 font-mono bg-gray-50 px-2 py-1 rounded inline-block">{{ $inscricao->token_matricula }}</dd>
                                </div>
                            @endif
                        </dl>
                    </section>
                    
                    <section>
                        <h3 class="t-label-12-semibold tracking-wider text-gray-500 uppercase flex items-center gap-2 mb-5 border-b border-gray-100 pb-2">
                            <i class="ph-fill ph-list-dashes text-lg text-purpura-500"></i> Questionário Complementar
                        </h3>
                        
                        @php
                            $dinamicos = is_string($inscricao->dados_dinamicos) ? json_decode($inscricao->dados_dinamicos, true) : ($inscricao->dados_dinamicos ?? []);
                        @endphp

                        @if(is_array($dinamicos) && count($dinamicos) > 0)
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-4">
                                @foreach($dinamicos as $chave => $valor)
                                    @continue(str_contains(strtolower($chave), 'form_config'))
                                    @continue(in_array(strtolower(trim($chave)), ['submission started', 'last updated']))
                                    
                                    @php
                                        $isAssociative = is_array($valor) && count(array_filter(array_keys($valor), 'is_string')) > 0;
                                    @endphp

                                    @if($isAssociative)
                                        @foreach($valor as $rede => $usuario)
                                            @continue(empty($usuario))
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 bg-gray-50 border border-gray-200">
                                                    @if($rede === 'instagram') <i class="ph-fill ph-instagram-logo text-lg text-pink-500"></i>
                                                    @elseif($rede === 'facebook') <i class="ph-fill ph-facebook-logo text-lg text-blue-600"></i>
                                                    @elseif($rede === 'youtube') <i class="ph-fill ph-youtube-logo text-lg text-red-600"></i>
                                                    @elseif($rede === 'tiktok') <i class="ph-fill ph-tiktok-logo text-lg text-gray-900"></i>
                                                    @elseif($rede === 'linkedin') <i class="ph-fill ph-linkedin-logo text-lg text-blue-700"></i>
                                                    @else <i class="ph-fill ph-link text-lg text-gray-500"></i>
                                                    @endif
                                                </div>
                                                <div class="overflow-hidden">
                                                    <dt class="block t-label-12-semibold text-gray-400 uppercase tracking-wider mb-0.5 truncate">Rede Social ({{ ucfirst($rede) }})</dt>
                                                    <dd class="block text-sm font-bold text-gray-900 truncate" title="{{ $usuario }}">{{ $usuario }}</dd>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div>
                                            <dt class="block t-label-12-semibold text-gray-400 uppercase tracking-wider mb-1">{{ str_replace('_', ' ', $chave) }}</dt>
                                            <dd class="block text-sm font-bold text-gray-900 break-words">
                                                {{ !empty($valor) ? (is_array($valor) ? implode(', ', $valor) : $valor) : '-' }}
                                            </dd>
                                        </div>
                                    @endif
                                @endforeach
                            </dl>
                        @else
                            <p class="text-gray-500 text-sm font-medium">Nenhum dado complementar registrado para este candidato.</p>
                        @endif
                    </section>

                    <section class="mt-10">
                        <h3 class="t-label-12-semibold tracking-wider text-gray-500 uppercase flex items-center gap-2 mb-5 border-b border-gray-100 pb-2">
                            <i class="ph-fill ph-code text-lg text-purpura-500"></i> Metadados Ocultos (Importação)
                        </h3>
                        
                        @php
                            $metaArr = is_string($inscricao->metadados) ? json_decode($inscricao->metadados, true) : ($inscricao->metadados ?? []);
                        @endphp

                        @if(is_array($metaArr) && count($metaArr) > 0)
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-4">
                                @foreach($metaArr as $chave => $valor)
                                    <div>
                                        <dt class="block t-label-12-semibold text-gray-400 uppercase tracking-wider mb-1">{{ $chave }}</dt>
                                        <dd class="block text-sm font-bold text-gray-900 break-words">{{ is_array($valor) ? json_encode($valor) : $valor }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @else
                            <p class="text-gray-500 text-sm font-medium">Nenhum metadado de sistema foi registrado para este candidato.</p>
                        @endif
                    </section>
                </div>
            </div>

            <div class="lg:col-span-1 space-y-6">
                <div class="card !p-0 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-ponkan-400 rounded-bl-full -z-0 opacity-10"></div>
                    
                    <div class="p-6 relative z-10 w-full">
                        <div class="flex justify-between items-start border-b border-gray-100 pb-4 mb-4">
                            <h3 class="t-label-12-semibold tracking-wider text-gray-500 uppercase flex items-center gap-2">
                                <i class="ph-fill ph-calculator text-lg text-ponkan-500"></i> Pontuação
                            </h3>
                            <div class="text-center">
                                <span class="bg-gray-900 text-ponkan-400 font-extrabold text-xl px-3 py-1 rounded-lg shadow-sm border border-gray-800">
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
                                    <p class="t-label-12-semibold text-gray-400 uppercase tracking-wider mb-2">Regras Atendidas / Cálculos:</p>
                                    @foreach($detalhes['auditoria_detalhada'] as $info)
                                        @php
                                            $isPadrao = ($info['tipo_regra'] ?? 'padrao') === 'padrao';
                                        @endphp
                                        <div class="bg-white border border-gray-100 p-3 rounded-lg flex justify-between items-center group hover:border-ponkan-400 transition-colors shadow-sm">
                                            <div class="flex-1 pr-3">
                                                <span class="text-[10px] font-bold text-gray-900 uppercase block mb-1">
                                                    {{ str_replace('_', ' ', $info['campo_avaliado'] ?? 'Regra Padrão') }}
                                                </span>
                                                
                                                @if($isPadrao)
                                                    <p class="text-xs text-gray-600 mb-1">
                                                        Resposta: <b class="text-gray-900">{{ $info['resposta_dada'] ?? '-' }}</b>
                                                    </p>
                                                    <p class="text-[9px] text-gray-400 font-bold mt-1 leading-tight">
                                                        <i class="ph-fill ph-info"></i> Condição atendida: {{ str_replace('Exigência: ', '', $info['condicao'] ?? '') }}
                                                    </p>
                                                @else
                                                    <p class="text-[10px] text-petunia-600 font-bold mt-1 leading-tight">
                                                        <i class="ph-fill ph-info"></i> {{ str_replace('Exigência: ', '', $info['condicao'] ?? 'Bônus/Multiplicador aplicado') }}
                                                    </p>
                                                @endif
                                            </div>
                                            <span class="text-pistache-700 font-extrabold bg-pistache-50 px-2 py-1 rounded-md text-[11px] border border-pistache-200 shrink-0">
                                                +{{ $info['pontos_ganhos'] ?? 0 }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mt-3 text-right">
                                    <button wire:click="abrirRegras" class="btn btn--secondary btn--small w-full">
                                        <i class="ph-bold ph-list-numbers text-sm"></i> Ver mapa completo
                                    </button>
                                </div>
                            @endif
                        @else
                            <div class="py-10 text-center">
                                <i class="ph-fill ph-calculator text-3xl text-gray-200 mb-2"></i>
                                <p class="text-gray-400 text-xs font-bold leading-snug">Nenhuma pontuação vinculada.<br>Cálculos não foram realizados.</p>
                            </div>
                        @endif
                    
                        @if($inscricao->posicao_ranking_geral || $inscricao->posicao_ranking)
                            <div class="mt-6 border-t border-gray-100 pt-5">
                                <h4 class="t-label-12-semibold text-gray-400 uppercase tracking-wider mb-3">Classificação (Ranking)</h4>
                                <div class="grid grid-cols-2 gap-3">
                                    @if($inscricao->posicao_ranking_geral)
                                        <div class="bg-gray-50 border border-gray-200 p-2 rounded-lg text-center shadow-sm">
                                            <span class="block text-xl font-black text-gray-700">{{ $inscricao->posicao_ranking_geral }}º</span>
                                            <span class="block text-[9px] text-gray-500 uppercase font-bold mt-1">Geral</span>
                                        </div>
                                    @endif
                                    @if($inscricao->posicao_ranking_unidade)
                                        <div class="bg-gray-50 border border-gray-200 p-2 rounded-lg text-center shadow-sm">
                                            <span class="block text-xl font-black text-gray-700">{{ $inscricao->posicao_ranking_unidade }}º</span>
                                            <span class="block text-[9px] text-gray-500 uppercase font-bold mt-1">Unidade</span>
                                        </div>
                                    @endif
                                    @if($inscricao->posicao_ranking_curso)
                                        <div class="bg-gray-50 border border-gray-200 p-2 rounded-lg text-center shadow-sm">
                                            <span class="block text-xl font-black text-gray-700">{{ $inscricao->posicao_ranking_curso }}º</span>
                                            <span class="block text-[9px] text-gray-500 uppercase font-bold mt-1">Curso</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                
                <div class="card !p-5 bg-purpura-50/50 border-purpura-100 text-center">
                    <span class="block t-label-12-semibold text-purpura-400 uppercase tracking-wider mb-1 w-full">Ciclo Operacional</span>
                    <span class="block text-sm font-bold text-purpura-900 w-full">{{ $inscricao->ciclo->nome ?? 'Formulário Legado' }}</span>
                </div>

            </div>
        </div>
    @elseif($abaAtiva === 'movimentacao')
        <div class="mt-6 card !p-6 md:!p-8 animate-fade-in-down w-full block">
            <h3 class="t-label-12-semibold tracking-wider text-gray-500 uppercase flex items-center gap-2 mb-8">
                <i class="ph-fill ph-clock-counter-clockwise text-lg text-purpura-500"></i> Histórico de Movimentação do CRM
            </h3>
            
            @if(count($this->logsMovimentacao) > 0)
                <div class="relative border-l-2 border-gray-200 dark:border-gray-700 ml-3 mt-4 space-y-8 pb-4">
                    @foreach($this->logsMovimentacao as $mov)
                        <div class="relative pl-6">
                            {{-- Ponto na Timeline --}}
                            <div class="absolute w-4 h-4 rounded-full {{ $mov['tipo'] === 'criacao' ? 'bg-emerald-500' : 'bg-purpura-500' }} border-4 border-white dark:border-gray-900 left-[-9px] top-1 shadow-sm"></div>
                            
                            {{-- Card de Evento --}}
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col gap-1.5 w-full">
                                <div class="flex items-center gap-2">
                                    <i class="ph-fill {{ $mov['tipo'] === 'criacao' ? 'ph-rocket text-emerald-500' : 'ph-user text-purpura-500' }}"></i>
                                    <span class="font-bold text-gray-900 dark:text-white text-sm">{{ $mov['usuario'] }}</span>
                                </div>
                                <p class="text-sm text-gray-700 dark:text-gray-300 w-full">
                                    {!! $mov['mensagem'] !!}
                                </p>
                                <span class="text-xs font-bold text-gray-400 mt-1">{{ $mov['data']->format('d/m/Y \à\s H:i') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-12 text-center text-gray-500">
                    <i class="ph-fill ph-clock-counter-clockwise text-5xl mb-3 text-gray-300"></i>
                    <p class="font-medium text-sm">Nenhuma movimentação registrada até o momento.</p>
                </div>
            @endif
        </div>

    @elseif($abaAtiva === 'comunicacao')
        <div class="mt-6 card !p-0 overflow-hidden animate-fade-in-down w-full block">
            <div class="p-6 md:p-8 border-b border-gray-100 dark:border-gray-700 w-full">
                <h3 class="t-label-12-semibold tracking-wider text-gray-500 uppercase flex items-center gap-2">
                    <i class="ph-fill ph-envelope-simple text-lg text-purpura-500"></i> Histórico de E-mails Disparados
                </h3>
            </div>
            
            @if(count($this->logsComunicacao) > 0)
                <div class="overflow-x-auto w-full">
                    <table class="w-full min-w-full text-left whitespace-nowrap">
                        <thead class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th class="px-6 py-3 font-semibold text-xs text-gray-500 uppercase">Assunto / Template</th>
                                <th class="px-6 py-3 font-semibold text-xs text-gray-500 uppercase">Origem</th>
                                <th class="px-6 py-3 font-semibold text-xs text-gray-500 uppercase">Gatilho</th>
                                <th class="px-6 py-3 font-semibold text-xs text-gray-500 uppercase">Disparado por</th>
                                <th class="px-6 py-3 font-semibold text-xs text-gray-500 uppercase">Data de Envio</th>
                                <th class="px-6 py-3 font-semibold text-xs text-gray-500 uppercase text-center">Status</th>
                                <th class="px-6 py-3 font-semibold text-xs text-gray-500 uppercase text-center">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($this->logsComunicacao as $log)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-sm text-gray-900 dark:text-white">{{ \Illuminate\Support\Str::limit($log->assunto, 40) }}</div>
                                        <div class="text-xs text-gray-500 mt-0.5">Template: {{ $log->template_nome ?? 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 text-[10px] font-bold {{ strtolower($log->origem) === 'automação' || strtolower($log->origem) === 'automacao' ? 'text-purpura-700 bg-purpura-100 border border-purpura-200' : 'text-blue-700 bg-blue-100 border border-blue-200' }} rounded-full uppercase tracking-wider">
                                            {{ $log->origem }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $log->gatilho ? str_replace('inscricao.status.', 'Status: ', $log->gatilho) : '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 font-medium">
                                        {{ $log->usuario_nome ?? 'Sistema' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $log->data_envio ? \Carbon\Carbon::parse($log->data_envio)->format('d/m/Y H:i') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($log->status === 'enviado')
                                            <span class="px-2.5 py-1 text-[10px] font-bold text-green-700 bg-green-100 border border-green-200 rounded-full uppercase tracking-wider">Enviado</span>
                                        @elseif($log->status === 'erro')
                                            <span class="px-2.5 py-1 text-[10px] font-bold text-red-700 bg-red-100 border border-red-200 rounded-full uppercase tracking-wider" title="{{ $log->erro_mensagem }}">Erro</span>
                                        @else
                                            <span class="px-2.5 py-1 text-[10px] font-bold text-yellow-700 bg-yellow-100 border border-yellow-200 rounded-full uppercase tracking-wider">Pendente</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button wire:click="verConteudoEmail({{ $log->id }})" class="p-2 bg-gray-100 dark:bg-gray-700 hover:bg-purpura-100 hover:text-purpura-600 text-gray-600 dark:text-gray-300 rounded-lg transition-colors shadow-sm" title="Visualizar E-mail">
                                            <i class="ph-bold ph-eye text-lg"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-16 text-center text-gray-500 w-full">
                    <i class="ph-fill ph-envelope-open text-5xl mb-3 text-gray-300"></i>
                    <p class="font-medium text-sm">Nenhum e-mail disparado para este candidato.</p>
                </div>
            @endif
        </div>
    @endif

    {{-- MODAL DE SPAM --}}
    @if($modalAntiSpamAberto)
        <div class="fixed inset-0 z-[120] flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="card !w-full !max-w-md !p-0 shadow-2xl">
                <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-pitaya-50 dark:bg-pitaya-900/20 w-full">
                    <h3 class="text-lg font-bold text-pitaya-700 dark:text-pitaya-400 flex items-center gap-2">
                        <i class="ph-fill ph-warning-circle text-2xl"></i> Alerta Anti-Spam
                    </h3>
                </div>
                
                <div class="p-6 w-full">
                    <p class="text-sm text-gray-700 dark:text-gray-300 font-medium text-center">
                        Este candidato <strong>já recebeu</strong> o e-mail automático configurado para esta etapa anteriormente.
                    </p>
                    <p class="text-xs text-gray-500 text-center mt-2">Deseja alterar o status e disparar o e-mail novamente?</p>
                </div>

                <div class="p-5 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex flex-col md:flex-row justify-center gap-3 w-full">
                    <button wire:click="cancelarAntiSpam" class="btn btn--secondary btn--medium">
                        Cancelar Alteração
                    </button>
                    <button wire:click="executarMudancaStatusFinal" class="btn btn--primary btn--medium !bg-pitaya-600 !text-white hover:!bg-pitaya-700 border-none shadow-sm">
                        <i class="ph-bold ph-paper-plane-tilt"></i> Sim, Reenviar E-mail
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL DE VISUALIZAÇÃO DE E-MAIL --}}
    @if($emailVisualizacao)
        <div class="fixed inset-0 z-[130] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">
                <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center bg-gray-50 dark:bg-gray-800/50">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="ph-fill ph-envelope-open text-purpura-600"></i> Visualização de E-mail
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">Enviado em {{ $emailVisualizacao->data_envio ? \Carbon\Carbon::parse($emailVisualizacao->data_envio)->format('d/m/Y \à\s H:i') : 'Pendente' }}</p>
                    </div>
                    <button wire:click="fecharModalEmail" class="text-gray-400 hover:text-red-500 transition"><i class="ph-bold ph-x text-xl"></i></button>
                </div>
                
                <div class="p-6 border-b border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900">
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                        <div class="col-span-2 lg:col-span-4"><span class="font-bold text-gray-500 uppercase text-[10px] tracking-wider block mb-1">Assunto</span> <span class="font-medium text-gray-900 dark:text-gray-100">{{ $emailVisualizacao->assunto }}</span></div>
                        <div class="col-span-2 lg:col-span-1"><span class="font-bold text-gray-500 uppercase text-[10px] tracking-wider block mb-1">Destinatário</span> <span class="font-medium text-gray-900 dark:text-gray-100">{{ $emailVisualizacao->destinatario }}</span></div>
                        <div class="col-span-2 lg:col-span-1"><span class="font-bold text-gray-500 uppercase text-[10px] tracking-wider block mb-1">Template Usado</span> <span class="font-medium text-gray-900 dark:text-gray-100">{{ $emailVisualizacao->template_nome ?? 'Desconhecido' }}</span></div>
                        <div class="col-span-2 lg:col-span-2"><span class="font-bold text-gray-500 uppercase text-[10px] tracking-wider block mb-1">Gatilho / Disparado Por</span> <span class="font-medium text-gray-900 dark:text-gray-100">{{ $emailVisualizacao->gatilho ?? '-' }} ({{ $emailVisualizacao->usuario_nome ?? 'Sistema' }})</span></div>
                    </div>
                </div>

                <div class="p-6 overflow-y-auto bg-gray-50 dark:bg-gray-800/30 flex-1">
                    <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm min-h-[300px]">
                        {!! $emailVisualizacao->corpo !!}
                    </div>
                </div>

                <div class="p-4 border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 flex justify-end">
                    <button wire:click="fecharModalEmail" class="btn btn--secondary btn--medium">Fechar Visualização</button>
                </div>
            </div>
        </div>
    @endif
</div>