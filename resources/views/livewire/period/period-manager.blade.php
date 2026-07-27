<div class="p-6">
    @if (session()->has('sucesso'))
        <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded shadow-sm">
            {{ session('sucesso') }}
        </div>
    @endif

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Gerenciamento de Ciclos (Semestres)</h2>
        <button wire:click="abrirModal" class="bg-brand-purple hover:bg-brand-purpleHover text-white font-bold py-2 px-4 rounded shadow">
            + Novo Ciclo
        </button>
    </div>

    <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-200">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-3">Nome / Período</th>
                    <th class="px-6 py-3">Abertura</th>
                    <th class="px-6 py-3">Encerramento</th>
                    <th class="px-6 py-3 text-center">Status</th>
                    <th class="px-6 py-3 text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ciclos as $ciclo)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900">{{ $ciclo->nome }}</div>
                            <div class="text-xs text-gray-500">{{ $ciclo->ano }}.{{ $ciclo->semestre }}</div>
                        </td>
                        <td class="px-6 py-4">{{ $ciclo->data_inicio->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-4">{{ $ciclo->data_fim->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-4 text-center">
                            
                            <div class="text-[10px] mt-1 font-bold {{ $ciclo->status ? 'text-green-600' : 'text-gray-500' }}">
                                {{ $ciclo->status ? 'ATIVO' : 'INATIVO' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="abrirModal({{ $ciclo->id }})" class="text-indigo-600 hover:text-indigo-900 font-medium">Editar</button>
                            <a href="{{ route('ciclos.campos', $ciclo->id) }}" class="text-blue-600 hover:text-blue-900 font-medium mr-3">Gerenciar Perguntas</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">Nenhum ciclo cadastrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">
            {{ $ciclos->links() }}
        </div>
    </div>

    @if($modalAberto)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden">
            <div class="flex justify-between items-center p-5 border-b bg-gray-50">
                <h3 class="text-lg font-bold text-gray-800">{{ $cicloId ? 'Editar Ciclo' : 'Novo Ciclo' }}</h3>
                <button wire:click="fecharModal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="p-6">
                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 mb-1">Nome de Exibição (Ex: Processo Seletivo 2026)</label>
                    <input type="text" wire:model="nome" class="w-full border rounded p-2 focus:ring-brand-purple focus:border-brand-purple">

                    {{-- NOVO AVISO --}}
                    <p class="mt-1 text-xs text-indigo-600 font-medium flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Dica: Se você deixar este campo em branco, o sistema gerará o nome automaticamente (Ex: "2026 - 2º Semestre").
                    </p>

                    @error('nome') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Ano</label>
                        <input type="number" wire:model="ano" class="w-full border rounded p-2 focus:ring-brand-purple focus:border-brand-purple">
                        @error('ano') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Semestre</label>
                        <select wire:model="semestre" class="w-full border rounded p-2 focus:ring-brand-purple focus:border-brand-purple">
                            <option value="1">1º Semestre</option>
                            <option value="2">2º Semestre</option>
                        </select>
                        @error('semestre') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Data/Hora Abertura</label>
                        <input type="datetime-local" wire:model="data_inicio" class="w-full border rounded p-2 focus:ring-brand-purple focus:border-brand-purple">
                        @error('data_inicio') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Data/Hora Encerramento</label>
                        <input type="datetime-local" wire:model="data_fim" class="w-full border rounded p-2 focus:ring-brand-purple focus:border-brand-purple">
                        @error('data_fim') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex items-center mb-2">
                    <input type="checkbox" wire:model="status" id="status" class="w-5 h-5 text-brand-purple border-gray-300 rounded focus:ring-brand-purple">
                    <label for="status" class="ml-2 block text-sm font-bold text-gray-900">
                        Ativar este ciclo
                    </label>
                </div>
                <p class="text-xs text-gray-500 ml-7">Ao ativar, os outros ciclos abertos serão automaticamente inativados para evitar conflito no formulário.</p>
            </div>
            
            <div class="p-5 border-t bg-gray-50 flex justify-end gap-3">
                <button wire:click="fecharModal" class="px-4 py-2 border border-gray-300 rounded text-gray-700 bg-white hover:bg-gray-50 font-medium">Cancelar</button>
                <button wire:click="salvar" class="px-4 py-2 bg-brand-purple text-white rounded hover:bg-brand-purpleHover font-medium shadow-sm">Salvar Ciclo</button>
            </div>
        </div>
    </div>
    @endif
</div>