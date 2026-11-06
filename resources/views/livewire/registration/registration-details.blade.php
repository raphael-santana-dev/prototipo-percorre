<div class="p-6 max-w-[1400px] mx-auto font-sans"
     x-data="{ emailModal: @entangle('modalEmailAberto') }" 
     x-effect="document.body.classList.toggle('overflow-hidden', emailModal || @entangle('urlEmbedAtiva') || @entangle('modalHistoricoFormularioAberto'))">

    @php
        $dinamicos = is_string($inscricao->dados_dinamicos) ? json_decode($inscricao->dados_dinamicos, true) : ($inscricao->dados_dinamicos ?? []);
        if (!is_array($dinamicos)) $dinamicos = [];
        $mapaParticipante = $dinamicos['_preenchido_por_participante'] ?? [];
        $ultimoAtendimentoParticipante = $mapaParticipante['_ultimo_atendimento'] ?? null;
        $formulariosAssistidos = $dinamicos['_formularios_assistidos'] ?? [];
        $podeEditarAssistido = $this->usuarioPodeEditarAssistido();
    @endphp

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
            <div class="flex flex-col sm:flex-row items-end sm:items-center gap-2.5 flex-wrap justify-end">
                @if($ultimoAtendimentoParticipante || $inscricao->origem === 'manual')
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 shadow-2xs"
                          title="Esta inscrição possui dados preenchidos ou enviados por um Participante (funcionário)">
                        <i class="ph-fill ph-user-switch text-sm text-indigo-600"></i>
                        <span>Preenchido por Participante{{ !empty($ultimoAtendimentoParticipante['user_nome']) ? ': ' . $ultimoAtendimentoParticipante['user_nome'] : '' }}</span>
                    </span>
                @endif

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

                <button type="button"
                        wire:click="baixarFichaInscricaoPdf({{ $inscricao->id }})"
                        wire:loading.attr="disabled"
                        class="btn btn--secondary btn--small !h-[30px] !px-3 !text-[11px] bg-white border border-gray-200 text-purpura-700 hover:bg-purpura-50 flex items-center gap-1.5 shadow-2xs">
                    <i class="ph-bold ph-file-pdf text-sm text-purpura-600"></i> Ficha de Inscrição (PDF)
                </button>

                @if($podeEditarAssistido)
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
        <button wire:click="setAba('geral')" class="pb-3 text-sm font-bold transition-colors border-b-2 whitespace-nowrap flex items-center gap-1.5 {{ $abaAtiva === 'geral' ? 'border-purpura-600 text-purpura-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
            <i class="ph-bold ph-identification-card"></i> Geral e Dados
        </button>

        <button wire:click="setAba('documentacao')" class="pb-3 text-sm font-bold transition-colors border-b-2 whitespace-nowrap flex items-center gap-1.5 {{ $abaAtiva === 'documentacao' ? 'border-purpura-600 text-purpura-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
            <i class="ph-bold ph-folder-open"></i> Documentação (Envio Assistido)
            @if(count($this->documentosExigidos) > 0)
                <span class="px-1.5 py-0.5 text-[10px] rounded-full bg-purpura-50 text-purpura-700 border border-purpura-200">
                    {{ count($this->documentosExigidos) }}
                </span>
            @endif
        </button>

        <button wire:click="setAba('formularios')" class="pb-3 text-sm font-bold transition-colors border-b-2 whitespace-nowrap flex items-center gap-1.5 {{ $abaAtiva === 'formularios' ? 'border-purpura-600 text-purpura-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
            <i class="ph-bold ph-browsers"></i> Formulários (Embed)
        </button>

        <button wire:click="setAba('movimentacao')" class="pb-3 text-sm font-bold transition-colors border-b-2 whitespace-nowrap flex items-center gap-1.5 {{ $abaAtiva === 'movimentacao' ? 'border-purpura-600 text-purpura-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
            <i class="ph-bold ph-clock-counter-clockwise"></i> Movimentação
        </button>

        <button wire:click="setAba('comunicacao')" class="pb-3 text-sm font-bold transition-colors border-b-2 whitespace-nowrap flex items-center gap-1.5 {{ $abaAtiva === 'comunicacao' ? 'border-purpura-600 text-purpura-600' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
            <i class="ph-bold ph-envelope-simple"></i> Comunicação
        </button>
    </div>

    {{-- ===================================================================== --}}
    {{-- ABA 1: GERAL E EDIÇÃO ASSISTIDA POR PARTICIPANTE                      --}}
    {{-- ===================================================================== --}}
    @if($abaAtiva === 'geral')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6 animate-fade-in-down">
            <div class="lg:col-span-2">
                <div class="card p-6 md:p-8 space-y-10">

                    {{-- BARRA DE ATENDIMENTO ASSISTIDO PELO PARTICIPANTE --}}
                    @if($podeEditarAssistido)
                        <div class="p-4 rounded-xl border {{ $modoEdicaoAssistida ? 'bg-indigo-50/80 border-indigo-300' : 'bg-gray-50 border-gray-200' }} flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <div class="w-9 h-9 rounded-lg bg-indigo-600 text-white flex items-center justify-center shrink-0 mt-0.5">
                                    <i class="ph-fill ph-user-switch text-lg"></i>
                                </div>
                                <div>
                                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-gray-900">
                                        Modo Participante (Preenchimento Assistido)
                                    </h4>
                                    <p class="text-xs text-gray-600 mt-0.5">
                                        Edite os dados abaixo caso o candidato tenha dificuldade com tecnologia. Os campos alterados receberão o selo <b>Preenchido por Participante</b>.
                                    </p>
                                </div>
                            </div>

                            <div class="shrink-0 flex items-center gap-2">
                                @if(!$modoEdicaoAssistida)
                                    <button type="button" wire:click="iniciarEdicaoAssistida" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-xs transition flex items-center gap-1.5">
                                        <i class="ph-bold ph-pencil-simple"></i> Editar Dados da Inscrição
                                    </button>
                                @else
                                    <button type="button" wire:click="cancelarEdicaoAssistida" class="px-3 py-2 bg-white border border-gray-300 text-gray-700 text-xs font-bold rounded-lg hover:bg-gray-50 transition">
                                        Cancelar
                                    </button>
                                    <button type="button" wire:click="salvarEdicaoAssistida" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow-xs transition flex items-center gap-1.5">
                                        <i class="ph-bold ph-floppy-disk"></i> Salvar como Participante
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($modoEdicaoAssistida)
                        {{-- FORMULÁRIO DE EDIÇÃO ASSISTIDA PELO PARTICIPANTE --}}
                        <form wire:submit.prevent="salvarEdicaoAssistida" class="space-y-8">
                            <section class="space-y-4">
                                <h3 class="t-label-12-semibold tracking-wider text-indigo-700 uppercase flex items-center gap-2 border-b border-indigo-100 pb-2">
                                    <i class="ph-fill ph-identification-card text-lg"></i> 1. Dados Pessoais e Curso
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Nome Completo *</label>
                                        <input type="text" wire:model="formFixo.nome" class="w-full rounded-lg border-gray-300 text-sm">
                                        @error('formFixo.nome') <span class="text-xs text-red-500 font-bold">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">CPF *</label>
                                        <input type="text" wire:model="formFixo.cpf" class="w-full rounded-lg border-gray-300 text-sm">
                                        @error('formFixo.cpf') <span class="text-xs text-red-500 font-bold">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Data de Nascimento</label>
                                        <input type="date" wire:model="formFixo.data_nascimento" class="w-full rounded-lg border-gray-300 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Celular / Telefone</label>
                                        <input type="text" wire:model="formFixo.celular" class="w-full rounded-lg border-gray-300 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">E-mail *</label>
                                        <input type="email" wire:model="formFixo.email" class="w-full rounded-lg border-gray-300 text-sm">
                                        @error('formFixo.email') <span class="text-xs text-red-500 font-bold">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Unidade</label>
                                        <select wire:model="formFixo.unidade_id" class="w-full rounded-lg border-gray-300 text-sm">
                                            <option value="">Selecione...</option>
                                            @foreach($unidadesDb as $u)
                                                <option value="{{ $u->id }}">{{ $u->nome }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Curso</label>
                                        <select wire:model="formFixo.curso_id" class="w-full rounded-lg border-gray-300 text-sm">
                                            <option value="">Selecione...</option>
                                            @foreach($cursosDb as $c)
                                                <option value="{{ $c->id }}">{{ $c->nome }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Turno</label>
                                        <select wire:model="formFixo.turno_id" class="w-full rounded-lg border-gray-300 text-sm">
                                            <option value="">Selecione...</option>
                                            @foreach($turnosDb as $t)
                                                <option value="{{ $t->id }}">{{ $t->nome }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Possui Deficiência (PcD)?</label>
                                        <select wire:model.live="formFixo.possui_deficiencia" class="w-full rounded-lg border-gray-300 text-sm">
                                            <option value="nao">Não</option>
                                            <option value="sim">Sim</option>
                                        </select>
                                    </div>
                                    @if(($formFixo['possui_deficiencia'] ?? 'nao') === 'sim')
                                        <div class="md:col-span-2">
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Natureza da Deficiência</label>
                                            <input type="text" wire:model="formFixo.natureza_deficiencia" class="w-full rounded-lg border-gray-300 text-sm">
                                        </div>
                                    @endif
                                </div>
                            </section>

                            <section class="space-y-4">
                                <h3 class="t-label-12-semibold tracking-wider text-indigo-700 uppercase flex items-center gap-2 border-b border-indigo-100 pb-2">
                                    <i class="ph-fill ph-map-pin text-lg"></i> 2. Endereço
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">CEP</label>
                                        <input type="text" wire:model="formFixo.cep" class="w-full rounded-lg border-gray-300 text-sm">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Logradouro / Rua</label>
                                        <input type="text" wire:model="formFixo.logradouro" class="w-full rounded-lg border-gray-300 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Número</label>
                                        <input type="text" wire:model="formFixo.numero" class="w-full rounded-lg border-gray-300 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Complemento</label>
                                        <input type="text" wire:model="formFixo.complemento" class="w-full rounded-lg border-gray-300 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Bairro</label>
                                        <input type="text" wire:model="formFixo.bairro" class="w-full rounded-lg border-gray-300 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Cidade</label>
                                        <input type="text" wire:model="formFixo.cidade" class="w-full rounded-lg border-gray-300 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Estado (UF)</label>
                                        <input type="text" wire:model="formFixo.estado" maxlength="2" class="w-full rounded-lg border-gray-300 text-sm uppercase">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Região</label>
                                        <input type="text" wire:model="formFixo.regiao" class="w-full rounded-lg border-gray-300 text-sm">
                                    </div>
                                </div>
                            </section>

                            @if($this->camposDinamicosCiclo->count() > 0)
                                <section class="space-y-4">
                                    <h3 class="t-label-12-semibold tracking-wider text-indigo-700 uppercase flex items-center gap-2 border-b border-indigo-100 pb-2">
                                        <i class="ph-fill ph-list-dashes text-lg"></i> 3. Questionário Complementar do Ciclo
                                    </h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        @foreach($this->camposDinamicosCiclo as $cDin)
                                            @continue(in_array($cDin->tipo, ['file', 'signature']))
                                            @php
                                                $opcoesCampo = is_array($cDin->opcoes) ? $cDin->opcoes : (json_decode($cDin->opcoes ?? '[]', true) ?: []);
                                            @endphp
                                            <div>
                                                <label class="block text-xs font-bold text-gray-700 mb-1">{{ strip_tags($cDin->label) }}</label>
                                                @if(in_array($cDin->tipo, ['select', 'radio']) && !empty($opcoesCampo))
                                                    <select wire:model="formDinamico.{{ $cDin->name }}" class="w-full rounded-lg border-gray-300 text-sm">
                                                        <option value="">Selecione...</option>
                                                        @foreach($opcoesCampo as $opt)
                                                            <option value="{{ $opt }}">{{ $opt }}</option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <input type="text" wire:model="formDinamico.{{ $cDin->name }}" class="w-full rounded-lg border-gray-300 text-sm">
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            @endif

                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                                <button type="button" wire:click="cancelarEdicaoAssistida" class="btn btn--secondary btn--medium">Cancelar</button>
                                <button type="submit" class="btn btn--primary btn--medium bg-emerald-600 hover:bg-emerald-700 border-none flex items-center gap-2">
                                    <i class="ph-bold ph-floppy-disk"></i> Salvar Alterações como Participante
                                </button>
                            </div>
                        </form>
                    @else
                        {{-- VISUALIZAÇÃO PADRÃO DOS DADOS COM BADGES DE PARTICIPANTE --}}
                        <section>
                            <div class="flex justify-between items-center mb-5 border-b border-gray-100 pb-2">
                                <h3 class="t-label-12-semibold tracking-wider text-gray-500 uppercase flex items-center gap-2">
                                    <i class="ph-fill ph-student text-lg text-purpura-500"></i> Dados da Inscrição
                                </h3>
                                <div class="flex items-center gap-2">
                                    <span class="tag tag--small tag--outline tag--neutral">Origem: {{ ucfirst($inscricao->origem ?? 'Não informada') }}</span>
                                </div>
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
                                        <dt class="t-label-12-semibold text-gray-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                            <span>Unidade Selecionada</span>
                                            @if(!empty($mapaParticipante['fixo_unidade_id']))
                                                <span class="px-1.5 py-0.5 text-[9px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 rounded" title="Alterado por {{ $mapaParticipante['fixo_unidade_id']['user_nome'] }} em {{ $mapaParticipante['fixo_unidade_id']['data'] }}">Participante</span>
                                            @endif
                                        </dt>
                                        <dd class="text-sm font-bold text-gray-900">{{ $inscricao->unidade->nome ?? 'Não informada' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="t-label-12-semibold text-gray-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                            <span>Curso Selecionado</span>
                                            @if(!empty($mapaParticipante['fixo_curso_id']))
                                                <span class="px-1.5 py-0.5 text-[9px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 rounded" title="Alterado por {{ $mapaParticipante['fixo_curso_id']['user_nome'] }} em {{ $mapaParticipante['fixo_curso_id']['data'] }}">Participante</span>
                                            @endif
                                        </dt>
                                        <dd class="text-sm font-bold text-gray-900">{{ $inscricao->curso->nome ?? 'Não informado' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="t-label-12-semibold text-gray-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                            <span>Turno Selecionado</span>
                                            @if(!empty($mapaParticipante['fixo_turno_id']))
                                                <span class="px-1.5 py-0.5 text-[9px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 rounded" title="Alterado por {{ $mapaParticipante['fixo_turno_id']['user_nome'] }} em {{ $mapaParticipante['fixo_turno_id']['data'] }}">Participante</span>
                                            @endif
                                        </dt>
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
                                    <dt class="t-label-12-semibold text-gray-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                        <span>Logradouro</span>
                                        @if(!empty($mapaParticipante['fixo_logradouro']) || !empty($mapaParticipante['fixo_numero']))
                                            <span class="px-1.5 py-0.5 text-[9px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 rounded">Preenchido por Participante</span>
                                        @endif
                                    </dt>
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
                            </dl>
                        </section>
                        
                        <section>
                            <h3 class="t-label-12-semibold tracking-wider text-gray-500 uppercase flex items-center gap-2 mb-5 border-b border-gray-100 pb-2">
                                <i class="ph-fill ph-list-dashes text-lg text-purpura-500"></i> Questionário Complementar
                            </h3>

                            @if(is_array($dinamicos) && count($dinamicos) > 0)
                                <dl class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-4">
                                    @foreach($dinamicos as $chave => $valor)
                                        @continue(str_contains(strtolower($chave), 'form_config'))
                                        @continue(str_starts_with(strtolower($chave), 'ui_'))
                                        @continue(str_starts_with(strtolower($chave), '_'))
                                        @continue(in_array(strtolower(trim($chave)), ['submission started', 'last updated']))
                                        
                                        @php
                                            $infoPartCampo = $mapaParticipante['din_' . $chave] ?? null;
                                            $isAssociative = is_array($valor) && count(array_filter(array_keys($valor), 'is_string')) > 0;
                                            $isAssinatura = is_string($valor) && str_starts_with($valor, 'data:image/');
                                            $valorFormatado = !empty($valor) ? (is_array($valor) ? implode(', ', $valor) : $valor) : '-';
                                            $isArquivo = is_string($valorFormatado) && preg_match('/\/(uploads|documentos|links)\//', $valorFormatado);
                                        @endphp

                                        @if($isAssociative)
                                            @foreach($valor as $rede => $usuario)
                                                @continue(empty($usuario))
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 bg-gray-50 border border-gray-200">
                                                        <i class="ph-fill ph-link text-lg text-gray-500"></i>
                                                    </div>
                                                    <div class="overflow-hidden">
                                                        <dt class="block t-label-12-semibold text-gray-400 uppercase tracking-wider mb-0.5 truncate">Rede Social ({{ ucfirst($rede) }})</dt>
                                                        <dd class="block text-sm font-bold text-gray-900 truncate" title="{{ $usuario }}">{{ $usuario }}</dd>
                                                    </div>
                                                </div>
                                            @endforeach

                                        @elseif($isAssinatura)
                                            <div class="md:col-span-2">
                                                <dt class="block t-label-12-semibold text-gray-400 uppercase tracking-wider mb-2">
                                                    {{ str_replace('_', ' ', $chave) }}
                                                </dt>
                                                <dd class="inline-flex flex-col items-center p-3 bg-white border border-gray-200 rounded-xl shadow-sm">
                                                    <img src="{{ $valor }}" alt="Assinatura" class="max-h-28 w-auto object-contain">
                                                </dd>
                                            </div>

                                        @elseif($isArquivo)
                                            <div class="md:col-span-2">
                                                <dt class="block t-label-12-semibold text-gray-400 uppercase tracking-wider mb-2">
                                                    {{ str_replace('_', ' ', $chave) }}
                                                </dt>
                                                <dd class="flex flex-wrap gap-2">
                                                    @foreach(explode(', ', $valorFormatado) as $caminhoArquivo)
                                                        @continue(empty(trim($caminhoArquivo)))
                                                        <a href="{{ asset('storage/' . trim($caminhoArquivo)) }}" target="_blank" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white border border-gray-200 text-xs font-bold text-purpura-600 hover:border-purpura-400 shadow-sm transition">
                                                            <i class="ph-bold ph-paperclip text-sm"></i>
                                                            <span class="truncate max-w-[220px]">{{ basename(trim($caminhoArquivo)) }}</span>
                                                            <i class="ph-bold ph-arrow-square-out"></i>
                                                        </a>
                                                    @endforeach
                                                </dd>
                                            </div>

                                        @else
                                            <div>
                                                <dt class="t-label-12-semibold text-gray-400 uppercase tracking-wider mb-1 flex items-center gap-1.5 flex-wrap">
                                                    <span>{{ str_replace('_', ' ', $chave) }}</span>
                                                    @if($infoPartCampo)
                                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200"
                                                              title="Preenchido por Participante: {{ $infoPartCampo['user_nome'] }} em {{ $infoPartCampo['data'] }}">
                                                            <i class="ph-fill ph-user-switch"></i> Participante ({{ $infoPartCampo['user_nome'] }})
                                                        </span>
                                                    @endif
                                                </dt>
                                                <dd class="block text-sm font-bold text-gray-900 break-words">
                                                    {{ $valorFormatado }}
                                                </dd>
                                            </div>
                                        @endif
                                    @endforeach
                                </dl>
                            @else
                                <p class="text-gray-500 text-sm font-medium">Nenhum dado complementar registrado para este candidato.</p>
                            @endif
                        </section>
                    @endif
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

    {{-- ===================================================================== --}}
    {{-- ABA 2: DOCUMENTAÇÃO (ENVIO ASSISTIDO COM CHAVE DE VALIDAÇÃO POR IA)   --}}
    {{-- ===================================================================== --}}
    @elseif($abaAtiva === 'documentacao')
        <div class="mt-6 space-y-6 animate-fade-in-down">
            <div class="card !p-6 md:!p-8 space-y-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-100 pb-5">
                    <div>
                        <h3 class="text-base font-extrabold text-gray-900 flex items-center gap-2">
                            <i class="ph-fill ph-folder-open text-purpura-600 text-xl"></i> Envio de Documentação Assistido (Modo Participante)
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">
                            Envie os documentos de matrícula em nome do candidato. Todos os arquivos enviados por aqui serão identificados com o badge <b>Enviado por Participante</b>.
                        </p>
                    </div>

                    {{-- INTERRUPTOR: ATIVAR / DESATIVAR VALIDAÇÃO POR IA --}}
                    <label class="flex items-center gap-3 px-4 py-3 rounded-xl border cursor-pointer transition shrink-0 {{ $validarComIa ? 'bg-purpura-50/70 border-purpura-300 text-purpura-900' : 'bg-amber-50/70 border-amber-300 text-amber-900' }}">
                        <input type="checkbox" wire:model.live="validarComIa" class="w-4 h-4 rounded text-purpura-600 focus:ring-purpura-500 border-gray-300">
                        <div class="text-xs">
                            <span class="font-extrabold block flex items-center gap-1.5">
                                <i class="ph-fill {{ $validarComIa ? 'ph-sparkle text-purpura-600' : 'ph-shield-check text-amber-600' }}"></i>
                                {{ $validarComIa ? 'Validação por IA: ATIVADA' : 'Validação por IA: DESATIVADA (Aprovação Direta)' }}
                            </span>
                            <span class="text-[10px] opacity-80 block">
                                {{ $validarComIa ? 'O documento será analisado pela IA antes de aprovar.' : 'O documento enviado pelo participante será aprovado automaticamente.' }}
                            </span>
                        </div>
                    </label>
                </div>

                @if(count($this->documentosExigidos) > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($this->documentosExigidos as $doc)
                            @php
                                $infoDoc = $arquivosEnviados[$doc->id] ?? ['status' => 'pendente'];
                                $statusDoc = $infoDoc['status'] ?? 'pendente';
                            @endphp
                            <div class="p-5 rounded-xl border bg-white shadow-2xs flex flex-col justify-between gap-4 {{ in_array($statusDoc, ['valido_ia', 'aprovado_manual']) ? 'border-emerald-200 bg-emerald-50/10' : 'border-gray-200' }}">
                                <div>
                                    <div class="flex items-start justify-between gap-2">
                                        <h4 class="text-sm font-bold text-gray-900 flex items-center gap-2 flex-wrap">
                                            <span>{{ $doc->nome }}</span>
                                            @if($doc->is_obrigatorio)
                                                <span class="px-2 py-0.5 text-[9px] font-bold uppercase bg-red-50 text-red-600 border border-red-200 rounded">Obrigatório</span>
                                            @endif
                                        </h4>

                                        @if(in_array($statusDoc, ['valido_ia', 'aprovado_manual']))
                                            <span class="px-2.5 py-1 text-[10px] font-extrabold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg flex items-center gap-1">
                                                <i class="ph-bold ph-check"></i> Aprovado
                                            </span>
                                        @elseif($statusDoc === 'invalido_ia' || $statusDoc === 'reprovado_manual')
                                            <span class="px-2.5 py-1 text-[10px] font-extrabold uppercase bg-red-50 text-red-700 border border-red-200 rounded-lg flex items-center gap-1">
                                                <i class="ph-bold ph-x"></i> Rejeitado
                                            </span>
                                        @elseif($statusDoc === 'analise_manual')
                                            <span class="px-2.5 py-1 text-[10px] font-extrabold uppercase bg-amber-50 text-amber-700 border border-amber-200 rounded-lg flex items-center gap-1">
                                                <i class="ph-bold ph-clock"></i> Em Análise
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 text-[10px] font-bold uppercase bg-gray-100 text-gray-600 rounded-lg">
                                                Pendente
                                            </span>
                                        @endif
                                    </div>

                                    <p class="text-xs text-gray-500 mt-1">{{ $doc->descricao }}</p>

                                    {{-- BADGE INDICANDO QUE FOI ENVIADO POR UM PARTICIPANTE --}}
                                    @if(!empty($infoDoc['enviado_por_participante']))
                                        <div class="mt-3 p-2.5 rounded-lg bg-indigo-50/70 border border-indigo-200 flex items-center justify-between gap-2 text-[11px] text-indigo-800">
                                            <span class="font-bold flex items-center gap-1.5">
                                                <i class="ph-fill ph-user-switch text-indigo-600 text-sm"></i>
                                                Enviado por Participante: {{ $infoDoc['participante_nome'] ?? 'Equipe' }}
                                            </span>
                                            <span class="text-[10px] font-mono text-indigo-600">
                                                {{ !empty($infoDoc['validacao_ia_ativa']) ? 'IA Ativa' : 'IA Desativada' }}
                                                {{ !empty($infoDoc['data_envio_participante']) ? '• ' . $infoDoc['data_envio_participante'] : '' }}
                                            </span>
                                        </div>
                                    @endif

                                    @if(!empty($infoDoc['motivo_rejeicao']))
                                        <div class="mt-2 p-2.5 rounded-lg bg-red-50 border border-red-200 text-xs text-red-700">
                                            <b>Motivo IA:</b> {{ $infoDoc['motivo_rejeicao'] }}
                                        </div>
                                    @endif
                                </div>

                                <div class="pt-3 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        @if(!empty($infoDoc['arquivo_caminho']))
                                            <a href="{{ asset('storage/' . $infoDoc['arquivo_caminho']) }}" target="_blank" class="px-3 py-1.5 text-xs font-bold text-purpura-700 bg-purpura-50 hover:bg-purpura-100 border border-purpura-200 rounded-lg transition inline-flex items-center gap-1">
                                                <i class="ph-bold ph-eye"></i> Ver Arquivo
                                            </a>
                                        @endif

                                        @if(!empty($infoDoc['id']) && !in_array($statusDoc, ['valido_ia', 'aprovado_manual']))
                                            <button type="button" wire:click="aprovarDocumentoManualmente({{ $doc->id }})" class="px-3 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-lg transition inline-flex items-center gap-1">
                                                <i class="ph-bold ph-check"></i> Forçar Aprovação
                                            </button>
                                        @endif
                                    </div>

                                    @if($podeEditarAssistido)
                                        <label class="px-3.5 py-2 bg-gray-900 hover:bg-black text-white text-xs font-bold rounded-lg cursor-pointer transition inline-flex items-center gap-1.5 shadow-2xs">
                                            <i class="ph-bold ph-upload-simple"></i>
                                            <span wire:loading.remove wire:target="uploads.{{ $doc->id }}">
                                                {{ !empty($infoDoc['id']) ? 'Substituir Arquivo' : 'Enviar pelo Candidato' }}
                                            </span>
                                            <span wire:loading wire:target="uploads.{{ $doc->id }}">Enviando...</span>
                                            <input type="file" wire:model="uploads.{{ $doc->id }}" accept=".jpg,.jpeg,.png,.webp,.pdf" class="hidden">
                                        </label>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-12 text-center text-gray-400">
                        <i class="ph-duotone ph-folder-open text-4xl mb-2 block mx-auto"></i>
                        <p class="text-sm font-bold text-gray-600">Nenhum documento exigido cadastrado para o ciclo desta inscrição.</p>
                    </div>
                @endif
            </div>
        </div>

    {{-- ===================================================================== --}}
    {{-- ABA 3: FORMULÁRIOS E PREENCHIMENTO ASSISTIDO (EMBED EM TABELA)        --}}
    {{-- ===================================================================== --}}
    @elseif($abaAtiva === 'formularios')
        <div class="mt-6 space-y-6 animate-fade-in-down w-full block">
            <div class="card !p-6 md:!p-8 space-y-6 w-full block">
                <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 border-b border-gray-100 pb-5 w-full">
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base font-extrabold text-gray-900 flex items-center gap-2">
                            <i class="ph-fill ph-browsers text-purpura-600 text-xl"></i> Formulários e Preenchimento Assistido
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">
                            Selecione o formulário na lista abaixo para iniciar um preenchimento em nome do candidato ou consultar o histórico de edições passadas.
                        </p>
                    </div>
                    <div class="relative w-full sm:w-72 shrink-0">
                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input wire:model.live.debounce.300ms="searchFormulario" type="text" placeholder="Buscar formulário..." class="pl-9 w-full rounded-lg border-gray-300 text-xs shadow-sm focus:border-purpura-500 focus:ring-purpura-500 h-10">
                    </div>
                </div>

                {{-- Tabela de Formulários Disponíveis --}}
                <div class="overflow-x-auto w-full border border-gray-200 rounded-xl shadow-sm">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-gray-50 border-b border-gray-200 text-gray-500 font-bold text-[10px] uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3">Formulário / Nome</th>
                                <th class="px-4 py-3 text-center">Preenchimentos</th>
                                <th class="px-4 py-3">Última Edição</th>
                                <th class="px-4 py-3 text-right">Ações de Preenchimento</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($this->formulariosPaginados as $form)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3">
                                        <span class="font-bold text-gray-900 block truncate max-w-sm">{{ $form['titulo'] }}</span>
                                        <span class="block text-[10px] font-bold text-gray-500 mt-0.5 tracking-wider">{{ $form['tipo'] }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($form['total_preenchimentos'] > 0)
                                            <span class="px-2 py-0.5 rounded-full bg-purpura-100 text-purpura-700 font-bold text-[10px]">
                                                {{ $form['total_preenchimentos'] }}x
                                            </span>
                                        @else
                                            <span class="text-gray-400 text-xs font-medium">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($form['ultimo_preenchimento'])
                                            <div class="text-xs font-bold text-gray-800 flex items-center gap-1.5 truncate max-w-[200px]">
                                                <i class="ph-fill ph-user-switch text-indigo-500"></i> {{ $form['ultimo_preenchimento']['participante_nome'] ?? 'Participante' }}
                                            </div>
                                            <div class="text-[10px] text-gray-500 font-mono mt-0.5">{{ $form['ultimo_preenchimento']['data'] ?? '-' }}</div>
                                        @else
                                            <span class="text-gray-400 text-xs italic">Nunca preenchido</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="inline-flex items-center gap-2 justify-end">
                                            @if($form['total_preenchimentos'] > 0)
                                                <button type="button" wire:click="verHistoricoFormulario('{{ $form['ref'] }}', '{{ $form['id'] }}')" class="px-3 py-1.5 text-[11px] font-bold text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition inline-flex items-center gap-1 shadow-sm" title="Ver histórico completo de edições">
                                                    <i class="ph-bold ph-clock-counter-clockwise"></i> Histórico
                                                </button>
                                            @endif
                                            <button type="button" wire:click="abrirFormularioEmbedTable('{{ $form['ref'] }}', '{{ $form['id'] }}')" class="px-3.5 py-1.5 text-[11px] font-bold text-white bg-purpura-600 rounded-lg hover:bg-purpura-700 transition shadow-sm inline-flex items-center gap-1.5">
                                                <i class="ph-bold ph-play"></i> Iniciar Preenchimento
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-12 text-center text-gray-500">
                                        <i class="ph-duotone ph-browsers text-4xl mb-2 block mx-auto text-gray-300"></i>
                                        <p class="text-sm font-bold text-gray-700">Nenhum formulário localizado.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($this->formulariosPaginados->hasPages())
                    <div class="mt-4">
                        {{ $this->formulariosPaginados->links() }}
                    </div>
                @endif
            </div>
        </div>

    {{-- ===================================================================== --}}
    {{-- ABA 4: MOVIMENTAÇÃO (COM BADGE DE PARTICIPANTE)                       --}}
    {{-- ===================================================================== --}}
    @elseif($abaAtiva === 'movimentacao')
        <div class="mt-6 card !p-6 md:!p-8 animate-fade-in-down w-full block">
            <h3 class="t-label-12-semibold tracking-wider text-gray-500 uppercase flex items-center gap-2 mb-8">
                <i class="ph-fill ph-clock-counter-clockwise text-lg text-purpura-500"></i> Histórico de Movimentação do CRM
            </h3>
            
            @if(count($this->logsMovimentacao) > 0)
                <div class="relative border-l-2 border-gray-200 dark:border-gray-700 ml-3 mt-4 space-y-8 pb-4">
                    @foreach($this->logsMovimentacao as $mov)
                        <div class="relative pl-6">
                            <div class="absolute w-4 h-4 rounded-full {{ $mov['tipo'] === 'criacao' ? 'bg-emerald-500' : ($mov['tipo'] === 'participante' ? 'bg-indigo-600' : 'bg-purpura-500') }} border-4 border-white dark:border-gray-900 left-[-9px] top-1 shadow-sm"></div>
                            
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border {{ !empty($mov['is_participante']) ? 'border-indigo-200' : 'border-gray-100 dark:border-gray-700' }} flex flex-col gap-1.5 w-full">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <i class="ph-fill {{ $mov['tipo'] === 'criacao' ? 'ph-rocket text-emerald-500' : 'ph-user text-purpura-500' }}"></i>
                                    <span class="font-bold text-gray-900 dark:text-white text-sm">{{ $mov['usuario'] }}</span>
                                    @if(!empty($mov['is_participante']))
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 flex items-center gap-1">
                                            <i class="ph-fill ph-user-switch"></i> Preenchido por Participante
                                        </span>
                                    @endif
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

    {{-- ===================================================================== --}}
    {{-- ABA 5: COMUNICAÇÃO                                                    --}}
    {{-- ===================================================================== --}}
    @elseif($abaAtiva === 'comunicacao')
        <div class="mt-6 space-y-6 animate-fade-in-down w-full block">
            <div class="card !p-0 overflow-hidden w-full block">
                <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="t-label-12-semibold tracking-wider text-gray-500 uppercase flex items-center gap-2">
                        <i class="ph-fill ph-chat-circle-text text-lg text-emerald-500"></i> Contatos Registrados (WhatsApp / Telefone / E-mail)
                    </h3>
                    <span class="text-xs font-bold text-gray-400">{{ count($this->contatosRegistrados) }} registro(s)</span>
                </div>

                @if(count($this->contatosRegistrados) > 0)
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($this->contatosRegistrados as $contato)
                            <div class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-gray-50/60 transition">
                                <div class="flex items-start gap-3.5">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 border
                                        {{ $contato->canal === 'whatsapp' ? 'bg-emerald-50 text-emerald-600 border-emerald-200' : ($contato->canal === 'email' ? 'bg-blue-50 text-blue-600 border-blue-200' : 'bg-amber-50 text-amber-600 border-amber-200') }}">
                                        <i class="ph-fill {{ $contato->canal === 'whatsapp' ? 'ph-whatsapp-logo' : ($contato->canal === 'email' ? 'ph-envelope-simple' : 'ph-phone-call') }} text-xl"></i>
                                    </div>
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm font-bold text-gray-900 uppercase">{{ $contato->canal }}</span>
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full {{ $contato->houve_resposta ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600' }}">
                                                {{ $contato->houve_resposta ? 'Houve Resposta' : 'Aguardando / Sem Resposta' }}
                                            </span>
                                            <span class="text-xs text-gray-400">• Registrado por {{ $contato->user_nome ?? 'Sistema' }}</span>
                                        </div>
                                        <p class="text-sm text-gray-700 mt-1 whitespace-pre-line">{{ $contato->conteudo ?: 'Contato registrado sem descrição detalhada.' }}</p>
                                    </div>
                                </div>
                                <div class="text-xs font-bold text-gray-500 shrink-0">
                                    {{ $contato->data_contato ? $contato->data_contato->format('d/m/Y \à\s H:i') : $contato->created_at->format('d/m/Y \à\s H:i') }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-10 text-center text-gray-400 text-sm">
                        Nenhum contato manual registrado para este candidato.
                    </div>
                @endif
            </div>

            <div class="card !p-0 overflow-hidden w-full block">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between w-full">
                    <h3 class="t-label-12-semibold tracking-wider text-gray-500 uppercase flex items-center gap-2">
                        <i class="ph-fill ph-envelope-simple text-lg text-purpura-500"></i> Histórico de E-mails Disparados
                    </h3>
                    <span class="text-xs font-bold text-gray-400">{{ count($this->logsComunicacao) }} disparo(s)</span>
                </div>
                
                @if(count($this->logsComunicacao) > 0)
                    <div class="overflow-x-auto w-full">
                        <table class="w-full min-w-full text-left whitespace-nowrap">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-6 py-3 font-semibold text-xs text-gray-500 uppercase">Assunto / Template</th>
                                    <th class="px-6 py-3 font-semibold text-xs text-gray-500 uppercase">Origem</th>
                                    <th class="px-6 py-3 font-semibold text-xs text-gray-500 uppercase">Gatilho</th>
                                    <th class="px-6 py-3 font-semibold text-xs text-gray-500 uppercase">Engajamento</th>
                                    <th class="px-6 py-3 font-semibold text-xs text-gray-500 uppercase">Data de Envio</th>
                                    <th class="px-6 py-3 font-semibold text-xs text-gray-500 uppercase text-center">Status</th>
                                    <th class="px-6 py-3 font-semibold text-xs text-gray-500 uppercase text-center">Ação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($this->logsComunicacao as $log)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-sm text-gray-900">{{ \Illuminate\Support\Str::limit($log->assunto, 45) }}</div>
                                            <div class="text-xs text-gray-500 mt-0.5">Template: {{ $log->template_nome ?? 'N/A' }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2.5 py-1 text-[10px] font-bold text-purpura-700 bg-purpura-100 border border-purpura-200 rounded-full uppercase">
                                                {{ $log->origem ?? 'Automação' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            {{ $log->gatilho ? str_replace('inscricao.status.', 'Status: ', $log->gatilho) : '-' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-1.5">
                                                @if(($log->aberturas_count ?? 0) > 0)
                                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        Aberto ({{ $log->aberturas_count }}x)
                                                    </span>
                                                @else
                                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-gray-100 text-gray-500 border border-gray-200">
                                                        Não aberto
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            {{ $log->data_envio ? \Carbon\Carbon::parse($log->data_envio)->format('d/m/Y H:i') : $log->created_at->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-2.5 py-1 text-[10px] font-bold text-green-700 bg-green-100 border border-green-200 rounded-full uppercase">
                                                {{ $log->status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <button wire:click="verConteudoEmail({{ $log->id }})" class="p-2 bg-gray-100 hover:bg-purpura-100 hover:text-purpura-600 text-gray-600 rounded-lg transition-colors shadow-sm">
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
                        <p class="font-medium text-sm">Nenhum e-mail disparado para este candidato.</p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- MODAL DE SPAM --}}
    @if($modalAntiSpamAberto)
        <div class="fixed inset-0 z-[120] flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="card !w-full !max-w-md !p-0 shadow-2xl">
                <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-pitaya-50 w-full">
                    <h3 class="text-lg font-bold text-pitaya-700 flex items-center gap-2">
                        <i class="ph-fill ph-warning-circle text-2xl"></i> Alerta Anti-Spam
                    </h3>
                </div>
                <div class="p-6 w-full">
                    <p class="text-sm text-gray-700 font-medium text-center">
                        Este candidato <strong>já recebeu</strong> o e-mail automático configurado para esta etapa anteriormente.
                    </p>
                </div>
                <div class="p-5 border-t border-gray-100 bg-gray-50 flex justify-center gap-3 w-full">
                    <button wire:click="cancelarAntiSpam" class="btn btn--secondary btn--medium">Cancelar</button>
                    <button wire:click="executarMudancaStatusFinal" class="btn btn--primary btn--medium !bg-pitaya-600 !text-white border-none">
                        Sim, Reenviar E-mail
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL DE VISUALIZAÇÃO DE E-MAIL --}}
    @if($emailVisualizacao)
        <div class="fixed inset-0 z-[130] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <i class="ph-fill ph-envelope-open text-purpura-600"></i> {{ $emailVisualizacao->assunto }}
                    </h3>
                    <button wire:click="fecharModalEmail" class="text-gray-400 hover:text-red-500 transition"><i class="ph-bold ph-x text-xl"></i></button>
                </div>
                <div class="p-6 overflow-y-auto bg-gray-50 flex-1">
                    <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm min-h-[300px]">
                        {!! $emailVisualizacao->corpo !!}
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL EM TELA CHEIA PARA PREENCHIMENTO DE FORMULÁRIO (EMBED) --}}
    @if($urlEmbedAtiva)
        <div class="fixed inset-0 z-[200] flex flex-col bg-white dark:bg-gray-900 animate-fade-in-up">
            <!-- Cabeçalho do Modal -->
            <div class="px-6 py-4 bg-indigo-600 dark:bg-indigo-700 text-white flex justify-between items-center shrink-0 shadow-md z-10">
                <div class="flex-1 min-w-0">
                    <h3 class="text-base font-extrabold flex items-center gap-2 truncate">
                        <i class="ph-fill ph-user-switch text-xl"></i>
                        Atendimento Assistido Ativo: {{ $tituloEmbedAtivo }}
                    </h3>
                    <p class="text-[11px] text-indigo-200 mt-0.5 font-medium">
                        Preenchendo em nome de <b class="text-white">{{ $inscricao->nome }}</b> (Participante Logado: {{ auth()->user()->name }})
                    </p>
                </div>
                
                <div class="flex items-center gap-4 shrink-0 pl-4">
                    <a href="{{ $urlEmbedAtiva }}" target="_blank" class="hidden sm:flex text-xs text-indigo-100 hover:text-white transition items-center gap-1.5 font-bold bg-indigo-800/50 hover:bg-indigo-800 px-3 py-1.5 rounded-lg border border-indigo-500/30">
                        Abrir em nova guia <i class="ph-bold ph-arrow-square-out text-sm"></i>
                    </a>
                    <button type="button" wire:click="fecharFormularioEmbed" class="flex items-center gap-1.5 px-4 py-2 bg-red-500 hover:bg-red-600 text-white text-xs font-bold rounded-lg transition shadow-sm border border-red-400/50">
                        <i class="ph-bold ph-x text-sm"></i> Fechar e Sincronizar Ficha
                    </button>
                </div>
            </div>
            
            <!-- Corpo do Modal (Iframe) -->
            <div class="flex-1 w-full bg-gray-50 dark:bg-gray-950 relative">
                <iframe src="{{ $urlEmbedAtiva }}" class="absolute inset-0 w-full h-full border-0"></iframe>
            </div>
        </div>
    @endif

    {{-- MODAL DE HISTÓRICO DE PREENCHIMENTOS DO FORMULÁRIO --}}
    @if($modalHistoricoFormularioAberto)
        <div class="fixed inset-0 z-[150] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-xl w-full overflow-hidden flex flex-col my-8 border border-gray-200">
                <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="text-base font-extrabold text-gray-900 flex items-center gap-2">
                        <i class="ph-fill ph-clock-counter-clockwise text-purpura-600 text-xl"></i> 
                        Histórico de Acessos ao Formulário
                    </h3>
                    <button wire:click="$set('modalHistoricoFormularioAberto', false)" class="text-gray-400 hover:text-red-500 transition"><i class="ph-bold ph-x text-xl"></i></button>
                </div>
                
                <div class="p-4 border-b border-gray-100 bg-white">
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Formulário:</p>
                    <p class="text-sm font-bold text-gray-900">{{ $tituloHistoricoFormulario }}</p>
                </div>

                <div class="p-0 overflow-y-auto max-h-[60vh] custom-scrollbar">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-gray-50 border-b border-gray-100 text-[10px] font-bold uppercase text-gray-500 sticky top-0">
                            <tr>
                                <th class="px-4 py-3">Data e Hora do Atendimento</th>
                                <th class="px-4 py-3">Participante (Autor)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($historicoFormularioSelecionado as $hist)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-xs font-mono font-bold text-gray-600">{{ $hist['data'] ?? '-' }}</td>
                                    <td class="px-4 py-3 text-xs font-bold text-gray-900 flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-indigo-50 border border-indigo-200 text-indigo-600 flex items-center justify-center shrink-0">
                                            <i class="ph-fill ph-user-switch text-xs"></i>
                                        </div>
                                        {{ $hist['participante_nome'] ?? 'Desconhecido' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="p-6 text-center text-gray-500">
                                        Nenhum registro de preenchimento assistido para este formulário.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <button wire:click="$set('modalHistoricoFormularioAberto', false)" class="btn btn--secondary btn--medium">Fechar Histórico</button>
                </div>
            </div>
        </div>
    @endif
</div>