<div class="font-sans flex flex-col transition-all duration-300 bg-gray-50 dark:bg-gray-900 w-full h-full overflow-hidden"
     x-data="{ navbarOculta: false }"
     x-init="
        document.body.classList.add('modo-expandido');
        $watch('navbarOculta', val => document.body.classList.toggle('navbar-oculta', val));
     ">

    <style>
        /* Força o modo expandido na área principal sem quebrar o header/sidebar do sistema */
        body.modo-expandido main > div > div { max-width: 100% !important; padding-left: 0 !important; padding-right: 0 !important; height: 100%; display: flex; flex-direction: column; }
        body.modo-expandido main > div.py-6 { padding-top: 0 !important; padding-bottom: 0 !important; height: 100%; display: flex; flex-direction: column; }
        body.modo-expandido main { overflow: hidden !important; display: flex; flex-direction: column; }
        body.navbar-oculta .js-topnav { display: none !important; }
        body.navbar-oculta header { display: none !important; }
        body.navbar-oculta .js-sidebar { display: none !important; }
    </style>

    <script>
        if (typeof window.abrirAssinaturaBase64 !== 'function') {
            window.abrirAssinaturaBase64 = function(dataUrl) {
                fetch(dataUrl)
                    .then(res => res.blob())
                    .then(blob => {
                        const blobUrl = URL.createObjectURL(blob);
                        window.open(blobUrl, '_blank');
                    });
            };
        }
    </script>
    
    {{-- BARRA SUPERIOR DA PLANILHA --}}
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 sm:px-5 py-2.5 flex flex-wrap items-center justify-between gap-3 shrink-0 z-20">
        <div class="flex items-center gap-3 min-w-0">
            <a href="{{ route('formularios.show', $formulario->id) }}" wire:navigate class="w-8 h-8 flex items-center justify-center rounded-md border border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition shrink-0" title="Voltar para Detalhes">
                <i class="ph ph-arrow-left"></i>
            </a>
            <div class="border-l border-gray-200 dark:border-gray-700 pl-3 min-w-0">
                <h1 class="text-sm font-semibold text-gray-900 dark:text-white truncate max-w-[200px] sm:max-w-md">
                    {{ $formulario->titulo }}
                </h1>
                <span class="text-[11px] text-gray-500 dark:text-gray-400">{{ $respostas->total() }} resposta(s) filtrada(s)</span>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($search || $data_inicio || $data_fim || $filtro_curso || $filtro_unidade || $filtro_turno)
                <button type="button" wire:click="limparFiltros" class="px-2.5 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md hover:bg-gray-50 transition">
                    Limpar filtros
                </button>
            @endif

            <button type="button" @click="navbarOculta = !navbarOculta" class="px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition flex items-center gap-1.5" title="Ocultar ou mostrar menus do sistema">
                <i class="ph" :class="navbarOculta ? 'ph-eye' : 'ph-eye-slash'"></i>
                <span class="hidden sm:inline" x-text="navbarOculta ? 'Mostrar menus' : 'Ocultar menus'"></span>
            </button>
            
            <div x-data="{ openExport: false }" class="relative inline-block text-left">
                <button type="button" @click="openExport = !openExport" @click.away="openExport = false" class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-purpura-600 rounded-md hover:bg-purpura-700 transition">
                    <i class="ph ph-export text-sm"></i> Exportar <i class="ph ph-caret-down text-[10px]"></i>
                </button>
                <div x-show="openExport" x-cloak class="absolute right-0 w-40 mt-1 origin-top-right bg-white border border-gray-200 divide-y divide-gray-100 rounded-md shadow-md z-50 dark:bg-gray-800 dark:border-gray-700">
                    <div class="py-1">
                        <button type="button" wire:click="solicitarExportacao('xlsx')" @click="openExport = false" class="flex items-center w-full px-3 py-2 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 text-left gap-2">
                            <i class="ph ph-file-xls text-base text-gray-500"></i> Excel (.xlsx)
                        </button>
                        <button type="button" wire:click="solicitarExportacao('csv')" @click="openExport = false" class="flex items-center w-full px-3 py-2 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 text-left gap-2">
                            <i class="ph ph-file-csv text-base text-gray-500"></i> CSV (.csv)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BARRA DE FILTROS EM GRADE RESPONSIVA --}}
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 sm:px-5 py-2.5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-2 shrink-0 z-10">
        <div class="relative">
            <i class="ph ph-magnifying-glass text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2"></i>
            <input wire:model.live.debounce.500ms="search" type="text" placeholder="Nome, CPF ou e-mail..." class="pl-8 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-xs py-1.5">
        </div>
        
        <input wire:model.live="data_inicio" type="date" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-xs py-1.5 px-2.5" title="Data inicial">
        <input wire:model.live="data_fim" type="date" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-xs py-1.5 px-2.5" title="Data final">

        <select wire:model.live="filtro_unidade" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-xs py-1.5">
            <option value="">Todas as unidades</option>
            @foreach($unidadesDb as $u) <option value="{{ $u->id }}">{{ Str::limit($u->nome, 24) }}</option> @endforeach
        </select>

        <select wire:model.live="filtro_curso" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-xs py-1.5">
            <option value="">Todos os cursos</option>
            @foreach($cursosDb as $c) <option value="{{ $c->id }}">{{ Str::limit($c->nome, 24) }}</option> @endforeach
        </select>

        <select wire:model.live="filtro_turno" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-xs py-1.5">
            <option value="">Todos os turnos</option>
            @foreach($turnosDb as $t) <option value="{{ $t->id }}">{{ $t->nome }}</option> @endforeach
        </select>
    </div>

    {{-- TABELA DE RESPOSTAS --}}
    <div class="flex-1 overflow-auto bg-gray-50 dark:bg-gray-900 relative custom-scrollbar">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-[11px] md:text-xs">
            <thead class="bg-gray-100 dark:bg-gray-800 sticky top-0 z-10 border-b border-gray-200 dark:border-gray-700">
                <tr>
                    <th wire:click="sortBy('id')" class="px-3 py-2.5 text-left font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-300 dark:hover:bg-gray-700 whitespace-nowrap border-r border-gray-300 dark:border-gray-700 bg-gray-200 dark:bg-gray-800">
                        Resposta
                        @if($sortField === 'id') <i class="ph-bold ph-caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1 text-purpura-600"></i> @endif
                    </th>
                    <th wire:click="sortBy('created_at')" class="px-3 py-2.5 text-left font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-300 dark:hover:bg-gray-700 whitespace-nowrap border-r border-gray-300 dark:border-gray-700 bg-gray-200 dark:bg-gray-800">
                        Data do Envio
                        @if($sortField === 'created_at') <i class="ph-bold ph-caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1 text-purpura-600"></i> @endif
                    </th>
                    
                    @foreach($campos as $campo)
                        <th wire:click="sortBy('respostas->{{ $campo->name }}')" class="px-3 py-2.5 text-left font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-300 dark:hover:bg-gray-700 whitespace-nowrap max-w-[200px] truncate border-r border-gray-300 dark:border-gray-700 bg-gray-200 dark:bg-gray-800" title="{{ $campo->label }}">
                            {{ Str::limit($campo->label, 30) }}
                            @if($sortField === 'respostas->' . $campo->name) <i class="ph-bold ph-caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1 text-purpura-600"></i> @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-white dark:bg-gray-900">
                @forelse($respostas as $resp)
                    @php
                        $respostasSalvas = is_string($resp->respostas) ? json_decode($resp->respostas, true) : ($resp->respostas ?? []);
                    @endphp
                    <tr class="hover:bg-blue-50 dark:hover:bg-gray-800/80 transition group">
                        <td class="px-3 py-2 font-mono text-gray-500 dark:text-gray-400 whitespace-nowrap border-r border-gray-100 dark:border-gray-800 group-hover:border-blue-100">
                            #{{ str_pad($resp->id, 5, '0', STR_PAD_LEFT) }}
                        </td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300 whitespace-nowrap border-r border-gray-100 dark:border-gray-800 group-hover:border-blue-100">
                            {{ $resp->created_at->format('d/m/Y H:i') }}
                        </td>
                        
                        @foreach($campos as $campo)
                            @php
                                $val = $respostasSalvas[$campo->name] ?? null;
                                $valStr = is_array($val) ? implode(' | ', array_filter($val)) : (string) $val;
                                $valStr = empty(trim($valStr)) ? '-' : $valStr;
                            @endphp
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300 max-w-[250px] truncate border-r border-gray-100 dark:border-gray-800" title="{{ !str_starts_with($valStr, 'data:image/') ? $valStr : 'Assinatura Registrada' }}">
                                @if(str_starts_with($valStr, 'data:image/'))
                                    <button type="button"
                                            onclick="window.abrirAssinaturaBase64(@js($valStr))"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-200 border border-gray-200 dark:border-gray-700 rounded text-[11px] font-medium transition whitespace-nowrap cursor-pointer">
                                        <i class="ph ph-signature text-xs"></i> Assinatura
                                    </button>
                                @elseif(preg_match('/\/(uploads|documentos|links)\//', $valStr))
                                    <div class="flex flex-wrap gap-1">
                                        @foreach(explode(' | ', $valStr) as $caminhoArquivo)
                                            @continue(empty(trim($caminhoArquivo)))
                                            <a href="{{ asset('storage/' . trim($caminhoArquivo)) }}" target="_blank" class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-200 border border-gray-200 dark:border-gray-700 rounded text-[11px] font-medium transition whitespace-nowrap">
                                                <i class="ph ph-paperclip text-xs"></i> Arquivo
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    {{ $valStr }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($campos) + 2 }}" class="px-4 py-8 text-center text-gray-500">
                            <i class="ph-fill ph-magnifying-glass text-3xl mb-2 text-gray-300"></i><br>
                            Nenhuma resposta atende aos filtros atuais.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($respostas->hasPages())
        <div class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 px-4 py-2 shrink-0 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
            {{ $respostas->links() }}
        </div>
    @endif
</div>