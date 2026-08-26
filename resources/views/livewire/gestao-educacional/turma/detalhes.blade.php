<div class="p-6 max-w-6xl mx-auto font-sans relative">
    
    <x-page-header 
        title="{{ $turmaId ? 'Editar Turma' : 'Nova Turma' }}" 
        icon="ph ph-chalkboard"
        badge="Dossiê Acadêmico">
        
        <x-slot name="actions">
            <a href="{{ route('turmas.index') }}" wire:navigate class="px-4 py-2 text-sm font-bold border rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 flex items-center gap-2">
                <i class="ph-bold ph-arrow-left"></i> Voltar
            </a>
        </x-slot>
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- COLUNA ESQUERDA: FORMULÁRIO --}}
        <div class="lg:col-span-2">
            <form wire:submit.prevent="salvar" class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 space-y-6">
                
                <h3 class="text-lg font-bold border-b border-gray-100 dark:border-gray-700 pb-2 text-gray-800 dark:text-gray-200">Informações Principais</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nome da Turma</label>
                        <input type="text" wire:model="nome" placeholder="Ex: Turma A - Informática" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm font-bold text-sm">
                        @error('nome') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Unidade Polo</label>
                        <select wire:model="unidade_id" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm text-sm">
                            <option value="">Selecione...</option>
                            @foreach($unidades as $uni)
                                <option value="{{ $uni->id }}">{{ $uni->nome }}</option>
                            @endforeach
                        </select>
                        @error('unidade_id') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Curso Vinculado</label>
                        <select wire:model="curso_id" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm text-sm">
                            <option value="">Selecione...</option>
                            @foreach($cursos as $cur)
                                <option value="{{ $cur->id }}">{{ $cur->nome }}</option>
                            @endforeach
                        </select>
                        @error('curso_id') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Turno</label>
                        <select wire:model="turno_id" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm text-sm">
                            <option value="">Selecione...</option>
                            @foreach($turnos as $tur)
                                <option value="{{ $tur->id }}">{{ $tur->nome }} (Início: {{ substr($tur->horario_inicio, 0, 5) }})</option>
                            @endforeach
                        </select>
                        @error('turno_id') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Ciclo Letivo (Semestre)</label>
                        <select wire:model="ciclo_id" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm text-sm">
                            <option value="">Selecione...</option>
                            @foreach($ciclos as $ciclo)
                                <option value="{{ $ciclo->id }}">{{ $ciclo->nome }} ({{ $ciclo->ano }}/{{ $ciclo->semestre }})</option>
                            @endforeach
                        </select>
                        @error('ciclo_id') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Ano Letivo (Referência)</label>
                        <input type="number" wire:model="ano" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm text-sm">
                        @error('ano') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-end pb-2">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="status" class="w-5 h-5 text-purpura-600 border-gray-300 rounded focus:ring-purpura-500 dark:bg-gray-700 dark:border-gray-600">
                            <span class="ml-2 font-bold text-gray-700 dark:text-gray-300 text-sm">Turma Ativa</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-gray-100 dark:border-gray-700">
                    @if(feature('turma.editar') && (auth()->user()->hasRole('dev') || auth()->user()->can('turma.editar')))
                        <button type="submit" class="px-8 py-3 bg-purpura-600 hover:bg-purpura-700 text-white font-black rounded-lg shadow-sm transition flex items-center gap-2">
                            <i class="ph-bold ph-floppy-disk text-lg"></i> Salvar Turma
                        </button>
                    @endif
                </div>
            </form>
        </div>

        {{-- COLUNA DIREITA: INFORMAÇÕES EXTRAS (Só exibe se a turma já existir) --}}
        @if($turmaId)
            <div class="space-y-6">
                
                {{-- Professores --}}
                <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-bold border-b border-gray-100 dark:border-gray-700 pb-2 mb-3 text-gray-800 dark:text-gray-200 flex justify-between items-center">
                        Corpo Docente
                        <span class="bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 px-2 py-0.5 rounded text-[10px]">{{ count($professores) }}</span>
                    </h3>
                    <div class="space-y-2 max-h-48 overflow-y-auto custom-scrollbar">
                        @forelse($professores as $prof)
                            <div class="flex items-center gap-3 p-2 hover:bg-gray-50 dark:hover:bg-gray-900 rounded transition">
                                <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs shrink-0">
                                    <i class="ph-fill ph-chalkboard-teacher"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ $prof->name }}</p>
                                    <p class="text-[10px] text-gray-500">{{ $prof->email }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-gray-500 text-center py-4">Nenhum professor vinculado a esta turma.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Matrículas (Alunos) --}}
                <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-bold border-b border-gray-100 dark:border-gray-700 pb-2 mb-3 text-gray-800 dark:text-gray-200 flex justify-between items-center">
                        Alunos Matriculados
                        <span class="bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 px-2 py-0.5 rounded text-[10px]">{{ count($matriculas) }}</span>
                    </h3>
                    <div class="space-y-2 max-h-64 overflow-y-auto custom-scrollbar">
                        @forelse($matriculas as $mat)
                            <div class="flex items-center gap-3 p-2 hover:bg-gray-50 dark:hover:bg-gray-900 rounded transition border border-transparent hover:border-gray-100 dark:hover:border-gray-700">
                                <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs shrink-0">
                                    {{ substr($mat->student->name ?? 'A', 0, 1) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-gray-800 dark:text-gray-200 truncate">{{ $mat->student->name ?? 'Aluno Removido' }}</p>
                                    <p class="text-[10px] text-gray-500 font-mono mt-0.5">RA: {{ $mat->numero_matricula }}</p>
                                </div>
                                <div>
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider {{ $mat->status == 'ativa' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $mat->status }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-gray-500 text-center py-4">Nenhuma matrícula registrada.</p>
                        @endforelse
                    </div>
                </div>

            </div>
        @else
            <div class="flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 p-10 h-full min-h-[300px]">
                <i class="ph ph-lock-key text-4xl text-gray-400 mb-3"></i>
                <p class="text-sm font-bold text-gray-600 dark:text-gray-400 text-center">Salve a turma primeiro para poder visualizar e gerenciar o corpo docente e as matrículas vinculadas.</p>
            </div>
        @endif
    </div>
</div>