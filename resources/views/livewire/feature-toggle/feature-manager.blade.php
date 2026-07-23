<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Gerenciamento de Features (Toggles)</h1>
    </div>

    <!-- Formulário para adicionar nova feature -->
    <div class="p-6 bg-white border border-gray-100 shadow-sm rounded-xl">
        <h2 class="text-lg font-medium text-gray-900">Cadastrar Nova Feature</h2>
        <form wire:submit="addFeature" class="flex items-end gap-4 mt-4">
            <div class="flex-1">
                <label for="name" class="block text-sm font-medium text-gray-700">Chave (ex: area_alunos)</label>
                <input type="text" id="name" wire:model="name" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
            <div class="flex-1">
                <label for="description" class="block text-sm font-medium text-gray-700">Descrição</label>
                <input type="text" id="description" wire:model="description" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                @error('description') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Adicionar
                </button>
            </div>
        </form>
    </div>

    <!-- Lista de Features -->
    <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-xl">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Status</th>
                    <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Chave da Feature</th>
                    <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Descrição</th>
                    <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Ação</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($features as $feature)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($feature->is_active)
                                <span class="inline-flex px-2 text-xs font-semibold leading-5 text-green-800 bg-green-100 rounded-full">Ativa</span>
                            @else
                                <span class="inline-flex px-2 text-xs font-semibold leading-5 text-red-800 bg-red-100 rounded-full">Inativa</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-mono text-sm font-medium text-gray-900 whitespace-nowrap">
                            {{ $feature->name }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $feature->description }}
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-right whitespace-nowrap">
                            <button 
                                wire:click="toggle('{{ $feature->name }}', {{ $feature->is_active ? 'true' : 'false' }})" 
                                class="{{ $feature->is_active ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900' }} font-semibold">
                                {{ $feature->is_active ? 'Desativar' : 'Ativar' }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-sm text-center text-gray-500">Nenhuma feature cadastrada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>