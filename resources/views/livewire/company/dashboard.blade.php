<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-gray-900 dark:text-white">Olá, {{ explode(' ', $usuario->name)[0] }}!</h2>
            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Bem-vindo(a) ao Portal da Empresa do Instituto Percorre.</p>
        </div>
        
        <div class="flex items-center gap-2">
            <span class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider rounded-full border {{ $usuario->tipo_acesso === 'contato_principal' ? 'bg-purpura-50 text-purpura-700 border-purpura-200 dark:bg-purpura-900/30 dark:text-purpura-400' : 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400' }}">
                <i class="ph-fill ph-identification-badge"></i> {{ $usuario->tipo_acesso === 'contato_principal' ? 'Contato de Aprendizagem' : 'Gestor Avaliador' }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex items-center gap-4">
            <div class="w-12 h-12 bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400 rounded-full flex items-center justify-center text-2xl shrink-0">
                <i class="ph-fill ph-users-three"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Aprendizes Ativos</p>
                <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ $metricas['total_aprendizes'] }}</h3>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex items-center gap-4">
            <div class="w-12 h-12 bg-yellow-50 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400 rounded-full flex items-center justify-center text-2xl shrink-0">
                <i class="ph-fill ph-warning-circle"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Avaliações Pendentes</p>
                <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ $metricas['avaliacoes_pendentes'] }}</h3>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex items-center gap-4">
            <div class="w-12 h-12 bg-green-50 text-green-600 dark:bg-green-900/30 dark:text-green-400 rounded-full flex items-center justify-center text-2xl shrink-0">
                <i class="ph-fill ph-check-circle"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Avaliações Concluídas</p>
                <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ $metricas['avaliacoes_concluidas'] }}</h3>
            </div>
        </div>
    </div>

    <div class="flex justify-end mb-6">
        <a href="{{ route('company.aprendizes') }}" wire:navigate class="px-4 py-2 bg-white border border-gray-200 shadow-sm text-gray-700 font-bold rounded-lg hover:bg-gray-50 transition flex items-center gap-2">
            <i class="ph-bold ph-student"></i> Ver Meus Aprendizes
        </a>
    </div>

    @if($usuario->tipo_acesso === 'contato_principal')
        <div class="bg-indigo-50 border border-indigo-200 dark:bg-indigo-900/20 dark:border-indigo-800 p-6 rounded-xl shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
            <div>
                <h4 class="text-indigo-800 dark:text-indigo-300 font-bold text-lg"><i class="ph-fill ph-users"></i> Gestão da Equipe</h4>
                <p class="text-indigo-600 dark:text-indigo-400 text-sm mt-1">Como contato principal, você precisa cadastrar os gestores que irão avaliar os aprendizes da sua empresa.</p>
            </div>
            <a href="{{ route('company.gestores') }}" wire:navigate class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow transition whitespace-nowrap">
                Gerenciar Avaliadores
            </a>
        </div>
    @endif
</div>