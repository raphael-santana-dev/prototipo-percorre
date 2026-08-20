<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Gestão de Matrículas" 
        icon="ph ph-identification-card"
        badge="Secretaria">
        
        <x-slot name="actions">
            <a href="{{ route('matriculas.create') }}" wire:navigate class="px-4 py-2 text-sm font-bold text-white bg-purpura-600 hover:bg-purpura-700 rounded-lg shadow-sm transition flex items-center gap-2">
                <i class="ph-bold ph-plus text-lg"></i> Nova Matrícula
            </a>
        </x-slot>

        <x-slot name="filters">
            <div class="w-full md:w-1/3 relative">
                <input type="text" wire:model.live.debounce.300ms="busca" placeholder="Buscar por nome, CPF ou RA..." class="w-full pl-10 pr-4 py-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-purpura-500 focus:border-purpura-500 bg-white dark:bg-gray-800 text-sm shadow-sm transition dark:text-white">
                <i class="ph ph-magnifying-glass text-gray-400 absolute left-3 top-2.5 text-lg"></i>
            </div>
        </x-slot>
    </x-page-header>

    <x-table 
        :headers="$this->headers" 
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="false">
        
        @forelse($registros as $matricula)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                
                <td class="px-4 py-3 whitespace-nowrap">
                    <span class="font-black text-purpura-600 dark:text-purpura-400 text-md">{{ $matricula->numero_matricula }}</span>
                </td>

                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="font-bold text-gray-800 dark:text-white text-sm">{{ $matricula->student->name ?? 'Estudante Removido' }}</div>
                    <div class="text-[10px] text-gray-500 font-mono mt-0.5">{{ $matricula->student->cpf ?? '-' }}</div>
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="font-bold text-gray-700 dark:text-gray-300 text-sm truncate max-w-[250px]" title="{{ $matricula->curso->nome ?? 'N/A' }}">
                        {{ $matricula->curso->nome ?? 'N/A' }}
                    </div>
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap text-center">
                    @php
                        $cores = [
                            'ativa' => 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/30 dark:text-green-400',
                            'concluida' => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400',
                            'trancada' => 'bg-orange-100 text-orange-800 border-orange-200 dark:bg-orange-900/30 dark:text-orange-400',
                            'cancelada' => 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-400',
                        ];
                        $corClass = $cores[strtolower($matricula->status)] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                    @endphp
                    <span class="px-2.5 py-1 text-[10px] font-bold rounded-full {{ $corClass }} uppercase tracking-wider border">
                        {{ $matricula->status }}
                    </span>
                </td>
                
                <td class="px-4 py-3 text-right whitespace-nowrap space-x-1">
                    <a href="{{ route('matriculas.edit', $matricula->id) }}" wire:navigate class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-gray-600 inline-block" title="Editar Matrícula">
                        <i class="text-xl ph ph-pencil-simple"></i>
                    </a>
                    <button wire:click="excluir({{ $matricula->id }})" wire:confirm="Isso apagará a matrícula deste aluno. Deseja continuar?" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50 dark:hover:bg-gray-600 inline-block" title="Excluir">
                        <i class="text-xl ph ph-trash"></i>
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-12 text-center">
                    <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3 border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                        <i class="ph ph-identification-card text-2xl text-gray-400"></i>
                    </div>
                    <p class="font-bold text-gray-600 dark:text-gray-400">Nenhuma matrícula registrada.</p>
                </td>
            </tr>
        @endforelse
    </x-table>
</div>