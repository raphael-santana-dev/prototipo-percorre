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
</div>