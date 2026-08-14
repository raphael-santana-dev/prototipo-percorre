<div class="p-6 max-w-[1400px] mx-auto font-sans">
    
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => '#'], 
        ['label' => 'Unidades', 'url' => route('unidades.index') ?? '#'], 
        ['label' => 'Detalhes', 'url' => '#']
    ]" />

    <x-details-card 
        title="Ficha da Unidade" 
        subtitle="Visão geral e metadados da unidade operacional."
        backUrl="{{ route('unidades.index') ?? '#' }}"
        backLabel="Voltar à Lista"
        avatarInitials="{{ strtoupper(substr($unidade->nome, 0, 2)) }}"
        itemName="{{ $unidade->nome }}"
        itemDescription="{{ $unidade->endereco }}">
        
        <x-slot name="badge">
            @if($unidade->status === 'Ativa')
                <span class="px-3 py-1 bg-green-100 text-green-700 font-bold text-xs rounded-full border border-green-200">EM OPERAÇÃO</span>
            @else
                <span class="px-3 py-1 bg-red-100 text-red-700 font-bold text-xs rounded-full border border-red-200">INATIVA</span>
            @endif
        </x-slot>

        <!-- Slot Inferior (Grid de Metadados) -->
        <div>
            <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">E-mail Corporativo</span>
            <span class="block text-sm font-bold text-gray-900">{{ $unidade->email ?: 'Não informado' }}</span>
        </div>
        <div>
            <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Telefone Principal</span>
            <span class="block text-sm font-bold text-gray-900">{{ $unidade->telefone ?: 'Não informado' }}</span>
        </div>
        <div>
            <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Inauguração</span>
            <span class="block text-sm font-bold text-gray-900">{{ $unidade->data_inauguracao ? \Carbon\Carbon::parse($unidade->data_inauguracao)->format('d/m/Y') : 'Desconhecida' }}</span>
        </div>
        <div>
            <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Tipo de Perfil</span>
            <span class="inline-block px-2 py-0.5 mt-0.5 bg-blue-50 text-blue-600 font-bold text-[10px] rounded border border-blue-200">UNIDADE BASE</span>
        </div>
    </x-details-card>

    <div class="grid grid-cols-1 mt-6">
        <!-- Cursos Vinculados -->
        <div class="bg-white border border-gray-200 shadow-sm rounded-xl p-6 h-full">
            <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
                <h3 class="font-bold text-gray-900 flex items-center gap-2">
                    <i class="ph-fill ph-graduation-cap text-purpura-500 text-lg"></i> Cursos Ministrados nesta Unidade
                </h3>
                <span class="bg-gray-50 text-gray-600 text-[10px] uppercase font-bold px-2.5 py-1 rounded border border-gray-200">
                    {{ $unidade->cursos->count() }} cursos ativos
                </span>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                @forelse($unidade->cursos as $curso)
                    <div class="p-4 border border-gray-100 rounded-xl bg-gray-50 hover:border-purpura-300 transition-colors flex items-start gap-3">
                        <div class="p-2.5 bg-white rounded-lg shadow-sm border border-gray-200">
                            <i class="text-xl ph-fill ph-book-bookmark text-ponkan-500"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 text-sm">{{ $curso->nome }}</h4>
                            <p class="text-[10px] font-bold text-gray-500 uppercase mt-1">Status: {{ $curso->status }}</p>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full p-12 text-center border border-dashed border-gray-300 rounded-xl">
                        <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="ph ph-books text-2xl text-gray-400"></i>
                        </div>
                        <p class="text-gray-500 font-bold text-sm">Nenhum curso associado a esta unidade atualmente.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>