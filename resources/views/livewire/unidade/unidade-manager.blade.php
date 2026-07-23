<div class="space-y-6">
    <div class="flex items-center justify-between">
        <!-- Heading Large formatado -->
        <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-2">
            <i class="ph ph-map-pin text-purpura-500"></i> Unidades
        </h1>
        @can('unidade.criar')
            <!-- Button Primary Small/Medium usando Purpura -->
            <button wire:click="openModal" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg bg-purpura-500 hover:bg-purpura-600">
                <i class="ph ph-plus"></i> Nova Unidade
            </button>
        @endcan
    </div>

    @if (session()->has('success'))
        <div class="p-4 rounded-md text-pistache-100 bg-pistache-500"><i class="ph ph-check-circle"></i> {{ session('success') }}</div>
    @endif

    <div class="overflow-hidden bg-white border rounded-xl border-gray-200 shadow-gray-1">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-xs font-semibold text-left text-gray-500 uppercase">Nome</th>
                    <th class="px-6 py-3 text-xs font-semibold text-left text-gray-500 uppercase">Contato</th>
                    <th class="px-6 py-3 text-xs font-semibold text-left text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-xs font-semibold text-right text-gray-500 uppercase">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($unidades as $unidade)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $unidade->nome }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $unidade->email }} <br> <span class="text-xs">{{ $unidade->contatos }}</span></td>
                        <td class="px-6 py-4">
                            <!-- Tags baseadas no Design System -->
                            @if($unidade->status)
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-bold rounded-full bg-pistache-100 text-pistache-500"><i class="ph-bold ph-check"></i> Ativa</span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-bold rounded-full bg-gray-200 text-gray-500"><i class="ph-bold ph-minus"></i> Inativa</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-right">
                            <a href="{{ route('unidades.show', $unidade->id) }}" class="mr-3 text-ponkan-500 hover:text-ponkan-700"><i class="ph ph-magnifying-glass"></i> Detalhes</a>
                            @can('unidade.editar')
                                <button wire:click="edit({{ $unidade->id }})" class="text-purpura-500 hover:text-purpura-700"><i class="ph ph-pencil"></i> Editar</button>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- O Modal com Botão CTA -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 transition-opacity bg-gray-1000/50" wire:click="$set('showModal', false)"></div>
                
                <div class="relative z-10 w-full max-w-lg p-6 bg-white rounded-xl shadow-gray-4">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">{{ $isEditMode ? 'Editar' : 'Nova' }} Unidade</h3>
                    <form wire:submit="save" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-800">Nome da Unidade</label>
                            <input type="text" wire:model="nome" class="w-full mt-1">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-800">Endereço</label>
                            <input type="text" wire:model="endereco" class="w-full mt-1">
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-1">
                                <label class="block text-sm font-semibold text-gray-800">E-mail</label>
                                <input type="email" wire:model="email" class="w-full mt-1">
                            </div>
                            <div class="flex-1">
                                <label class="block text-sm font-semibold text-gray-800">Telefone/Contato</label>
                                <input type="text" wire:model="contatos" class="w-full mt-1">
                            </div>
                        </div>
                        <div class="pt-4 border-t border-gray-200 flex justify-end gap-3">
                            <!-- Botão Secondary Outline -->
                            <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-sm font-bold border rounded-lg text-purpura-500 border-purpura-500 hover:bg-purpura-100">Cancelar</button>
                            <!-- Botão CTA Ponkan -->
                            <button type="submit" class="px-4 py-2 text-sm font-bold text-white rounded-lg bg-ponkan-500 hover:bg-ponkan-600">Salvar Unidade</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>