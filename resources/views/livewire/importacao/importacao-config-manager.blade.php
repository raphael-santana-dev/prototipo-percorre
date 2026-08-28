<div class="p-6 mx-auto font-sans relative max-w-7xl">
    <div class="mb-6 flex justify-between items-end">
        <div>
            <h1 class="text-2xl font-black text-gray-900 flex items-center gap-2"><i class="ph-bold ph-plugs text-purpura-600"></i> Hub de Integrações (Dicionário de Models)</h1>
            <p class="text-sm text-gray-500 font-medium">Defina onde o sistema deve buscar os IDs (foreign keys) quando receber textos via planilhas.</p>
        </div>
        <button wire:click="abrirModalNovo" class="bg-purpura-600 hover:bg-purpura-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm flex items-center gap-2">
            <i class="ph-bold ph-plus"></i> Novo Mapeamento
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-bold">
                <tr>
                    <th class="p-4">Coluna Recebida</th>
                    <th class="p-4">Model Class Destino</th>
                    <th class="p-4">Campo de Busca</th>
                    <th class="p-4 text-center">Permite Auto-Cadastro?</th>
                    <th class="p-4 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($configs as $c)
                    <tr class="hover:bg-gray-50">
                        <td class="p-4 font-bold text-purpura-700">{{ $c->coluna }}</td>
                        <td class="p-4 text-gray-600 font-mono text-xs">{{ $c->model_class }}</td>
                        <td class="p-4 font-medium text-gray-800">{{ $c->campo_busca }}</td>
                        <td class="p-4 text-center">
                            @if($c->auto_cadastro) <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-[10px] font-bold">SIM</span> @else <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-[10px] font-bold">NÃO</span> @endif
                        </td>
                        <td class="p-4 text-right">
                            <button wire:click="editar({{ $c->id }})" class="p-1.5 text-blue-500 hover:bg-blue-50 rounded"><i class="ph-fill ph-pencil-simple text-lg"></i></button>
                            <button wire:click="excluir({{ $c->id }})" class="p-1.5 text-red-500 hover:bg-red-50 rounded"><i class="ph-fill ph-trash text-lg"></i></button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-8 text-center text-gray-400 font-medium">Nenhuma regra cadastrada. O Job não traduzirá NENHUMA coluna.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($modalAberto)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex justify-between">
                <h3 class="font-bold text-gray-900 text-lg">Regra de Relacionamento</h3>
                <button wire:click="$set('modalAberto', false)"><i class="ph-bold ph-x text-gray-400"></i></button>
            </div>
            <form wire:submit.prevent="salvar" class="p-5 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Nome da Coluna Alvo (ex: curso_id)</label>
                    <input wire:model="coluna" type="text" class="w-full border-gray-300 rounded focus:ring-purpura-500 focus:border-purpura-500 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Model de Destino (ex: \App\Models\Curso)</label>
                    <input wire:model="model_class" type="text" class="w-full border-gray-300 rounded focus:ring-purpura-500 focus:border-purpura-500 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Campo para pesquisa (ex: nome)</label>
                    <input wire:model="campo_busca" type="text" class="w-full border-gray-300 rounded focus:ring-purpura-500 focus:border-purpura-500 text-sm">
                </div>
                <div class="flex items-center gap-2 mt-2 bg-purpura-50 p-3 rounded border border-purpura-100">
                    <input wire:model="auto_cadastro" type="checkbox" id="checkAuto" class="text-purpura-600 rounded border-gray-300">
                    <label for="checkAuto" class="text-xs font-bold text-purpura-900 cursor-pointer">Se não encontrar no banco, criar um novo registro?</label>
                </div>
                @if($auto_cadastro)
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Payload JSON Padrão para criação (Opcional)</label>
                    <textarea wire:model="payload_padrao" rows="3" placeholder='Ex: {"status": "Ativo", "permite_estado_diferente": false}' class="w-full border-gray-300 rounded focus:ring-purpura-500 font-mono text-xs"></textarea>
                    @error('payload_padrao') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                </div>
                @endif
                <div class="pt-4 flex justify-end">
                    <button type="submit" class="bg-purpura-600 hover:bg-purpura-700 text-white font-bold py-2 px-6 rounded-lg shadow-sm">Salvar Regra</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>