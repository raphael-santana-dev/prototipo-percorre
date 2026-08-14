<div class="p-6 max-w-[1400px] mx-auto font-sans">
    
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => '#'], 
        ['label' => 'Alunos', 'url' => route('students.index') ?? '#'], 
        ['label' => 'Detalhes', 'url' => '#']
    ]" />

    <x-details-card 
        title="Ficha do Aluno" 
        subtitle="Dados da conta e histórico de candidaturas."
        backUrl="{{ route('students.index') ?? '#' }}"
        backLabel="Voltar à Lista"
        avatarInitials="{{ strtoupper(substr($student->name, 0, 2)) }}"
        itemName="{{ $student->name }}"
        itemDescription="{{ $student->email }}">
        
        <x-slot name="badge">
            @if($student->is_active)
                <span class="px-3 py-1 bg-green-100 text-green-700 font-bold text-xs rounded-full border border-green-200">CONTA ATIVA</span>
            @else
                <span class="px-3 py-1 bg-gray-100 text-gray-700 font-bold text-xs rounded-full border border-gray-200">INATIVO</span>
            @endif
        </x-slot>

        <!-- Slot Inferior (Grid de Metadados) -->
        <div>
            <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Unidade Local</span>
            <span class="block text-sm font-bold text-gray-900 mt-1">{{ $student->unidade?->nome ?? 'Não alocado' }}</span>
        </div>
        
        <div>
            <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Data de Registro</span>
            <span class="block text-base font-bold text-gray-900">{{ $student->created_at->format('d/m/Y') }}</span>
            <span class="block text-[10px] text-gray-400 mt-0.5 font-medium">há {{ $student->created_at->diffInDays() }} dias</span>
        </div>

        <div>
            <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Último Acesso</span>
            <span class="block text-sm font-bold text-gray-900 mt-1">{{ $student->updated_at->format('d/m/Y - H:i') }}</span>
        </div>

        <div>
            <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Tipo de Perfil</span>
            <span class="inline-block px-2 py-0.5 mt-0.5 bg-blue-50 text-blue-600 font-bold text-[10px] rounded border border-blue-200">ALUNO (ESTUDANTE)</span>
        </div>
    </x-details-card>

    <div class="grid grid-cols-1 lg:grid-cols-2 mt-6">
        <!-- Vida Acadêmica e Histórico -->
        <div class="bg-white border border-gray-200 shadow-sm rounded-xl p-6">
            <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
                <h2 class="flex items-center gap-2 text-lg font-bold text-gray-900">
                    <i class="ph-fill ph-books text-purpura-500"></i> Vida Acadêmica
                </h2>
                <button class="text-[11px] font-bold text-ponkan-500 hover:text-ponkan-600 transition-colors bg-ponkan-50 px-3 py-1.5 rounded-lg border border-ponkan-100 uppercase">
                    Ver Boletim Geral
                </button>
            </div>
            
            <div class="flex flex-col items-center justify-center p-12 text-center bg-gray-50 border border-gray-200 border-dashed rounded-xl">
                <div class="w-16 h-16 bg-white border border-gray-100 shadow-sm rounded-full flex items-center justify-center mb-3">
                    <i class="text-3xl text-gray-300 ph-fill ph-student"></i>
                </div>
                <h3 class="text-sm font-bold text-gray-800">Módulo Acadêmico Pendente</h3>
                <p class="text-xs font-medium text-gray-500 mt-1 max-w-xs mx-auto">O histórico consolidado de turmas, faltas e notas avaliativas será integrado nesta seção futuramente.</p>
            </div>
        </div>
    </div>
</div>