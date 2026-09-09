<div>
    @if($student->matriculado)
        {{-- TELA 1: ALUNO MATRICULADO --}}
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Meu Painel</h1>
        <p class="mt-2 text-slate-600 dark:text-slate-400">Bem-vindo de volta! Aqui está o seu progresso.</p>

        <div class="mt-8 p-12 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200 dark:border-gray-700 text-center">
            <div class="text-5xl mb-4">📚</div>
            <h3 class="text-lg font-medium text-slate-900 dark:text-white">Em breve</h3>
            <p class="mt-1 text-slate-500 dark:text-slate-400">Seu ambiente de aprendizagem será carregado aqui.</p>
        </div>
    @else
        {{-- TELA 2: ALUNO EM PROCESSO SELETIVO (Acompanhamento) --}}
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
                    
                    {{-- COLUNA ESQUERDA: CURSO E INTERESSE --}}
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

                    {{-- COLUNA DIREITA: DADOS DO FORMULÁRIO --}}
                    <div class="lg:col-span-2">
                        <h4 class="text-xs font-bold text-purpura-600 uppercase tracking-widest mb-3 flex items-center gap-2"><i class="ph-fill ph-list-dashes text-lg"></i> Dados Informados</h4>
                        <div class="bg-slate-50 dark:bg-gray-900/50 p-4 rounded-xl border border-slate-100 dark:border-gray-700">
                            @if($inscricao->dados_dinamicos)
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    @foreach($inscricao->dados_dinamicos as $chave => $valor)
                                        {{-- Remove do visual as chaves do sistema --}}
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