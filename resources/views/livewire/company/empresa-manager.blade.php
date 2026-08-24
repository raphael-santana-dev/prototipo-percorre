<div class="p-6 max-w-7xl mx-auto font-sans relative">

    <x-page-header 
        title="Empresas Parceiras" 
        icon="ph ph-buildings"
        badge="Integração ERP"
        :breadcrumbs="$breadcrumbs">

        <x-slot name="filters">
            <div class="flex gap-2 items-center">
                <span class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider mr-2 hidden md:block">
                    <i class="ph-fill ph-lock-key"></i> Somente Leitura
                </span>
                
                <div class="relative">
                    <input wire:model.live.debounce.300ms="filtro_busca" type="text" placeholder="Buscar por Nome ou CNPJ..." class="pl-9 pr-4 py-2 rounded-md border-gray-300 text-sm shadow-sm focus:ring-purpura-500 focus:border-purpura-500 w-64 md:w-80 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                    <i class="ph ph-magnifying-glass absolute left-3 top-2.5 text-gray-400 text-lg"></i>
                </div>

                @if($filtro_busca !== '')
                    <button wire:click="limparFiltros" class="px-3 py-2 text-sm font-bold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-1 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        <i class="ph-bold ph-x"></i> Limpar
                    </button>
                @endif
            </div>
        </x-slot>

    </x-page-header>

    <x-table
        :headers="$this->headers"
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao"
        :permiteGrid="$permiteGrid"
        :modoExibicao="$modoExibicao">

        @forelse ($registros as $empresa)
            <tr wire:key="empresa-{{ $empresa->id }}" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                
                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-500 dark:text-gray-400">
                    #{{ $empresa->id }}
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $empresa->nome_fantasia ?? $empresa->razao_social }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $empresa->razao_social }}</div>
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap font-mono text-sm text-gray-700 dark:text-gray-300">
                    {{ preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', str_pad($empresa->cnpj, 14, '0', STR_PAD_LEFT)) }}
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                        @if($empresa->is_active)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-green-50 text-green-700 border border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800">
                                Ativa
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800">
                                Inativa
                            </span>
                        @endif

                        <div class="flex gap-2">
                            <span class="text-xs text-gray-500 dark:text-gray-400 font-bold" title="Aprendizes Vinculados"><i class="ph-fill ph-student text-indigo-500"></i> {{ $empresa->aprendizes_count }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 font-bold" title="Avaliadores/Gestores"><i class="ph-fill ph-users-three text-purpura-500"></i> {{ $empresa->company_users_count }}</span>
                        </div>
                    </div>
                </td>
                
                <td class="px-4 py-3 whitespace-nowrap text-right">
                    <a href="{{ route('empresas.show', $empresa->id) }}" class="inline-flex items-center justify-center p-2 text-gray-400 transition-colors rounded hover:text-purpura-600 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Ver Detalhes e Vínculos">
                        <i class="text-xl ph-bold ph-caret-right"></i>
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-12 text-center text-gray-500">
                    <i class="ph ph-buildings text-4xl mb-3 text-gray-300 dark:text-gray-600"></i>
                    <p class="font-bold text-gray-700 dark:text-gray-400">Nenhuma empresa encontrada.</p>
                    <p class="text-xs mt-1">As empresas parceiras serão populadas aqui através da integração com o Protheus.</p>
                </td>
            </tr>
        @endforelse

        {{-- SLOT DE SEGURANÇA: Se o componente forçar grid, desenha vazio e não quebra a tela --}}
        <x-slot name="gridSlot"></x-slot>

    </x-table>
</div>