<div class="p-6 max-w-7xl mx-auto font-sans relative">
    @if (session()->has('sucesso'))
        <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded shadow-sm">
            {{ session('sucesso') }}
        </div>
    @endif

    <x-breadcrumb :items="$breadcrumbs" />
    
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gry-800">Etapas</h2>

        <span class="bg-purple-100 text-purple-800 text-sm font-semibold px-4 py-2 rounded-full border border-purple-200">
            Visão Global (Administrador)
        </span>
    </div>

    @if(isset($metricas))
        <x-summary-cards :metricas="$metricas" />
    @endif

    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 mb-6 flex flex-col md:flex-row gap-4">
        <div class="w-full md:w-1/4">
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1 flex items-center gap-1">
                <i class="ph ph-calendar-check text-purpura-500"></i> Semestre / Ciclo
            </label>
            <select wire:model.live="filtroCiclo" class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                <option value="">Todos os Semestres</option>
                @foreach($ciclosDb as $ciclo) 
                    <option value="{{ $ciclo->id }}">{{ $ciclo->nome }}</option> 
                @endforeach
            </select>
        </div>
        <div class="w-full md:w-1/4">
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1 flex items-center gap-1">
                <i class="ph ph-buildings text-purpura-500"></i> Unidade
            </label>
            <select wire:model.live="filtroUnidade" class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                <option value="">Todas as Unidades</option>
                @foreach($unidadesDb as $u) <option value="{{ $u->id }}">{{ $u->nome }}</option> @endforeach
            </select>
        </div>
        <div class="w-full md:w-1/4">
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1 flex items-center gap-1">
                <i class="ph ph-clock text-purpura-500"></i> Turno
            </label>
            <select wire:model.live="filtroTurno" class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                <option value="">Todos os Turnos</option>
                @foreach($turnosDb as $t) <option value="{{ $t->id }}">{{ $t->nome }}</option> @endforeach
            </select>
        </div>
        <div class="w-full md:w-1/4">
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1 flex items-center gap-1">
                <i class="ph ph-graduation-cap text-purpura-500"></i> Curso
            </label>
            <select wire:model.live="filtroCurso" class="w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                <option value="">Todos os Cursos</option>
                @foreach($cursosDb as $c) <option value="{{ $c->id }}">{{ $c->nome }}</option> @endforeach
            </select>
        </div>
    </div>

    <x-table
        :headers="$this->headers"
        :registros="$registros"
        :ordenacaoCampo="$ordenacaoCampo"
        :ordenacaoDirecao="$ordenacaoDirecao">

        @forelse($registros as $inscricao)
            <tr class="bg-white  hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700 transition-colors">
                <td class="px-6 py-4 font-medium text-gray-900 dark:text-gray-300">#{{ $inscricao->id }}</td>
                <td class="px-6 py-4">
                    <div class="font-bold text-gray-900 dark:text-white">{{ $inscricao->nome }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $inscricao->email }}</div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                    {{ $inscricao->curso->nome ?? 'Não selecionado' }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                    Passo {{ $inscricao->etapa_atual }}
                </td>
                <td class="px-6 py-4">
                    <span class="px-3 py-1 text-xs font-bold rounded-full bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                        {{ $inscricao->statusInscricao->nome ?? 'Pendente' }}
                    </span>
                </td>
                <td class="px-6 py-4 text-right">
                    <button class="p-2 text-gray-400 transition-colors rounded-lg hover:text-purpura-600 hover:bg-purpura-50 dark:hover:bg-gray-600" title="Ver Detalhes">
                        <i class="text-xl ph ph-eye"></i>
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                    <p class="text-lg font-semibold">Nenhuma inscrição encontrada.</p>
                    <p class="text-sm">Ajuste os filtros ou aguarde novos candidatos.</p>
                </td>
            </tr>   
        @endforelse
    
    </x-table>

    <div x-data="{ show: false, msg: '' }" 
        @etapa-salva.window="show = true; msg = $event.detail.msg; setTimeout(() => show = false, 3500);"
        x-show="show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-10" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-10"
        class="fixed bottom-8 right-8 bg-green-600 text-white px-6 py-4 rounded-xl shadow-2xl z-[200] flex items-center gap-3 font-bold" x-cloak>
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        <span x-text="msg"></span>
    </div>
</div>