<div class="p-6 max-w-[1400px] mx-auto font-sans">
    
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => '#'], 
        ['label' => 'Cursos', 'url' => route('cursos.index') ?? '#'], 
        ['label' => 'Detalhes', 'url' => '#']
    ]" />

    <x-details-card 
        title="Ficha do Curso" 
        subtitle="Gestão de configurações, turnos e corpo docente."
        backUrl="{{ route('cursos.index') ?? '#' }}"
        backLabel="Voltar à Lista"
        avatarInitials="{{ strtoupper(substr($this->curso->nome, 0, 2)) }}"
        itemName="{{ $this->curso->nome }}"
        itemDescription="ID: #{{ str_pad($this->curso->id, 4, '0', STR_PAD_LEFT) }} • {{ $this->curso->slug }}">
        
        <x-slot name="badge">
            @if($this->curso->status === 'Ativo')
                <span class="px-3 py-1 bg-green-100 text-green-700 font-bold text-xs rounded-full border border-green-200">CURSO ATIVO</span>
            @else
                <span class="px-3 py-1 bg-red-100 text-red-700 font-bold text-xs rounded-full border border-red-200">INATIVO</span>
            @endif
        </x-slot>
        <div>
            <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Aceita Fora do Estado</span>
            <span class="block text-sm font-bold text-gray-900 mt-1">{{ $this->curso->permite_estado_diferente ? 'Sim' : 'Não' }}</span>
        </div>
        <div>
            <span class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Tipo de Perfil</span>
            <span class="inline-block px-2 py-0.5 mt-0.5 bg-blue-50 text-blue-600 font-bold text-[10px] rounded border border-blue-200">CURSO ACADÊMICO</span>
        </div>
    </x-details-card>

    <!-- Estrutura Inferior de Conteúdo -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        
        <!-- Coluna Esquerda: Unidades e Professores -->
        <div class="space-y-6 lg:col-span-1">
            <div class="bg-white border border-gray-200 shadow-sm rounded-xl p-5">
                <h3 class="text-xs font-bold tracking-wider text-gray-500 uppercase flex items-center gap-2 mb-4 border-b border-gray-100 pb-2">
                    <i class="ph-fill ph-buildings text-lg text-purpura-500"></i> Unidades Presentes
                </h3>
                <div class="flex flex-wrap gap-2">
                    @forelse($this->curso->unidades as $unidade)
                        <span class="px-2.5 py-1 text-xs font-bold text-gray-700 bg-gray-50 rounded-md border border-gray-200">
                            {{ $unidade->nome }}
                        </span>
                    @empty
                        <span class="text-xs text-gray-400">Nenhuma unidade vinculada.</span>
                    @endforelse
                </div>
            </div>

            <div class="bg-white border border-gray-200 shadow-sm rounded-xl p-5">
                <h3 class="text-xs font-bold tracking-wider text-gray-500 uppercase flex items-center gap-2 mb-4 border-b border-gray-100 pb-2">
                    <i class="ph-fill ph-clock text-lg text-amber-500"></i> Turnos Habilitados
                </h3>
                <div class="flex flex-wrap gap-2">
                    @forelse($this->curso->turnosVinculados as $turno)
                        <span class="px-2.5 py-1 text-xs font-bold text-gray-700 bg-gray-50 rounded-md border border-gray-200">
                            {{ $turno->nome }}
                        </span>
                    @empty
                        <span class="text-xs text-gray-400">Nenhum turno vinculado.</span>
                    @endforelse
                </div>
            </div>

            <div class="bg-white border border-gray-200 shadow-sm rounded-xl p-5">
                <h3 class="text-xs font-bold tracking-wider text-gray-500 uppercase flex items-center gap-2 mb-4 border-b border-gray-100 pb-2">
                    <i class="ph-fill ph-chalkboard-teacher text-lg text-ponkan-500"></i> Corpo Docente
                </h3>
                <div class="space-y-3">
                    @forelse($this->professoresVinculados as $professor)
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-xs shrink-0">
                                {{ strtoupper(substr($professor->name, 0, 2)) }}
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900 leading-none">{{ $professor->name }}</p>
                                <p class="text-[10px] text-gray-500 mt-1 uppercase font-bold">{{ $professor->unidades->first()->nome ?? 'Global' }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 italic font-medium">Nenhum professor vinculado.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Coluna Direita: Lista de Inscrições Recentes -->
        <div class="lg:col-span-2">
            <div class="bg-white border border-gray-200 shadow-sm rounded-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                    <h3 class="font-bold text-gray-900 flex items-center gap-2">
                        <i class="ph-fill ph-users-three text-lg text-purpura-500"></i> Últimas Inscrições
                    </h3>
                    @if(feature('inscricao.listar'))
                        @if(auth()->user()->hasRole('dev') || auth()->user()->can('inscricao.listar'))
                            <a href="{{ route('inscricoes.index', ['filtroCurso' => $this->curso->id]) }}" class="text-xs font-bold text-purpura-600 hover:text-purpura-700 hover:underline bg-white border border-gray-200 px-3 py-1.5 rounded-lg shadow-sm">
                                Ver base completa &rarr;
                            </a>
                        @endif
                    @endif
                </div>
                
                <div class="divide-y divide-gray-100">
                    @forelse($this->inscricoesRecentes as $inscricao)
                        <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 font-bold">
                                    {{ strtoupper(substr($inscricao->nome, 0, 2)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900">{{ $inscricao->nome }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $inscricao->email }} • {{ $inscricao->created_at->format('d/m/Y') }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-2.5 py-1 rounded text-[10px] font-bold uppercase bg-gray-100 text-gray-600 border border-gray-200">
                                    Etapa {{ $inscricao->etapa_atual }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-16 text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 mb-3 border border-gray-100 shadow-sm">
                                <i class="ph ph-empty text-3xl text-gray-400"></i>
                            </div>
                            <p class="text-base font-bold text-gray-900">Nenhum aluno matriculado.</p>
                            <p class="text-sm text-gray-500 mt-1 max-w-sm mx-auto">As inscrições e candidaturas para este curso aparecerão de forma resumida aqui.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>