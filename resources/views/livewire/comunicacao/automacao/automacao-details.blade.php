<div class="p-6 max-w-7xl mx-auto font-sans relative">
    
    <x-page-header 
        title="Detalhes da Automação" 
        icon="ph ph-chart-line-up"
        badge="{{ $automacao->status ? 'Ativa' : 'Inativa' }}"
        :breadcrumbs="$breadcrumbs"
        :metricas="$metricas">
        
        <x-slot name="actions">
            <a href="{{ route('automacoes.edit', $automacao->id) }}" class="flex items-center gap-2 px-4 py-2 text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 font-bold text-sm">
                <i class="ph ph-pencil-simple text-lg"></i> Editar Regra
            </a>
        </x-slot>
    </x-page-header>

    <!-- Tabela de Histórico -->
    <div class="bg-white border border-gray-100 shadow-sm rounded-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
            <h3 class="font-bold text-gray-800 flex items-center gap-2">
                <i class="ph-fill ph-clock-counter-clockwise text-gray-400"></i> Últimos Disparos (Log)
            </h3>
        </div>
        
        <div class="overflow-x-auto custom-scrollbar">
            <table class="min-w-full w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-500 uppercase border-b border-gray-100 bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 whitespace-nowrap">Data / Hora</th>
                        <th class="px-6 py-3 whitespace-nowrap">Destinatários</th>
                        <th class="px-6 py-3 whitespace-nowrap">Status do Envio</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($historico as $log)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-3 whitespace-nowrap font-medium text-gray-900">
                                {{ $log->created_at->format('d/m/Y') }} <span class="text-gray-400 ml-1">{{ $log->created_at->format('H:i:s') }}</span>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                @php $qtd = is_array($log->destinatarios) ? count($log->destinatarios) : 1; @endphp
                                <span class="inline-flex items-center gap-1 text-sm font-bold text-gray-700 bg-gray-100 px-2 py-0.5 rounded">
                                    <i class="ph-fill ph-users text-gray-400"></i> {{ $qtd }} envio(s)
                                </span>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <span class="px-2.5 py-1 text-[11px] font-bold rounded-full uppercase tracking-wider border border-green-200 bg-green-50 text-green-700">
                                    <i class="ph-bold ph-check mr-1"></i> Entregue
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-12 text-center text-gray-500">
                                <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-2 border border-gray-200">
                                    <i class="ph ph-envelope-simple-open text-2xl text-gray-400"></i>
                                </div>
                                <p class="font-bold text-gray-600">Nenhum histórico encontrado.</p>
                                <p class="text-xs mt-1">Quando essa automação for acionada, os registros aparecerão aqui.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($historico->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $historico->links('components.paginacao-customizada') }}
            </div>
        @endif
    </div>
</div>