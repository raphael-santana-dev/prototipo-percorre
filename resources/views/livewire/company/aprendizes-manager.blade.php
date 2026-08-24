<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <a href="{{ route('company.dashboard') }}" wire:navigate class="text-purpura-600 hover:text-purpura-800 text-sm font-bold flex items-center gap-1 mb-2">
                <i class="ph-bold ph-arrow-left"></i> Voltar ao Dashboard
            </a>
            <h2 class="text-2xl font-black text-gray-900 dark:text-white flex items-center gap-2">
                <i class="ph-fill ph-student"></i> Aprendizes da Empresa
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                {{ $usuario->tipo_acesso === 'contato_principal' ? 'Vincule os aprendizes aos seus respectivos gestores diretos.' : 'Abaixo estão os aprendizes que você precisa avaliar.' }}
            </p>
        </div>
    </div>

    @if (session()->has('sucesso'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg flex items-center gap-2 font-bold">
            <i class="ph-fill ph-check-circle text-xl"></i> {{ session('sucesso') }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30">
            <div class="relative w-full md:w-1/3">
                <input type="text" wire:model.live.debounce.300ms="busca" placeholder="Buscar por nome ou CPF..." class="w-full pl-10 pr-4 py-2 text-sm border-gray-300 rounded-lg focus:ring-purpura-500 focus:border-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <i class="ph ph-magnifying-glass absolute left-3 top-2.5 text-gray-400 text-lg"></i>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 font-bold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="px-6 py-3">Aprendiz (Aluno)</th>
                        <th class="px-6 py-3">CPF</th>
                        <th class="px-6 py-3">Gestor Vinculado</th>
                        <th class="px-6 py-3 text-right">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($aprendizes as $aluno)
                        <tr wire:key="aluno-{{ $aluno->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">
                                {{ $aluno->name }}
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300 font-mono text-xs">
                                {{ preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $aluno->cpf) }}
                            </td>
                            <td class="px-6 py-4">
                                @if($aluno->gestor_id)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 text-blue-700 border border-blue-200 rounded-md text-xs font-bold">
                                        <i class="ph-fill ph-user-circle"></i> {{ $aluno->gestor->name }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400 font-bold italic">Sem gestor atribuído</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($usuario->tipo_acesso === 'contato_principal')
                                    <button wire:click="abrirModalVinculo({{ $aluno->id }})" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded shadow-sm transition">
                                        <i class="ph-bold ph-link"></i> Vincular Gestor
                                    </button>
                                @else
                                    <button class="px-4 py-1.5 bg-green-500 hover:bg-green-600 text-white text-xs font-bold rounded shadow-sm transition">
                                        Responder Avaliação
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                <i class="ph ph-student text-4xl mb-3 text-gray-400"></i>
                                <p class="font-bold text-gray-600">Nenhum aprendiz encontrado.</p>
                                @if($usuario->tipo_acesso === 'contato_principal')
                                    <p class="text-xs mt-1">Os aprendizes da sua empresa aparecerão aqui assim que forem sincronizados.</p>
                                @else
                                    <p class="text-xs mt-1">Nenhum aprendiz foi atribuído a você para avaliação no momento.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-100 dark:border-gray-700">
            {{ $aprendizes->links() }}
        </div>
    </div>

    {{-- MODAL DE VÍNCULO (Visível apenas para Contato Principal) --}}
    @if($modalAberto && $usuario->tipo_acesso === 'contato_principal')
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="flex justify-between items-center p-5 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Atribuir Gestor Avaliador</h3>
                    <button wire:click="$set('modalAberto', false)" class="text-gray-400 hover:text-red-500 transition"><i class="ph-bold ph-x text-lg"></i></button>
                </div>
                
                <form wire:submit.prevent="vincularGestor" class="p-6">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Selecione qual gestor será responsável por acompanhar e avaliar este aprendiz durante o semestre:
                    </p>

                    <div class="mb-6">
                        <select wire:model="gestorSelecionadoId" class="w-full text-sm rounded-lg border-gray-300 focus:ring-purpura-500 focus:border-purpura-500 font-bold text-gray-700">
                            <option value="">Nenhum Gestor (Remover vínculo)</option>
                            @foreach($gestores as $gestor)
                                <option value="{{ $gestor->id }}">{{ $gestor->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                        <button type="button" wire:click="$set('modalAberto', false)" class="px-4 py-2 text-sm font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition">Cancelar</button>
                        <button type="submit" class="px-5 py-2 text-sm font-bold text-white bg-purpura-600 hover:bg-purpura-700 rounded-lg shadow transition">Confirmar Vínculo</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>