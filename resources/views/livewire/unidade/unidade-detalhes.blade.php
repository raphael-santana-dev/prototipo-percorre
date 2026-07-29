<div class="p-6 mx-auto font-sans max-w-7xl space-y-6">
    <div class="flex items-center gap-4 mb-4">
        <a href="{{ route('unidades.index') }}" class="p-2 text-gray-500 transition-colors bg-white border border-gray-200 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">
            <i class="text-xl ph ph-arrow-left"></i>
        </a>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
            Visão Geral da Unidade
        </h2>
    </div>

    <!-- Cabeçalho (Master) -->
    <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700 relative">
        <div class="absolute top-0 w-full h-32 bg-gradient-to-r from-purpura-600 to-petunia-500"></div>
        
        <div class="relative px-6 pt-24 pb-6 sm:px-8">
            <div class="flex flex-col sm:flex-row items-end sm:items-center gap-6">
                <!-- Avatar Unidade -->
                <div class="flex items-center justify-center w-24 h-24 bg-white border-4 border-white rounded-2xl shadow-lg dark:bg-gray-900 dark:border-gray-800 shrink-0">
                    <i class="text-4xl ph ph-buildings text-purpura-500"></i>
                </div>
                
                <div class="flex-1 w-full">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ $unidade->nome }}</h1>
                            <p class="text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-2">
                                <i class="ph ph-map-pin"></i> {{ $unidade->endereco }}
                            </p>
                        </div>
                        <div>
                            @if($unidade->status === 'Ativa')
                                <span class="px-4 py-2 text-sm font-bold text-pistache-700 bg-pistache-100 rounded-full uppercase border border-pistache-200">Em Operação</span>
                            @else
                                <span class="px-4 py-2 text-sm font-bold text-red-700 bg-red-100 rounded-full uppercase border border-red-200">Inativa</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grid de Informações -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Contatos e Metadados -->
        <div class="md:col-span-1 space-y-6">
            <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-6 dark:bg-gray-800 dark:border-gray-700">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4 border-b border-gray-100 pb-2 dark:border-gray-700">Contatos</h3>
                <ul class="space-y-4">
                    <li class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-300">
                        <div class="p-2 bg-gray-50 rounded-lg dark:bg-gray-700"><i class="text-lg ph ph-envelope-simple text-gray-400"></i></div>
                        {{ $unidade->email ?: 'Não informado' }}
                    </li>
                    <li class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-300">
                        <div class="p-2 bg-gray-50 rounded-lg dark:bg-gray-700"><i class="text-lg ph ph-phone text-gray-400"></i></div>
                        {{ $unidade->telefone ?: 'Não informado' }}
                    </li>
                    <li class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-300">
                        <div class="p-2 bg-gray-50 rounded-lg dark:bg-gray-700"><i class="text-lg ph ph-calendar-blank text-gray-400"></i></div>
                        Inauguração: <strong class="text-gray-900 dark:text-white">{{ $unidade->data_inauguracao ? \Carbon\Carbon::parse($unidade->data_inauguracao)->format('d/m/Y') : 'Desconhecida' }}</strong>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Cursos Vinculados -->
        <div class="md:col-span-2">
            <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-6 dark:bg-gray-800 dark:border-gray-700 h-full">
                <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-2 dark:border-gray-700">
                    <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="ph ph-graduation-cap text-purpura-500"></i> Cursos Ministrados nesta Unidade
                    </h3>
                    <span class="bg-gray-100 text-gray-600 text-xs font-bold px-2 py-1 rounded dark:bg-gray-700 dark:text-gray-300">
                        {{ $unidade->cursos->count() }} cursos
                    </span>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @forelse($unidade->cursos as $curso)
                        <div class="p-4 border border-gray-100 rounded-lg bg-gray-50 dark:bg-gray-900/50 dark:border-gray-700 flex items-start gap-3">
                            <div class="p-2 bg-white rounded-lg shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-600">
                                <i class="text-xl ph-fill ph-book-bookmark text-ponkan-500"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white">{{ $curso->nome }}</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Status: {{ $curso->status }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-2 p-8 text-center border border-dashed border-gray-300 rounded-xl dark:border-gray-700">
                            <p class="text-gray-500 dark:text-gray-400">Nenhum curso associado a esta unidade atualmente.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>