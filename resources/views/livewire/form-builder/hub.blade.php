<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white flex items-center gap-3">
            <i class="ph-fill ph-magic-wand text-purpura-600"></i> Construtor de Formulários
        </h1>
        <p class="text-gray-500 dark:text-gray-400 mt-2">Escolha abaixo o tipo de formulário que você deseja criar ou editar. O sistema adaptará as regras automaticamente.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- CARD 1: INSCRIÇÃO (CICLOS) -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 flex flex-col transition hover:shadow-md hover:-translate-y-1 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-50 dark:bg-indigo-900/20 rounded-bl-full -mr-10 -mt-10 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10">
                <div class="w-14 h-14 bg-indigo-100 dark:bg-indigo-900/50 rounded-xl flex items-center justify-center text-indigo-600 dark:text-indigo-400 mb-6 shadow-sm">
                    <i class="ph-bold ph-calendar-check text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Processo Seletivo</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 flex-1">
                    Crie a ficha de inscrição que os candidatos preencherão. Os dados coletados alimentarão a matriz de pontuação do ranking.
                </p>
                <button wire:click="$set('modalCicloAberto', true)" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-sm transition flex items-center justify-center gap-2">
                    Iniciar Construtor <i class="ph-bold ph-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- CARD 2: GERAL (FORMULÁRIOS) -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 flex flex-col transition hover:shadow-md hover:-translate-y-1 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-purpura-50 dark:bg-purpura-900/20 rounded-bl-full -mr-10 -mt-10 transition-transform group-hover:scale-110"></div>
            <div class="relative z-10">
                <div class="w-14 h-14 bg-purpura-100 dark:bg-purpura-900/50 rounded-xl flex items-center justify-center text-purpura-600 dark:text-purpura-400 mb-6 shadow-sm">
                    <i class="ph-bold ph-list-dashes text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Formulário Geral</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 flex-1">
                    Pesquisas de satisfação e formulários avulsos. Pode ser configurado com restrição de datas, usuários e níveis de acesso.
                </p>
                <a href="{{ route('formularios.create') }}" class="w-full py-3 bg-purpura-600 hover:bg-purpura-700 text-white font-bold rounded-lg shadow-sm transition flex items-center justify-center gap-2">
                    Criar Formulário Geral <i class="ph-bold ph-arrow-right"></i>
                </a>
            </div>
        </div>

        <!-- CARD 3: AVALIAÇÃO DE APRENDIZAGEM -->
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl border-2 border-dashed border-gray-300 dark:border-gray-600 p-6 flex flex-col relative overflow-hidden opacity-80 cursor-not-allowed">
            <div class="absolute top-4 right-4">
                <span class="bg-yellow-100 text-yellow-800 text-[10px] font-black uppercase px-2.5 py-1 rounded-full border border-yellow-200 tracking-wider">Em breve</span>
            </div>
            <div class="w-14 h-14 bg-gray-200 dark:bg-gray-700 rounded-xl flex items-center justify-center text-gray-500 dark:text-gray-400 mb-6">
                <i class="ph-bold ph-graduation-cap text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Avaliação Pedagógica</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 flex-1">
                Construtor de provas e matrizes integradas com a pauta dos professores no módulo de Gestão Educacional.
            </p>
            <button disabled class="w-full py-3 bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 font-bold rounded-lg cursor-not-allowed flex items-center justify-center gap-2">
                <i class="ph-bold ph-lock-key"></i> Módulo em Desenvolvimento
            </button>
        </div>

    </div>

    <!-- MODAL DE SELEÇÃO DE CICLO -->
    @if($modalCicloAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">A qual ciclo este formulário pertence?</h3>
                    <button wire:click="$set('modalCicloAberto', false)" class="text-gray-400 hover:text-gray-600"><i class="ph-bold ph-x text-xl"></i></button>
                </div>
                
                <div class="p-2 max-h-96 overflow-y-auto">
                    @forelse($ciclos as $ciclo)
                        <button wire:click="selecionarCiclo({{ $ciclo->id }})" class="w-full text-left px-4 py-3 hover:bg-indigo-50 dark:hover:bg-gray-700 transition border-b border-gray-50 flex items-center justify-between group">
                            <div>
                                <div class="font-bold text-gray-800 group-hover:text-indigo-700">{{ $ciclo->nome }}</div>
                                <div class="text-xs text-gray-500">{{ $ciclo->ano }}.{{ $ciclo->semestre }}</div>
                            </div>
                            <i class="ph-bold ph-caret-right text-gray-300 group-hover:text-indigo-500"></i>
                        </button>
                    @empty
                        <div class="p-6 text-center text-gray-500">Nenhum ciclo cadastrado no sistema.</div>
                    @endforelse
                </div>

                <div class="p-4 bg-gray-50 border-t border-gray-100 text-center">
                    <button wire:click="novoCiclo" class="text-sm font-bold text-indigo-600 hover:underline">
                        + Cadastrar um novo Processo Seletivo
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>