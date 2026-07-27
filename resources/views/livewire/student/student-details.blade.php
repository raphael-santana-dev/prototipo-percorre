<div class="space-y-6">
    <!-- Cabeçalho de Navegação -->
    <div class="flex items-center justify-between">
        <a href="{{ route('students.index') }}" class="flex items-center gap-2 text-sm font-bold text-gray-500 transition-colors hover:text-purpura-600 dark:text-gray-400">
            <i class="ph-bold ph-arrow-left"></i> Voltar para a lista
        </a>
        
        @can('estudante.editar')
            <button class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-white transition-colors rounded-lg bg-purpura-500 hover:bg-purpura-600">
                <i class="ph-bold ph-pencil-simple"></i> Editar Matrícula
            </button>
        @endcan
    </div>

    <!-- Hero Section do Perfil -->
    <div class="relative overflow-hidden bg-white border border-gray-200 rounded-2xl shadow-gray-2 dark:bg-gray-800 dark:border-gray-700">
        <!-- Fundo Gradiente Superior -->
        <div class="h-32 bg-gradient-to-r from-petunia-900 to-purpura-500 sm:h-40"></div>
        
        <div class="px-6 pb-6 sm:px-8">
            <div class="relative flex flex-col sm:flex-row sm:items-end gap-6 -mt-12 sm:-mt-16">
                <!-- Avatar -->
                <div class="relative p-1 bg-white rounded-full dark:bg-gray-800 w-fit">
                    <img class="object-cover w-24 h-24 border-4 border-gray-100 rounded-full sm:w-32 sm:h-32 dark:border-gray-700" 
                         src="https://ui-avatars.com/api/?name={{ urlencode($student->name) }}&background=F4E8FF&color=9B26B6&size=256&bold=true" 
                         alt="Foto de perfil">
                    
                    <!-- Indicador de Status na foto -->
                    <div class="absolute bottom-2 right-2 w-5 h-5 border-4 border-white rounded-full dark:border-gray-800 {{ $student->is_active ? 'bg-pistache-500' : 'bg-gray-400' }}" title="{{ $student->is_active ? 'Ativo' : 'Inativo' }}"></div>
                </div>

                <!-- Título e Infos Rápidas -->
                <div class="flex-1 pb-2">
                    <h1 class="text-2xl font-extrabold text-gray-900 truncate sm:text-3xl dark:text-white">{{ $student->name }}</h1>
                    <div class="flex flex-wrap items-center gap-4 mt-2 text-sm font-medium text-gray-500 dark:text-gray-400">
                        <span class="flex items-center gap-1"><i class="text-lg ph ph-envelope-simple"></i> {{ $student->email }}</span>
                        <span class="flex items-center gap-1"><i class="text-lg ph ph-map-pin"></i> Unidade: {{ $student->unidade?->nome ?? 'Não alocado' }}</span>
                        <span class="flex items-center gap-1"><i class="text-lg ph ph-calendar-blank"></i> Matriculado em {{ $student->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Layout Grid com Informações Detalhadas -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        
        <!-- Coluna Esquerda: Dados de Cadastro -->
        <div class="space-y-6 lg:col-span-1">
            <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-gray-1 dark:bg-gray-800 dark:border-gray-700">
                <h2 class="flex items-center gap-2 mb-4 text-lg font-bold text-gray-900 dark:text-white border-b border-gray-100 pb-2 dark:border-gray-700">
                    <i class="ph ph-identification-card text-purpura-500"></i> Informações Pessoais
                </h2>
                
                <dl class="space-y-4">
                    <div>
                        <dt class="text-xs font-bold tracking-wider text-gray-500 uppercase">Status da Matrícula</dt>
                        <dd class="mt-1">
                            @if($student->is_active)
                                <span class="inline-flex px-2 py-1 text-xs font-bold text-pistache-700 bg-pistache-100 rounded-full uppercase">Cursando / Ativo</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-bold text-gray-500 bg-gray-200 rounded-full uppercase">Inativo</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold tracking-wider text-gray-500 uppercase">E-mail Principal</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $student->email }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Coluna Direita: Relatórios e Relacionamentos (Placeholders para o futuro) -->
        <div class="space-y-6 lg:col-span-2">
            <!-- Bloco de Turmas / Cursos -->
            <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-gray-1 dark:bg-gray-800 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-2 dark:border-gray-700">
                    <h2 class="flex items-center gap-2 text-lg font-bold text-gray-900 dark:text-white">
                        <i class="ph ph-books text-purpura-500"></i> Vida Acadêmica
                    </h2>
                    <button class="text-sm font-bold text-ponkan-500 hover:text-ponkan-600 transition-colors">Ver Boletim</button>
                </div>
                
                <div class="flex flex-col items-center justify-center p-8 text-center bg-gray-50 border border-gray-100 border-dashed rounded-xl dark:bg-gray-800/50 dark:border-gray-700">
                    <i class="text-4xl text-gray-300 ph ph-student dark:text-gray-600 mb-2"></i>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">O módulo de turmas e notas será integrado nesta seção futuramente.</p>
                </div>
            </div>
        </div>
        
    </div>
</div>