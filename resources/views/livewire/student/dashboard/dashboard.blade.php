<div>
    @if($student->matriculado)
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Meu Painel</h1>
        <p class="mt-2 text-slate-600 dark:text-slate-400">Bem-vindo de volta! Aqui está o seu progresso.</p>

        @if(count($formulariosPendentes) > 0)
            <div class="mt-8 mb-6">
                <h2 class="text-xl font-bold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
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
                                    <a href="{{ route('formulario-aprendizagem.responder', ['slug' => $form->slug, 'aluno_id' => $student->id]) }}" class="px-5 py-2.5 bg-ponkan-500 hover:bg-ponkan-600 text-white text-sm font-bold rounded-lg shadow-sm transition flex items-center gap-2">
                                        Responder Agora <i class="ph-bold ph-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        @else
            <div class="mt-8 p-12 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 text-center">
                <div class="text-5xl mb-4 text-green-500"><i class="ph-fill ph-check-circle"></i></div>
                <h3 class="text-lg font-medium text-slate-900 dark:text-white">Tudo em dia!</h3>
                <p class="mt-1 text-slate-500 dark:text-slate-400">Você não possui formulários de aprendizagem pendentes para responder no momento.</p>
            </div>
        @endif

    @else
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Acompanhamento de Inscrição</h1>
        <p class="mt-2 text-slate-600 dark:text-slate-400">Abaixo estão os dados da sua inscrição e o status atual do processo seletivo.</p>

        @if($inscricao)
            <div class="mt-6 bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700">
                
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 border-b border-gray-100 dark:border-gray-700 pb-4 gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white">Status da Inscrição</h3>
                        <p class="text-xs text-slate-500 mt-1">Sua inscrição foi registrada em {{ $inscricao->created_at->format('d/m/Y') }}</p>
                    </div>
                    
                    @php $corHex = $inscricao->statusInscricao->cor ?? '#6B7280'; @endphp
                    <span class="px-4 py-1.5 text-sm font-bold rounded-full border shadow-sm uppercase tracking-wider" style="background-color: {{ $corHex }}15; color: {{ $corHex }}; border-color: {{ $corHex }}40;">
                        {{ $inscricao->statusInscricao->nome ?? 'Pendente' }}
                    </span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8">
                    
                    <div class="lg:col-span-1">
                        <h4 class="text-xs font-bold text-purpura-600 uppercase tracking-widest mb-3 flex items-center gap-2"><i class="ph-fill ph-graduation-cap text-lg"></i> Interesse Acadêmico</h4>
                        <button wire:click="abrirModalSolicitacao" class="text-[10px] bg-purpura-100 text-purpura-700 px-2 py-1 rounded font-bold hover:bg-purpura-200 transition shadow-sm">
                            Solicitar Alteração
                        </button>
                        <div class="flex flex-col gap-3 bg-slate-50 dark:bg-gray-900/50 p-5 rounded-xl border border-slate-100 dark:border-gray-700 h-full">
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 mb-0.5">Curso Escolhido</span>
                                <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $inscricao->curso->nome ?? '-' }}</span>
                            </div>
                            <div class="w-8 h-px bg-gray-200 dark:bg-gray-700 my-1"></div>
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 mb-0.5">Unidade / Sede</span>
                                <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $inscricao->unidade->nome ?? '-' }}</span>
                            </div>
                            <div class="w-8 h-px bg-gray-200 dark:bg-gray-700 my-1"></div>
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 mb-0.5">Turno</span>
                                <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $inscricao->turno->nome ?? '-' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-2">
                        <h4 class="text-xs font-bold text-purpura-600 uppercase tracking-widest mb-3 flex items-center gap-2"><i class="ph-fill ph-list-dashes text-lg"></i> Dados Informados</h4>
                        <div class="bg-slate-50 dark:bg-gray-900/50 p-4 rounded-xl border border-slate-100 dark:border-gray-700">
                            @if($inscricao->dados_dinamicos)
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    @foreach($inscricao->dados_dinamicos as $chave => $valor)
                                        @continue(str_contains(strtolower($chave), 'form_config'))
                                        
                                        @php 
                                            $valorFormatado = is_array($valor) ? implode(', ', $valor) : $valor; 
                                            $labelOriginal = str_replace('_', ' ', $chave);
                                        @endphp
                                        
                                        <div class="bg-white dark:bg-gray-800 p-3 rounded-lg border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col justify-center min-h-[60px]">
                                            <span class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5 truncate" title="{{ $labelOriginal }}">{{ $labelOriginal }}</span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $valorFormatado ?: '-' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <p class="text-sm text-slate-500 italic">Nenhum dado complementar registrado no formulário.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
            </div>
        @else
            <div class="mt-6 p-8 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700 text-center">
                <i class="ph ph-warning-circle text-4xl text-slate-400 mb-3"></i>
                <p class="text-slate-500 dark:text-slate-400 font-medium">Nenhuma inscrição vinculada a este perfil foi encontrada.</p>
            </div>
        @endif
    @endif

    @if(isset($minhasSolicitacoes) && count($minhasSolicitacoes) > 0)
    <div class="mt-8">
        <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-4">Minhas Solicitações Registradas</h3>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700 overflow-hidden">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3 font-bold text-[10px] text-gray-500 uppercase">Data</th>
                        <th class="px-4 py-3 font-bold text-[10px] text-gray-500 uppercase">Tipo</th>
                        <th class="px-4 py-3 font-bold text-[10px] text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 font-bold text-[10px] text-gray-500 uppercase">Resposta do Polo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($minhasSolicitacoes as $solic)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $solic->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $solic->tema === 'alteracao_academica' ? 'Troca de Curso/Turno' : str_replace('_', ' ', $solic->tema) }}</td>
                        <td class="px-4 py-3">
                            @if($solic->status === 'aprovada')
                                <span class="px-2.5 py-1 bg-green-100 text-green-700 text-[10px] font-bold uppercase rounded-md">Aprovada</span>
                            @elseif($solic->status === 'rejeitada')
                                <span class="px-2.5 py-1 bg-red-100 text-red-700 text-[10px] font-bold uppercase rounded-md">Reprovada</span>
                            @else
                                <span class="px-2.5 py-1 bg-yellow-100 text-yellow-700 text-[10px] font-bold uppercase rounded-md">Em Análise</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-400 truncate max-w-xs" title="{{ $solic->resposta_admin }}">
                            {{ $solic->resposta_admin ?: '-' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- MODAL DE SOLICITAÇÃO ACADÊMICA -->
    @if($modalSolicitacaoAberto)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col">
            <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="ph-fill ph-swap text-purpura-600"></i> Solicitar Alteração
                </h3>
                <button wire:click="$set('modalSolicitacaoAberto', false)" class="text-gray-400 hover:text-gray-600"><i class="ph-bold ph-x"></i></button>
            </div>
            <form wire:submit.prevent="salvarSolicitacao" class="p-6 space-y-4">
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Nova Unidade</label>
                    <select wire:model="novaUnidadeId" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                        <option value="">Selecione...</option>
                        @foreach($unidadesDb as $u) <option value="{{ $u->id }}">{{ $u->nome }}</option> @endforeach
                    </select>
                    @error('novaUnidadeId') <span class="text-red-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Novo Curso</label>
                    <select wire:model="novoCursoId" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                        <option value="">Selecione...</option>
                        @foreach($cursosDb as $c) <option value="{{ $c->id }}">{{ $c->nome }}</option> @endforeach
                    </select>
                    @error('novoCursoId') <span class="text-red-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Novo Turno</label>
                    <select wire:model="novoTurnoId" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                        <option value="">Selecione...</option>
                        @foreach($turnosDb as $t) <option value="{{ $t->id }}">{{ $t->nome }}</option> @endforeach
                    </select>
                    @error('novoTurnoId') <span class="text-red-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Justificativa</label>
                    <textarea wire:model="motivoSolicitacao" rows="3" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg"></textarea>
                    @error('motivoSolicitacao') <span class="text-red-500 text-[10px] font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" wire:click="$set('modalSolicitacaoAberto', false)" class="px-4 py-2 border rounded-lg text-sm font-bold text-gray-600">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-purpura-600 hover:bg-purpura-700 text-white rounded-lg text-sm font-bold">Enviar</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>