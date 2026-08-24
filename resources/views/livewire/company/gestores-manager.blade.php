<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <a href="{{ route('company.dashboard') }}" wire:navigate class="text-purpura-600 hover:text-purpura-800 text-sm font-bold flex items-center gap-1 mb-2">
                <i class="ph-bold ph-arrow-left"></i> Voltar ao Dashboard
            </a>
            <h2 class="text-2xl font-black text-gray-900 dark:text-white flex items-center gap-2">
                <i class="ph-fill ph-users"></i> Equipe de Avaliadores
            </h2>
        </div>
        <button wire:click="abrirModal" class="px-5 py-2.5 bg-purpura-600 hover:bg-purpura-700 text-white font-bold rounded-lg shadow transition flex items-center gap-2">
            <i class="ph-bold ph-plus"></i> Novo Avaliador
        </button>
    </div>

    @if (session()->has('sucesso'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg flex items-center gap-2 font-bold">
            <i class="ph-fill ph-check-circle text-xl"></i> {{ session('sucesso') }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30">
            <div class="relative w-full md:w-1/3">
                <input type="text" wire:model.live.debounce.300ms="busca" placeholder="Buscar por nome, e-mail ou CPF..." class="w-full pl-10 pr-4 py-2 text-sm border-gray-300 rounded-lg focus:ring-purpura-500 focus:border-purpura-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <i class="ph ph-magnifying-glass absolute left-3 top-2.5 text-gray-400 text-lg"></i>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 font-bold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="px-6 py-3">Nome do Gestor</th>
                        <th class="px-6 py-3">Contato</th>
                        <th class="px-6 py-3 text-center">Status</th>
                        <th class="px-6 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($gestores as $gestor)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-900 dark:text-white">{{ $gestor->name }}</div>
                                <div class="text-xs text-gray-500 font-mono">CPF: {{ preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $gestor->documento) }}</div>
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $gestor->email }}</td>
                            <td class="px-6 py-4 text-center">
                                @if($gestor->is_active)
                                    <span class="px-2.5 py-1 text-[10px] font-bold rounded border bg-green-50 text-green-700 border-green-200">Ativo</span>
                                @else
                                    <span class="px-2.5 py-1 text-[10px] font-bold rounded border bg-red-50 text-red-700 border-red-200">Inativo</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button wire:click="abrirModal({{ $gestor->id }})" class="p-1.5 text-gray-400 hover:text-blue-600 transition" title="Editar">
                                    <i class="ph-bold ph-pencil-simple text-lg"></i>
                                </button>
                                <button wire:click="excluir({{ $gestor->id }})" wire:confirm="Remover este gestor do sistema?" class="p-1.5 text-gray-400 hover:text-red-600 transition" title="Excluir">
                                    <i class="ph-bold ph-trash text-lg"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                <i class="ph ph-users-slash text-3xl mb-2"></i>
                                <p class="font-bold">Nenhum avaliador cadastrado.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-100 dark:border-gray-700">
            {{ $gestores->links() }}
        </div>
    </div>

    {{-- MODAL DE CADASTRO --}}
    @if($modalAberto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg overflow-hidden">
                <div class="flex justify-between items-center p-5 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $gestorId ? 'Editar Avaliador' : 'Novo Avaliador' }}</h3>
                    <button wire:click="$set('modalAberto', false)" class="text-gray-400 hover:text-red-500 transition"><i class="ph-bold ph-x text-lg"></i></button>
                </div>
                
                <form wire:submit.prevent="salvar" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Nome Completo</label>
                        <input type="text" wire:model="name" class="w-full text-sm rounded-lg border-gray-300 focus:ring-purpura-500 focus:border-purpura-500">
                        @error('name') <span class="text-xs text-red-500 font-bold">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">E-mail Corporativo</label>
                            <input type="email" wire:model="email" class="w-full text-sm rounded-lg border-gray-300 focus:ring-purpura-500 focus:border-purpura-500">
                            @error('email') <span class="text-xs text-red-500 font-bold">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">CPF do Gestor</label>
                            <input type="text" wire:model="documento" class="w-full text-sm rounded-lg border-gray-300 focus:ring-purpura-500 focus:border-purpura-500" placeholder="Apenas números">
                            @error('documento') <span class="text-xs text-red-500 font-bold">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    @if(!$gestorId)
                        <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg flex items-start gap-2 text-yellow-800 text-xs">
                            <i class="ph-fill ph-info text-lg shrink-0"></i>
                            <p>A senha padrão inicial deste gestor será <strong>mudar123</strong>. Instrua-o a acesssar o portal e alterar a senha no primeiro login.</p>
                        </div>
                    @endif

                    <div class="flex items-center gap-2 mt-2">
                        <input type="checkbox" wire:model="is_active" id="is_active" class="w-4 h-4 text-purpura-600 border-gray-300 rounded focus:ring-purpura-500">
                        <label for="is_active" class="text-sm font-bold text-gray-700 dark:text-gray-300 cursor-pointer">Avaliador Ativo</label>
                    </div>

                    <div class="pt-4 flex justify-end gap-3 border-t border-gray-100 mt-6">
                        <button type="button" wire:click="$set('modalAberto', false)" class="px-4 py-2 text-sm font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition">Cancelar</button>
                        <button type="submit" class="px-5 py-2 text-sm font-bold text-white bg-purpura-600 hover:bg-purpura-700 rounded-lg shadow transition">Salvar Dados</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>