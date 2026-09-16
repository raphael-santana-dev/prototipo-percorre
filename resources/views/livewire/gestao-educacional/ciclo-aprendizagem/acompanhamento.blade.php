<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header title="Acompanhamento: {{ $ciclo->nome }}" icon="ph ph-presentation-chart" badge="Aprendizagem">
        <x-slot name="filters">
            <div class="flex gap-3 w-full md:w-auto">
                <input type="text" wire:model.live.debounce.500ms="busca" placeholder="Buscar por aluno..." class="w-64 border border-gray-300 rounded-lg focus:ring-purpura-500 focus:border-purpura-500 text-sm shadow-sm">
                <select wire:model.live="filtroStatus" class="border border-gray-300 rounded-lg focus:ring-purpura-500 focus:border-purpura-500 text-sm shadow-sm">
                    <option value="">Todos os Status</option>
                    <option value="pendente">Pendente</option>
                    <option value="em_andamento">Em Andamento</option>
                    <option value="concluido">Concluído</option>
                </select>
            </div>
        </x-slot>
    </x-page-header>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 font-bold text-[10px] text-gray-500 uppercase tracking-wider">Aprendiz / Empresa</th>
                    <th class="px-4 py-3 font-bold text-[10px] text-gray-500 uppercase tracking-wider text-center">Fase Atual</th>
                    <th class="px-4 py-3 font-bold text-[10px] text-gray-500 uppercase tracking-wider text-center">Status</th>
                    <th class="px-4 py-3 font-bold text-[10px] text-gray-500 uppercase tracking-wider text-right">Ação</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($alunos as $item)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="font-bold text-gray-900 text-sm">{{ $item->student->name ?? 'Aluno N/A' }}</div>
                            <div class="text-[11px] text-gray-500"><i class="ph-fill ph-buildings"></i> {{ $item->student->empresa->nome_fantasia ?? 'Empresa N/A' }}</div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2.5 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-md border border-indigo-200 shadow-sm">
                                {{ $item->faseAtual->nome ?? 'Finalizado' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($item->status === 'concluido')
                                <span class="px-2.5 py-1 bg-green-50 text-green-700 text-[10px] font-bold uppercase tracking-wider rounded-full"><i class="ph-bold ph-check"></i> Concluído</span>
                            @elseif($item->status === 'pendente')
                                <span class="px-2.5 py-1 bg-yellow-50 text-yellow-700 text-[10px] font-bold uppercase tracking-wider rounded-full"><i class="ph-bold ph-clock"></i> Pendente</span>
                            @else
                                <span class="px-2.5 py-1 bg-blue-50 text-blue-700 text-[10px] font-bold uppercase tracking-wider rounded-full"><i class="ph-bold ph-spinner animate-spin"></i> Em Andamento</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 text-xs font-bold rounded-lg transition shadow-sm">
                                Ver Respostas
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-12 text-center text-gray-500">Nenhum aluno vinculado a este ciclo com o filtro selecionado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-gray-100">
            {{ $alunos->links() }}
        </div>
    </div>
</div>