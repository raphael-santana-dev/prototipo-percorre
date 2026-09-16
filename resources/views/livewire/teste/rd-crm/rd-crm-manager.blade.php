<div class="p-6 max-w-7xl mx-auto font-sans relative" x-data="{ abaAtiva: @entangle('abaAtiva') }">
    
    <x-page-header 
        title="Integração RD Station CRM"
        icon="ph ph-handshake"
        badge="Módulo de Teste"
        :breadcrumbs="$breadcrumbs">

        <x-slot name="actions">
            <!-- BOTÕES ENVOLVIDOS EM UMA DIV FLEXÍVEL PARA NÃO QUEBRAR O LAYOUT -->
            <div class="flex flex-wrap items-center justify-end gap-2 w-full">
                
                <button wire:click="criarCadastroTeste" wire:loading.attr="disabled" class="px-4 py-2 text-xs font-bold text-gray-700 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 transition flex items-center gap-1.5 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700" title="Gera um Contato/Deal fictício para testes">
                    <i class="ph-bold ph-user-plus text-base" wire:loading.class="animate-spin" wire:target="criarCadastroTeste"></i> Gerar Teste
                </button>

                <button wire:click="atualizarFunis" wire:loading.attr="disabled" class="px-4 py-2 text-xs font-bold text-purpura-700 bg-purpura-50 border border-purpura-200 rounded-lg shadow-sm hover:bg-purpura-100 transition flex items-center gap-1.5 dark:bg-purpura-900/30 dark:border-purpura-700 dark:text-purpura-400">
                    <i class="ph-bold ph-arrows-clockwise text-base" wire:loading.class="animate-spin" wire:target="atualizarFunis"></i> Baixar Funis
                </button>

                <button wire:click="forcarEnvioPendentes" wire:loading.attr="disabled" class="px-4 py-2 text-xs font-bold text-white bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700 transition flex items-center gap-1.5">
                    <i class="ph-bold ph-paper-plane-tilt text-base" wire:loading.class="animate-pulse animate-bounce" wire:target="forcarEnvioPendentes"></i> Forçar Envio
                </button>
            
            </div>
        </x-slot>
    </x-page-header>

    <!-- CARDS DE MÉTRICAS (IF REMOVIDO PARA EVITAR BUGS DE CARACTERE INVISÍVEL) -->
    <div class="mb-6">
        <x-summary-cards :metricas="$metricas" />
    </div>

    <!-- NAVEGAÇÃO DE ABAS -->
    <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl px-4 pt-2 shadow-sm border border-gray-200 dark:border-gray-700">
        <nav class="flex flex-wrap gap-6 -mb-px">
            <button @click="abaAtiva = 'funis'" :class="abaAtiva === 'funis' ? 'border-purpura-600 text-purpura-600 dark:text-purpura-400 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 font-medium'" class="py-3 px-1 border-b-2 text-sm flex items-center gap-2 transition-all">
                <i class="ph-bold ph-funnel text-lg"></i> Funis e Etapas (RD)
            </button>
            <button @click="abaAtiva = 'deals'" :class="abaAtiva === 'deals' ? 'border-purpura-600 text-purpura-600 dark:text-purpura-400 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 font-medium'" class="py-3 px-1 border-b-2 text-sm flex items-center gap-2 transition-all">
                <i class="ph-bold ph-users text-lg"></i> Negociações Locais (Deals)
            </button>
        </nav>
    </div>

    <!-- ABA 1: FUNIS -->
    <div x-show="abaAtiva === 'funis'" x-cloak class="space-y-6" wire:key="aba-funis">
        @forelse($pipelines as $pipe)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-4 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                    <div>
                        <h3 class="font-black text-gray-900 dark:text-white uppercase tracking-wider text-sm">{{ $pipe->nome }}</h3>
                        <p class="text-[10px] font-mono text-gray-500 dark:text-gray-400 mt-0.5">ID: {{ $pipe->rd_id }}</p>
                    </div>
                    <span class="px-2.5 py-1 text-[10px] font-bold bg-purpura-100 text-purpura-700 dark:bg-purpura-900/30 dark:text-purpura-400 rounded-full border border-purpura-200 dark:border-purpura-800">
                        {{ $pipe->stages->count() }} Etapas
                    </span>
                </div>
                
                <div class="p-5 overflow-x-auto custom-scrollbar">
                    <div class="flex gap-4 min-w-max">
                        @foreach($pipe->stages as $stage)
                            <div class="w-48 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-3 shadow-sm flex flex-col relative overflow-hidden group">
                                <div class="absolute top-0 left-0 w-1 h-full bg-purpura-500"></div>
                                <span class="text-xs font-bold text-gray-800 dark:text-gray-200 truncate">{{ $stage->nome }}</span>
                                <span class="text-[10px] text-gray-400 mt-1 font-mono truncate" title="{{ $stage->rd_id }}">ID: {{ $stage->rd_id }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @empty
            <div class="p-12 bg-white dark:bg-gray-800 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 text-center shadow-sm">
                <i class="ph-fill ph-funnel text-5xl text-gray-300 dark:text-gray-600 mb-3 block"></i>
                <h3 class="text-base font-bold text-gray-700 dark:text-gray-300">Nenhum Funil Sincronizado</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Clique em "Baixar Funis" no topo da tela para buscar a estrutura do RD Station.</p>
            </div>
        @endforelse
    </div>

    <!-- ABA 2: DEALS (TABELA) -->
    <div x-show="abaAtiva === 'deals'" x-cloak class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden" wire:key="aba-deals">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-gray-900/80 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="p-4 font-bold text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-wider">#ID Local</th>
                        <th class="p-4 font-bold text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contato (Lead)</th>
                        <th class="p-4 font-bold text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nome da Negociação</th>
                        <th class="p-4 font-bold text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-wider">Etapa Destino (ID)</th>
                        <th class="p-4 font-bold text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($deals as $deal)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/80 transition-colors">
                            <td class="p-4 text-xs font-bold text-gray-500 dark:text-gray-400">{{ $deal->id }}</td>
                            <td class="p-4">
                                <span class="font-bold text-sm text-gray-900 dark:text-white block">{{ $deal->contact->nome ?? 'Sem Contato' }}</span>
                                <span class="text-[10px] text-gray-500">{{ $deal->contact->email ?? '-' }}</span>
                            </td>
                            <td class="p-4 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $deal->nome }}</td>
                            <td class="p-4 text-[10px] font-mono text-gray-500">{{ $deal->deal_stage_id ?: 'Automático / Padrão' }}</td>
                            <td class="p-4 text-center">
                                @if($deal->sincronizado)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] uppercase font-bold tracking-wider text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-full dark:bg-emerald-900/30 dark:border-emerald-800 dark:text-emerald-400">
                                        <i class="ph-bold ph-check"></i> Sincronizado
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] uppercase font-bold tracking-wider text-orange-700 bg-orange-50 border border-orange-200 rounded-full dark:bg-orange-900/30 dark:border-orange-800 dark:text-orange-400">
                                        <i class="ph-bold ph-hourglass-high"></i> Pendente
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-10 text-center text-gray-400 italic text-sm">
                                Nenhuma negociação registrada localmente ainda.<br>
                                Clique em "Gerar Teste" para criar um registro pendente.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-100 dark:border-gray-800">
            {{ $deals->links('components.paginacao-customizada') }}
        </div>
    </div>

</div>