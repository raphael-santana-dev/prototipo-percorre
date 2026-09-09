<div>
    @if($student->matriculado)
        {{-- TELA 1: ALUNO MATRICULADO (O que já existia) --}}
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <h4 class="text-xs font-bold text-purpura-600 uppercase tracking-widest mb-3 flex items-center gap-2"><i class="ph-fill ph-graduation-cap text-lg"></i> Interesse Acadêmico</h4>
                        <div class="space-y-2 bg-slate-50 dark:bg-gray-900/50 p-4 rounded-lg border border-slate-100 dark:border-gray-700">
                            <p class="text-sm text-slate-800 dark:text-slate-300"><b>Curso:</b> {{ $inscricao->curso->nome ?? '-' }}</p>
                            <p class="text-sm text-slate-800 dark:text-slate-300"><b>Unidade:</b> {{ $inscricao->unidade->nome ?? '-' }}</p>
                            <p class="text-sm text-slate-800 dark:text-slate-300"><b>Turno:</b> {{ $inscricao->turno->nome ?? '-' }}</p>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold text-purpura-600 uppercase tracking-widest mb-3 flex items-center gap-2"><i class="ph-fill ph-list-dashes text-lg"></i> Dados Informados</h4>
                        <div class="space-y-2 bg-slate-50 dark:bg-gray-900/50 p-4 rounded-lg border border-slate-100 dark:border-gray-700">
                            @if($inscricao->dados_dinamicos)
                                @foreach($inscricao->dados_dinamicos as $chave => $valor)
                                    @php $valorFormatado = is_array($valor) ? implode(', ', $valor) : $valor; @endphp
                                    <p class="text-sm text-slate-800 dark:text-slate-300"><b class="capitalize">{{ str_replace('_', ' ', $chave) }}:</b> {{ $valorFormatado ?: '-' }}</p>
                                @endforeach
                            @else
                                <p class="text-sm text-slate-500 italic">Nenhum dado complementar registrado.</p>
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