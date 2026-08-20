<div class="p-6 max-w-6xl mx-auto font-sans relative">
    
    <x-page-header 
        title="{{ $matriculaId ? 'Editar Matrícula' : 'Nova Matrícula' }}" 
        icon="ph ph-identification-card"
        badge="Secretaria">
        
        <x-slot name="actions">
            <a href="{{ route('matriculas.index') }}" wire:navigate class="px-4 py-2 text-sm font-bold border rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 flex items-center gap-2">
                <i class="ph-bold ph-arrow-left"></i> Voltar
            </a>
        </x-slot>
    </x-page-header>

    <form wire:submit.prevent="salvar" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- COLUNA ESQUERDA: DADOS BASE --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold border-b border-gray-100 dark:border-gray-700 pb-2 mb-4 text-gray-800 dark:text-gray-200">Estrutura da Matrícula</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">RA / Nº Matrícula</label>
                        <input type="text" wire:model="numero_matricula" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm font-black text-purpura-600 text-sm">
                        @error('numero_matricula') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Estudante Vinculado</label>
                        <select wire:model="student_id" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm text-sm" @if($matriculaId) disabled @endif>
                            <option value="">Selecione o Aluno...</option>
                            @foreach($estudantes as $est)
                                <option value="{{ $est->id }}">{{ $est->name }} (CPF: {{ $est->cpf }})</option>
                            @endforeach
                        </select>
                        @error('student_id') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Curso</label>
                        <select wire:model="curso_id" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm text-sm">
                            <option value="">Selecione...</option>
                            @foreach($cursos as $cur)
                                <option value="{{ $cur->id }}">{{ $cur->nome }}</option>
                            @endforeach
                        </select>
                        @error('curso_id') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Status da Matrícula</label>
                        <select wire:model="status" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm font-bold text-sm">
                            <option value="ativa">Ativa (Cursando)</option>
                            <option value="concluida">Concluída (Formado)</option>
                            <option value="trancada">Trancada</option>
                            <option value="cancelada">Cancelada</option>
                        </select>
                        @error('status') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Unidade / Polo</label>
                        <select wire:model="unidade_id" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm text-sm">
                            <option value="">Selecione...</option>
                            @foreach($unidades as $uni)
                                <option value="{{ $uni->id }}">{{ $uni->nome }}</option>
                            @endforeach
                        </select>
                        @error('unidade_id') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Turno</label>
                        <select wire:model="turno_id" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-purpura-500 shadow-sm text-sm">
                            <option value="">Selecione...</option>
                            @foreach($turnos as $tur)
                                <option value="{{ $tur->id }}">{{ $tur->nome }}</option>
                            @endforeach
                        </select>
                        @error('turno_id') <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end pt-2">
                <button type="submit" class="px-8 py-3 bg-purpura-600 hover:bg-purpura-700 text-white font-black rounded-lg shadow-sm transition flex items-center gap-2">
                    <i class="ph-bold ph-floppy-disk text-lg"></i> Salvar Matrícula
                </button>
            </div>
        </div>

        {{-- COLUNA DIREITA: VÍNCULOS COM TURMAS --}}
        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 h-full">
                <h3 class="text-sm font-bold border-b border-gray-100 dark:border-gray-700 pb-2 mb-4 text-gray-800 dark:text-gray-200">
                    Enturmação (Vínculo de Turmas)
                </h3>
                
                <p class="text-[11px] text-gray-500 dark:text-gray-400 mb-4">
                    Marque as turmas das quais este aluno faz parte. Isso permitirá que os professores o visualizem nas matrizes de avaliação.
                </p>

                <div class="space-y-2 max-h-[350px] overflow-y-auto custom-scrollbar pr-2">
                    @foreach($turmasDisponiveis as $turmaBox)
                        <label class="flex items-start space-x-3 p-3 border border-gray-100 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-900 transition cursor-pointer">
                            <input type="checkbox" wire:model="turmas_selecionadas" value="{{ $turmaBox->id }}" class="mt-0.5 rounded text-purpura-600 focus:ring-purpura-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                            <div>
                                <span class="block text-sm font-bold text-gray-800 dark:text-gray-200">{{ $turmaBox->nome }}</span>
                                <span class="block text-[10px] text-gray-400">Ano Referência: {{ $turmaBox->ano }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('turmas_selecionadas') <span class="text-red-500 text-xs block font-bold mt-2">{{ $message }}</span> @enderror
            </div>
        </div>
    </form>
</div>