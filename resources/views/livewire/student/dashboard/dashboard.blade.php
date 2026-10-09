<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Meu Painel</h1>
        <p class="mt-2 text-slate-600 dark:text-slate-400">Bem-vindo de volta, {{ explode(' ', $student->name)[0] }}! Acompanhe o seu progresso abaixo.</p>
    </div>

    @if($documentosPendentes)
        <div class="mb-8 bg-gradient-to-r from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 border border-orange-200 dark:border-orange-800/50 p-6 rounded-2xl shadow-sm flex flex-col sm:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/50 text-orange-600 dark:text-orange-400 rounded-full flex items-center justify-center shrink-0">
                    <i class="ph-fill ph-warning-circle text-2xl"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-orange-900 dark:text-orange-300">Ação Necessária: Envio de Documentos</h2>
                    <p class="text-sm text-orange-700 dark:text-orange-400 mt-0.5">A sua inscrição requer o envio de documentação para prosseguir. Envie os arquivos de forma segura pelo portal.</p>
                </div>
            </div>
            <a href="{{ route('student.student.documentos') }}" wire:navigate class="shrink-0 px-6 py-3 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-xl shadow-sm transition flex items-center gap-2">
                <i class="ph-bold ph-folder-open"></i> Enviar Documentação
            </a>
        </div>
    @endif

    @if(count($formulariosPendentes) > 0)
        <div class="mb-8">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                <i class="ph-fill ph-warning-circle text-orange-500"></i> Avaliações Pendentes
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($formulariosPendentes as $av)
                    @foreach($av->faseAtual->formularios as $form)
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-orange-200 dark:border-orange-800/50 flex flex-col justify-between hover:shadow-md transition">
                            <div>
                                <span class="inline-block px-2.5 py-1 bg-orange-100 text-orange-700 text-[10px] font-bold uppercase tracking-wider rounded-md mb-3">
                                    {{ $av->ciclo->nome }}
                                </span>
                                <h4 class="text-lg font-bold text-slate-900 dark:text-white">{{ $form->titulo }}</h4>
                                <p class="text-sm text-slate-500 mt-1"><i class="ph-fill ph-clock"></i> Prazo: {{ $av->data_prazo ? \Carbon\Carbon::parse($av->data_prazo)->format('d/m/Y') : 'Não definido' }}</p>
                            </div>
                            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                                <a href="{{ route('student.formulario-aprendizagem.responder', ['slug' => $form->slug, 'aluno_id' => $student->id]) }}" class="px-5 py-2.5 bg-ponkan-500 hover:bg-ponkan-600 text-white text-sm font-bold rounded-lg shadow-sm transition flex items-center gap-2">
                                    Responder Agora <i class="ph-bold ph-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- COLUNA ESQUERDA: Resumos (Inscrição Atual e Solicitação) -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Card de Inscrição Atual -->
            @if($inscricao)
                <div class="bg-white dark:bg-gray-800 p-6 sm:p-8 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700">
                    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-slate-800 dark:text-white flex items-center gap-2">
                                <i class="ph-fill ph-star text-purpura-500 text-xl"></i> Inscrição em Andamento
                            </h3>
                            <p class="text-xs text-slate-500 font-medium mt-1">Registrada em {{ $inscricao->created_at->format('d/m/Y') }}</p>
                        </div>
                        @php $corHex = $inscricao->statusInscricao->cor ?? '#6B7280'; @endphp
                        <span class="px-3.5 py-1.5 text-[11px] font-bold rounded-full border uppercase tracking-wider w-max shadow-sm" style="background-color: {{ $corHex }}15; color: {{ $corHex }}; border-color: {{ $corHex }}40;">
                            
                            @if($inscricao->statusInscricao->visivel_estudante)
                                {{ $inscricao->statusInscricao->titulo_amigavel ?? 'Pendente' }}
                            @else
                                {{ 'Aguardando atualização...' }}
                            @endif
                            
                        </span>
                    </div>
                    
                    <div class="bg-slate-50 dark:bg-gray-900/50 p-5 rounded-xl border border-slate-100 dark:border-gray-700 grid grid-cols-1 sm:grid-cols-3 gap-5">
                        <div>
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-gray-400 dark:text-gray-500 mb-1">Curso Escolhido</span>
                            <span class="text-sm font-bold text-gray-800 dark:text-gray-200 leading-tight">{{ $inscricao->curso->nome ?? '-' }}</span>
                        </div>
                        <div class="sm:border-l sm:border-gray-200 sm:dark:border-gray-700 sm:pl-5">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-gray-400 dark:text-gray-500 mb-1">Unidade / Sede</span>
                            <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $inscricao->unidade->nome ?? '-' }}</span>
                        </div>
                        <div class="sm:border-l sm:border-gray-200 sm:dark:border-gray-700 sm:pl-5">
                            <span class="block text-[10px] uppercase tracking-wider font-bold text-gray-400 dark:text-gray-500 mb-1">Turno</span>
                            <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $inscricao->turno->nome ?? '-' }}</span>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex flex-wrap gap-3">
                        <button wire:click="abrirDetalhesInscricao({{ $inscricao->id }})" class="px-5 py-2.5 bg-purpura-50 hover:bg-purpura-100 text-purpura-700 border border-purpura-100 rounded-lg text-xs font-bold transition shadow-sm flex items-center gap-2">
                            <i class="ph-bold ph-identification-card text-lg"></i> Ficha Completa
                        </button>
                        <button wire:click="abrirModalSolicitacao" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg text-xs font-bold transition shadow-sm flex items-center gap-2">
                            <i class="ph-bold ph-swap text-lg"></i> Solicitar Alteração
                        </button>
                    </div>
                </div>
            @else
                <div class="p-8 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700 text-center">
                    <i class="ph-fill ph-warning-circle text-4xl text-slate-300 mb-3 block"></i>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">Sem inscrições ativas</h3>
                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Não foi encontrada nenhuma inscrição em andamento para o seu perfil.</p>
                </div>
            @endif

            <!-- Card Última Solicitação de Helpdesk -->
            @if(isset($minhasSolicitacoes) && $minhasSolicitacoes->count() > 0)
                @php $ultimaSolicitacao = $minhasSolicitacoes->first(); @endphp
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                    <div class="flex items-start gap-4">
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full shrink-0">
                            <i class="ph-fill ph-envelope-open text-2xl"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="text-sm font-bold text-slate-800 dark:text-white">
                                    {{ $ultimaSolicitacao->tema === 'alteracao_academica' ? 'Troca de Unidade/Curso' : str_replace('_', ' ', $ultimaSolicitacao->tema) }}
                                </h3>
                                @if($ultimaSolicitacao->status === 'aprovada')
                                    <span class="px-2 py-0.5 bg-green-100 text-green-700 text-[9px] font-bold uppercase rounded border border-green-200">Aprovado</span>
                                @elseif($ultimaSolicitacao->status === 'rejeitada')
                                    <span class="px-2 py-0.5 bg-red-100 text-red-700 text-[9px] font-bold uppercase rounded border border-red-200">Reprovado</span>
                                @else
                                    <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 text-[9px] font-bold uppercase rounded border border-yellow-200">Em Análise</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 max-w-sm truncate italic">"{{ $ultimaSolicitacao->justificativa }}"</p>
                        </div>
                    </div>
                    <div class="shrink-0 sm:text-right border-t sm:border-t-0 pt-4 sm:pt-0 border-gray-100 dark:border-gray-700">
                        <a href="{{ route('student.solicitacoes') }}" class="text-xs font-bold text-blue-600 hover:text-blue-700 hover:underline flex items-center justify-start sm:justify-end gap-1">
                            Acessar Histórico Completo <i class="ph-bold ph-arrow-right"></i>
                        </a>
                        <span class="text-[10px] font-medium text-gray-400 mt-1 block">Pedido efetuado em {{ $ultimaSolicitacao->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>
            @endif
        </div>

        <!-- COLUNA DIREITA: Timeline do Histórico de Inscrições -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700">
                <h3 class="text-md font-bold text-slate-800 dark:text-white flex items-center gap-2 mb-6 border-b border-gray-100 dark:border-gray-700 pb-4">
                    <i class="ph-fill ph-clock-counter-clockwise text-gray-400 text-lg"></i> Histórico Acadêmico
                </h3>

                @if(isset($historicoInscricoes) && $historicoInscricoes->count() > 0)
                    <div class="space-y-6 pl-2">
                        @foreach($historicoInscricoes as $hist)
                            <div class="relative pl-6 border-l-2 {{ $loop->first ? 'border-purpura-500' : 'border-gray-200 dark:border-gray-700' }}" wire:key="hist-{{ $hist->id }}">
                                
                                @if($loop->first)
                                    <span class="absolute -left-[9px] top-0 w-4 h-4 rounded-full bg-purpura-500 ring-4 ring-white dark:ring-gray-800"></span>
                                @else
                                    <span class="absolute -left-[9px] top-0 w-4 h-4 rounded-full bg-gray-300 dark:bg-gray-600 ring-4 ring-white dark:ring-gray-800"></span>
                                @endif
                                
                                <div class="flex flex-col -mt-1">
                                    <div class="flex flex-col gap-0.5 mb-1.5">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ $hist->created_at->format('d/m/Y') }}</span>
                                        <span class="text-sm font-bold text-gray-900 dark:text-white leading-tight">{{ $hist->curso->nome ?? 'Curso Indefinido' }}</span>
                                    </div>
                                    <span class="text-xs text-gray-600 dark:text-gray-400 mb-2">{{ $hist->unidade->nome ?? '-' }} • {{ $hist->turno->nome ?? '-' }}</span>
                                    
                                    <button wire:click="abrirDetalhesInscricao({{ $hist->id }})" class="text-[11px] font-bold px-3 py-1 bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 hover:bg-gray-200 rounded-md w-max transition">
                                        Visualizar Ficha
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6">
                        <i class="ph-fill ph-file-dashed text-3xl text-gray-300 mb-2 block"></i>
                        <p class="text-xs text-gray-500 font-medium">Nenhum histórico encontrado.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- MODAL DE ALTERAÇÃO ACADÊMICA (HELPDESK) -->
    @if($modalSolicitacaoAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4 overflow-y-auto">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col my-8 border border-gray-200 dark:border-gray-700">
                <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900/50">
                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="ph-fill ph-swap text-purpura-500 text-xl"></i> Solicitar Alteração
                    </h3>
                    <button wire:click="$set('modalSolicitacaoAberto', false)" class="text-gray-400 hover:text-red-500 transition"><i class="ph-bold ph-x text-xl"></i></button>
                </div>
                <form wire:submit.prevent="salvarSolicitacao" class="p-6 space-y-5">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nova Unidade Desejada <span class="text-red-500">*</span></label>
                        <select wire:model="novaUnidadeId" class="w-full px-3 py-2.5 text-sm font-semibold border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm">
                            <option value="">Selecione...</option>
                            @foreach($unidadesDb as $u) <option value="{{ $u->id }}">{{ $u->nome }}</option> @endforeach
                        </select>
                        @error('novaUnidadeId') <span class="text-red-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Novo Curso Desejado <span class="text-red-500">*</span></label>
                        <select wire:model="novoCursoId" class="w-full px-3 py-2.5 text-sm font-semibold border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm">
                            <option value="">Selecione...</option>
                            @foreach($cursosDb as $c) <option value="{{ $c->id }}">{{ $c->nome }}</option> @endforeach
                        </select>
                        @error('novoCursoId') <span class="text-red-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Novo Turno Desejado <span class="text-red-500">*</span></label>
                        <select wire:model="novoTurnoId" class="w-full px-3 py-2.5 text-sm font-semibold border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm">
                            <option value="">Selecione...</option>
                            @foreach($turnosDb as $t) <option value="{{ $t->id }}">{{ $t->nome }}</option> @endforeach
                        </select>
                        @error('novoTurnoId') <span class="text-red-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Justificativa do Pedido <span class="text-red-500">*</span></label>
                        <textarea wire:model="motivoSolicitacao" rows="3" placeholder="Explique brevemente o motivo da troca..." class="w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm"></textarea>
                        @error('motivoSolicitacao') <span class="text-red-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex justify-end gap-3 pt-5 border-t border-gray-100 dark:border-gray-700 mt-2">
                        <button type="button" wire:click="$set('modalSolicitacaoAberto', false)" class="px-5 py-2.5 bg-white border border-gray-300 rounded-xl text-xs font-bold text-gray-700 hover:bg-gray-100 shadow-sm transition">Cancelar</button>
                        <button type="submit" class="px-6 py-2.5 bg-purpura-600 hover:bg-purpura-700 text-white rounded-xl text-xs font-extrabold shadow-md flex items-center gap-2 transition hover:-translate-y-0.5">
                            <i class="ph-bold ph-paper-plane-tilt text-base"></i> Enviar Pedido
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- MODAL: FICHA COMPLETA DA INSCRIÇÃO (DADOS DINÂMICOS E RESPOSTAS) -->
    @if($modalInscricaoAberto && $inscricaoDetalhe)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4 overflow-y-auto">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-3xl overflow-hidden flex flex-col my-8 border border-gray-200 dark:border-gray-700">
                
                <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900/50 rounded-t-2xl">
                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="ph-fill ph-identification-card text-purpura-500 text-2xl"></i> Ficha da Inscrição #{{ $inscricaoDetalhe->id }}
                    </h3>
                    <button wire:click="$set('modalInscricaoAberto', false)" class="text-gray-400 hover:text-red-500 transition"><i class="ph-bold ph-x text-xl"></i></button>
                </div>
                
                <div class="p-6 sm:p-8 overflow-y-auto max-h-[70vh] custom-scrollbar">
                    
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Data de Registo</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $inscricaoDetalhe->created_at->format('d/m/Y às H:i') }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Status Oficial</span>
                            @php $corHex = $inscricaoDetalhe->statusInscricao->cor ?? '#6B7280'; @endphp
                            <span class="px-2.5 py-1 text-[10px] font-bold rounded border shadow-sm uppercase tracking-wider inline-block" style="background-color: {{ $corHex }}15; color: {{ $corHex }}; border-color: {{ $corHex }}40;">
                                @if($inscricao->statusInscricao->visivel_estudante)
                                    {{ $inscricao->statusInscricao->titulo_amigavel ?? 'Pendente' }}
                                @else
                                    {{ 'Aguardando atualização...' }}
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <h4 class="text-xs font-bold text-purpura-600 uppercase tracking-widest mb-3 flex items-center gap-1.5 border-b border-gray-100 dark:border-gray-700 pb-2">
                                <i class="ph-bold ph-student text-base"></i> Interesse Base
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="bg-gray-50 dark:bg-gray-900/50 p-3 rounded-lg border border-gray-100 dark:border-gray-700">
                                    <span class="block text-[10px] uppercase font-bold text-gray-400 mb-0.5">Curso</span>
                                    <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $inscricaoDetalhe->curso->nome ?? '-' }}</span>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-900/50 p-3 rounded-lg border border-gray-100 dark:border-gray-700">
                                    <span class="block text-[10px] uppercase font-bold text-gray-400 mb-0.5">Polo / Sede</span>
                                    <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $inscricaoDetalhe->unidade->nome ?? '-' }}</span>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-900/50 p-3 rounded-lg border border-gray-100 dark:border-gray-700">
                                    <span class="block text-[10px] uppercase font-bold text-gray-400 mb-0.5">Turno</span>
                                    <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $inscricaoDetalhe->turno->nome ?? '-' }}</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-xs font-bold text-purpura-600 uppercase tracking-widest mb-3 flex items-center gap-1.5 border-b border-gray-100 dark:border-gray-700 pb-2">
                                <i class="ph-bold ph-list-dashes text-base"></i> Respostas Adicionais (Formulário)
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @if($inscricaoDetalhe->dados_dinamicos && count(array_filter(array_keys($inscricaoDetalhe->dados_dinamicos), fn($k) => !str_contains(strtolower($k), 'form_config'))) > 0)
                                    @foreach($inscricaoDetalhe->dados_dinamicos as $chave => $valor)
                                        @continue(str_contains(strtolower($chave), 'form_config'))
                                        
                                        @php 
                                            $valorFormatado = is_array($valor) ? implode(', ', $valor) : $valor; 
                                            $labelOriginal = str_replace('_', ' ', $chave);
                                        @endphp
                                        
                                        <div class="bg-white dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700 flex flex-col justify-center shadow-sm">
                                            <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1 truncate" title="{{ $labelOriginal }}">{{ $labelOriginal }}</span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $valorFormatado ?: '-' }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="col-span-2 text-center py-6 bg-gray-50 dark:bg-gray-900/30 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
                                        <i class="ph-fill ph-file-dashed text-2xl text-gray-400 mb-2"></i>
                                        <p class="text-sm text-gray-500 font-medium">Nenhum campo complementar preenchido nesta etapa.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-5 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 flex justify-end gap-3 rounded-b-2xl">
                    <button wire:click="$set('modalInscricaoAberto', false)" class="px-6 py-2.5 bg-white border border-gray-300 rounded-xl text-xs font-bold text-gray-700 hover:bg-gray-100 shadow-sm transition">
                        Fechar Visualização
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>