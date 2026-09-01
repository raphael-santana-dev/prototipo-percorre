<div class="p-6 max-w-7xl mx-auto font-sans relative">

    <x-page-header 
        title="Gerenciador de Integrações (I/O)" 
        icon="ph ph-arrows-left-right"
        badge=""
        :breadcrumbs="$breadcrumbs ?? []">

        <x-slot name="filters">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 flex items-center gap-1">
                        <i class="ph ph-files text-purpura-500"></i> Tipo de Registro
                    </label>
                    <select wire:model.live="filtro_tipo" class="w-full rounded-md border-gray-300 shadow-sm px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                        <option value="">Todos</option>
                        <option value="inscricoes">Base de Inscrições</option>
                        <option value="usuarios">Usuários do Sistema</option>
                        <option value="campos">Blocos de Formulário</option>
                        <option value="unidades">Unidades / Sedes</option>
                        <option value="cursos">Cursos Ativos</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 flex items-center gap-1">
                        <i class="ph ph-activity text-purpura-500"></i> Status
                    </label>
                    <select wire:model.live="filtro_status" class="w-full rounded-md border-gray-300 shadow-sm px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                        <option value="">Todos</option>
                        <option value="mapeamento">Mapeamento</option>
                        <option value="na_fila">Na Fila</option>
                        <option value="processando">Processando</option>
                        <option value="concluido">Concluído</option>
                        <option value="erro_parcial">Concluído c/ Alertas</option>
                        <option value="erro">Falha Crítica</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 flex items-center gap-1">
                        <i class="ph ph-user text-purpura-500"></i> Usuário Responsável
                    </label>
                    <select wire:model.live="filtro_usuario" class="w-full rounded-md border-gray-300 shadow-sm px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                        <option value="">Todos os Usuários</option>
                        @foreach($usuariosDisponiveis as $id => $nome)
                            <option value="{{ $id }}">{{ Str::limit($nome, 20) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 flex items-center gap-1">
                        <i class="ph ph-calendar-plus text-purpura-500"></i> De (Data)
                    </label>
                    <input type="datetime-local" wire:model.live="filtro_data_inicio" class="w-full rounded-md border-gray-300 shadow-sm px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 flex items-center gap-1">
                        <i class="ph ph-calendar-check text-purpura-500"></i> Até (Data)
                    </label>
                    <input type="datetime-local" wire:model.live="filtro_data_fim" class="w-full rounded-md border-gray-300 shadow-sm px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                </div>
                @if($filtro_tipo !== '' || $filtro_status !== '' || $filtro_usuario !== '' || $filtro_data_inicio !== '' || $filtro_data_fim !== '')
                    <div class="md:col-span-12 flex justify-end mt-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button wire:click="limparFiltros" class="px-4 py-2 text-sm font-bold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-2 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            <i class="ph-bold ph-x"></i> Limpar Filtros
                        </button>
                    </div>
                @endif
            </div>
        </x-slot>

        <x-slot name="actions">
            @if(feature('importacao.exportar') && (auth()->user()->hasRole('dev') || auth()->user()->can('importacao.exportar')))
                <div x-data="{ openExport: false }" class="relative inline-block text-left mr-2">
                    <button @click="openExport = !openExport" @click.away="openExport = false" class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50">
                        <i class="text-lg ph ph-export"></i> Exportar Dados <i class="ph ph-caret-down"></i>
                    </button>
                    <div x-show="openExport" x-cloak class="absolute right-0 w-56 mt-2 origin-top-right bg-white border border-gray-200 divide-y divide-gray-100 rounded-md shadow-lg z-50">
                        <div class="py-1">
                            <button wire:click="solicitarExportacao('inscricoes', 'xlsx')" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-purpura-600 font-medium text-left">
                                Base de Dados de Inscrições
                            </button>
                            <button wire:click="solicitarExportacao('usuarios', 'csv')" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-purpura-600 font-medium text-left">
                                Lista de Usuários Internos
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            @if(feature('importacao.acessar') && (auth()->user()->hasRole('dev') || auth()->user()->can('importacao.acessar')))
                <div x-data="{ openTemplate: false }" class="relative inline-block text-left mr-2">
                    <button @click="openTemplate = !openTemplate" @click.away="openTemplate = false" class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50">
                        <i class="text-lg ph ph-download-simple"></i> Planilhas Modelo <i class="ph ph-caret-down"></i>
                    </button>
                    <div x-show="openTemplate" x-cloak class="absolute right-0 w-64 mt-2 origin-top-right bg-white border border-gray-200 divide-y divide-gray-100 rounded-md shadow-lg z-50">
                        <div class="py-1">
                            <button wire:click="baixarTemplate('inscricoes')" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-purpura-600 gap-2 font-medium">
                                <i class="ph ph-file-csv text-lg text-green-600"></i> Modelo: Inscrições
                            </button>
                        </div>
                    </div>
                </div>
                
                <button wire:click="abrirModalUpload" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600 font-bold text-sm">
                    <i class="ph ph-upload-simple text-lg"></i> Nova Importação
                </button>
            @endif
        </x-slot>

    </x-page-header>

    <div wire:poll.5s>
        <x-table 
            :headers="$this->headers" 
            :registros="$registros"
            :ordenacaoCampo="$ordenacaoCampo"
            :ordenacaoDirecao="$ordenacaoDirecao"
            :permiteGrid="$permiteGrid"
            :modoExibicao="$modoExibicao">
            
            @forelse($registros as $log)
                @php $visual = $log->status_visual; @endphp
                <tr class="hover:bg-gray-50 transition-colors duration-200">
                    <td class="px-4 py-2.5 whitespace-nowrap text-sm font-medium text-gray-500">
                        #{{ $log->id }}
                    </td>
                    <td class="px-4 py-2.5 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-gray-50 rounded-lg border border-gray-200">
                                <i class="ph-fill ph-file text-2xl {{ $log->formato_icone }}"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-gray-900 truncate max-w-[200px]">{{ $log->arquivo_nome ?? 'Exportação de Sistema' }}</span>
                                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">{{ $log->operacao }} • {{ $log->tipo }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-2.5">
                        <div class="flex flex-col gap-1.5 w-full max-w-xs">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold flex items-center gap-1.5 px-2 py-0.5 rounded border {{ $visual['cor'] }}">
                                    <i class="text-sm {{ $visual['icone'] }}"></i> {{ $visual['label'] }}
                                </span>
                                <span class="font-bold text-gray-600">{{ $log->progresso }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-1.5 overflow-hidden">
                                <div class="h-1.5 rounded-full transition-all duration-500 bg-purpura-500" style="width: {{ $log->progresso }}%"></div>
                            </div>
                            <span class="text-[10px] text-gray-400 font-medium">{{ number_format($log->linhas_processadas, 0, ',', '.') }} de {{ number_format($log->total_linhas, 0, ',', '.') }} linhas processadas</span>
                        </div>
                    </td>
                    <td class="px-4 py-2.5 whitespace-nowrap">
                        <div class="text-sm font-bold text-gray-800">{{ $log->created_at->format('d/m/Y H:i') }}</div>
                        <div class="text-[10px] font-bold text-gray-500 flex items-center gap-1 mt-0.5"><i class="ph-fill ph-user"></i> {{ $log->user->name ?? 'Sistema' }}</div>
                    </td>
                    <td class="px-4 py-2.5 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-1">
                            <button wire:click="verDetalhes({{ $log->id }})" class="p-1.5 text-gray-500 transition-colors rounded hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-gray-700" title="Ver Relatório">
                                <i class="text-lg ph-fill ph-info"></i>
                            </button>

                            @if(in_array($log->status, ['erro', 'erro_parcial']) && $log->operacao === 'importacao')
                                <button wire:click="abrirModalReprocessar({{ $log->id }})" class="p-1.5 text-orange-500 transition-colors rounded hover:text-orange-600 hover:bg-orange-50 dark:hover:bg-gray-700" title="Reprocessar Importação">
                                    <i class="text-lg ph-bold ph-arrows-clockwise"></i>
                                </button>
                            @endif

                            @if($log->operacao === 'exportacao' && $log->status === 'concluido' && $log->arquivo_gerado_caminho)
                                <button wire:click="baixarExportacao({{ $log->id }})" class="p-1.5 text-green-600 transition-colors rounded hover:bg-green-50" title="Baixar Planilha Gerada">
                                    <i class="text-lg ph-bold ph-download-simple"></i>
                                </button>
                            @endif

                            <button wire:click="excluirImportacao({{ $log->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50" title="Excluir Registro" onclick="confirm('Excluir este log e apagar os arquivos do servidor?') || event.stopImmediatePropagation()">
                                <i class="text-lg ph ph-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center text-gray-500 text-sm border-t border-gray-100">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3 border border-gray-200">
                            <i class="ph ph-files text-3xl text-gray-400"></i>
                        </div>
                        <p class="font-bold text-gray-600">Nenhuma importação ou exportação encontrada.</p>
                    </td>
                </tr>
            @endforelse
        </x-table>
    </div>

    <!-- MODAL 1: NOVO UPLOAD -->
    @if($modalUploadAberto)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="$set('modalUploadAberto', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <h3 class="mb-4 text-lg font-bold text-gray-900 border-b border-gray-100 pb-2 flex items-center gap-2">
                        <i class="ph-fill ph-upload-simple text-purpura-500 text-xl"></i> Adicionar à Fila
                    </h3>
                    
                    <form wire:submit.prevent="processarUpload" class="space-y-5">
                        
                        <div>
                            <label class="block mb-1 text-xs font-bold text-gray-700 uppercase tracking-wider">O que você vai importar?</label>
                            <select wire:model.live="tipoImportacao" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500 font-medium">
                                <option value="">Selecione uma opção...</option>
                                <option value="inscricoes">Inscrições de Estudantes</option>
                                <option value="usuarios">Acessos: Usuários Administrativos</option>
                                <option value="campos">Estrutura: Blocos de Formulário</option>
                                <option value="unidades">Cadastros: Unidades / Sedes</option>
                                <option value="cursos">Cadastros: Cursos Ativos</option>
                            </select>
                        </div>

                        @if(in_array($tipoImportacao, ['campos', 'inscricoes']))
                            <div>
                                <label class="block mb-1 text-xs font-bold text-gray-700 uppercase tracking-wider">Ciclo Vinculado <span class="text-red-500">*</span></label>
                                <select wire:model="cicloSelecionadoId" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500">
                                    <option value="">Selecione o Ciclo...</option>
                                    @foreach($ciclosDisponiveis as $ciclo)
                                        <option value="{{ $ciclo->id }}">{{ $ciclo->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                            <label class="block text-xs font-bold text-gray-800 mb-2 uppercase tracking-wider">Arquivo de Dados</label>
                            
                            <div class="flex items-center justify-center w-full">
                                <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-purpura-300 bg-purpura-50 rounded-lg cursor-pointer hover:bg-purpura-100 transition">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6 text-purpura-600">
                                        <i class="ph ph-file-csv text-3xl mb-1"></i>
                                        <p class="text-xs font-bold">Clique ou arraste o arquivo</p>
                                        <p class="text-[10px] mt-1 font-medium text-purpura-500">.CSV, .XLSX, .JSON ou .XML (Máx: 50MB)</p>
                                    </div>
                                    <input type="file" wire:model="arquivo" class="hidden" accept=".csv, .xlsx, .xls, .json, .xml">
                                </label>
                            </div>
                            
                            <div wire:loading wire:target="arquivo" class="mt-2 text-xs font-bold text-purpura-600 flex items-center justify-center gap-2">
                                <i class="ph ph-spinner animate-spin text-lg"></i> Analisando arquivo...
                            </div>
                            
                            @if($arquivo)
                                <div class="mt-3 bg-white p-2 border border-green-200 rounded-md flex items-center gap-2 text-green-700 text-xs font-bold shadow-sm">
                                    <i class="ph-fill ph-check-circle text-lg"></i> {{ $arquivo->getClientOriginalName() }} anexado.
                                </div>
                            @endif
                            @error('arquivo') <span class="text-xs text-red-500 mt-2 block font-bold text-center">{{ $message }}</span> @enderror
                        </div>

                        @if($tipoImportacao === 'inscricoes')
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                                <label class="flex items-start gap-3 p-3 border border-purpura-200 bg-purpura-50/50 rounded-lg cursor-pointer hover:bg-purpura-50 transition">
                                    <div class="flex items-center h-5 mt-0.5">
                                        <input type="checkbox" wire:model="permitirAutoCadastro" class="h-4 w-4 text-purpura-600 rounded border-gray-300 focus:ring-purpura-500">
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-sm text-purpura-900 font-bold">Auto-cadastrar Vínculos</span>
                                        <span class="text-[10px] text-purpura-600 leading-tight mt-0.5">Cria cadastros no sistema se não existirem.</span>
                                    </div>
                                </label>

                                <label class="flex items-start gap-3 p-3 border border-blue-200 bg-blue-50/50 rounded-lg cursor-pointer hover:bg-blue-50 transition">
                                    <div class="flex items-center h-5 mt-0.5">
                                        <input type="checkbox" wire:model="mesclarDuplicadas" class="h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-sm text-blue-900 font-bold">Mesclar Duplicadas</span>
                                        <span class="text-[10px] text-blue-600 leading-tight mt-0.5">Atualiza CPFs que já existem em vez de pular.</span>
                                    </div>
                                </label>
                            </div>
                        @endif

                        <div class="flex justify-end gap-3 pt-4 mt-2 border-t border-gray-100">
                            <button type="button" wire:click="$set('modalUploadAberto', false)" class="px-4 py-2.5 text-sm font-bold border rounded-lg text-gray-600 hover:bg-gray-50 transition">Cancelar</button>
                            <button type="submit" class="px-6 py-2.5 text-sm font-bold text-white rounded-lg shadow-sm bg-purpura-600 hover:bg-purpura-700 transition flex items-center gap-2">
                                Avançar <i class="ph-bold ph-arrow-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL 2: AUTO-MAPEAMENTO -->
    @if($modalMapeamentoAberto)
        <div class="fixed inset-0 z-[60] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/80 backdrop-blur-sm"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6">
                    
                    <div class="flex justify-between items-center mb-6 border-b border-gray-100 pb-4">
                        <div>
                            <h3 class="text-xl font-extrabold text-gray-900 flex items-center gap-2"><i class="ph-fill ph-git-merge text-ponkan-500"></i> Conferência de Mapeamento</h3>
                            <p class="text-sm text-gray-500 font-medium">Nossa IA tentou cruzar as colunas da sua planilha com os campos do banco de dados.</p>
                        </div>
                        <span class="bg-blue-100 text-blue-700 font-bold px-3 py-1 rounded text-xs">Etapa 2 de 2</span>
                    </div>
                    
                    <div class="max-h-[50vh] overflow-y-auto custom-scrollbar pr-2 space-y-3">
                        @foreach($mapeamento as $index => $item)
                            <div class="flex items-center gap-4 bg-gray-50 p-3 rounded-lg border border-gray-200" wire:key="map-{{ $index }}">
                                <div class="w-1/3 flex items-center gap-2">
                                    <i class="ph-fill ph-file-xls text-gray-400 text-xl"></i>
                                    <span class="text-sm font-bold text-gray-700 truncate" title="{{ $item['coluna_nome'] }}">{{ Str::limit($item['coluna_nome'], 25) }}</span>
                                </div>
                                <div class="text-gray-400"><i class="ph-bold ph-arrow-right"></i></div>
                                <div class="w-1/3">
                                    <select wire:model="mapeamento.{{ $index }}.destino" class="w-full text-xs font-bold rounded bg-white border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500">
                                        <option value="ignorar" class="text-red-500">-- Ignorar Coluna --</option>
                                        <option value="dados_dinamicos" class="text-blue-600">-- Salvar no JSON Bruto Oculto --</option>
                                        <optgroup label="Campos Nativos do Sistema">
                                            @foreach($opcoesMapeamento as $chave => $label)
                                                <option value="{{ $chave }}">{{ $label }}</option>
                                            @endforeach
                                        </optgroup>
                                        @if(!empty($camposDinamicosDisponiveis))
                                            <optgroup label="Campos Customizados (Formulário)">
                                                @foreach($camposDinamicosDisponiveis as $name => $label)
                                                    <option value="dinamico:{{ $name }}">{{ $label }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endif
                                    </select>
                                </div>
                                <div class="w-1/4">
                                    <select wire:model="mapeamento.{{ $index }}.tipo" class="w-full text-xs font-medium rounded bg-white border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500">
                                        <option value="texto">Formato: Texto / Número</option>
                                        <option value="data">Formato: Data</option>
                                        <option value="monetario">Formato: Monetário</option>
                                        <option value="booleano">Formato: Booleano</option>
                                    </select>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex justify-end gap-3 pt-6 mt-4 border-t border-gray-100">
                        <button type="button" wire:click="excluirImportacao({{ $importacaoAtualId }})" class="px-4 py-2.5 text-sm font-bold border rounded-lg text-gray-600 hover:bg-gray-50 transition">Cancelar e Apagar Arquivo</button>
                        <button type="button" wire:click="iniciarImportacao" class="px-6 py-2.5 text-sm font-bold text-white rounded-lg shadow-sm bg-ponkan-500 hover:bg-ponkan-600 transition flex items-center gap-2">
                            <i class="ph-bold ph-rocket-launch"></i> Confirmar e Enviar para a Fila
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL 3: DETALHES E LOG DA IMPORTAÇÃO -->
    @if($modalDetalhesAberto && $importacaoDetalhes)
        <div class="fixed inset-0 z-[70] overflow-y-auto" x-data="{ fullscreen: false, tab: 'logs' }">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="$set('modalDetalhesAberto', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div :class="fullscreen ? 'fixed inset-0 z-50 flex flex-col bg-white' : 'relative z-10 inline-block w-full max-w-5xl my-8 overflow-hidden text-left align-bottom transition-all transform bg-white shadow-2xl rounded-xl sm:align-middle'"
                     class="px-4 pt-5 pb-4 sm:p-6">
                    
                    @php 
                        $erros = json_decode($importacaoDetalhes->erro_mensagem, true) ?? []; 
                        $isDev = auth()->user()->hasRole('dev');
                        $totalProcessado = $importacaoDetalhes->linhas_processadas;
                        $totalFalhas = count($erros);
                        $totalSucesso = max(0, $totalProcessado - $totalFalhas);
                    @endphp

                    <div class="flex justify-between items-start mb-4 border-b border-gray-100 pb-4 shrink-0">
                        <div>
                            <h3 class="text-xl font-extrabold text-gray-900 flex items-center gap-2">
                                <i class="ph-fill ph-file-text text-purpura-500"></i> Relatório de Operação #{{ $importacaoDetalhes->id }}
                            </h3>
                            <p class="text-xs text-gray-500 font-medium mt-1">Nome do Arquivo: <b class="text-gray-700">{{ $importacaoDetalhes->arquivo_nome ?? 'Sem arquivo' }}</b></p>
                        </div>
                        <div class="flex gap-2 items-center">
                            @if(count($erros) > 0 && $importacaoDetalhes->operacao === 'importacao')
                                <button wire:click="baixarErros({{ $importacaoDetalhes->id }})" class="px-3 py-1.5 bg-red-50 border border-red-200 hover:bg-red-100 text-red-600 text-xs font-bold rounded-lg flex items-center gap-2 transition shadow-sm mr-2">
                                    <i class="ph-bold ph-download-simple"></i> Baixar Linhas com Erro
                                </button>
                            @endif
                            @if($importacaoDetalhes->operacao === 'importacao')
                                <button wire:click="baixarArquivoOriginal({{ $importacaoDetalhes->id }})" class="px-3 py-1.5 bg-gray-50 border border-gray-200 hover:bg-gray-100 text-gray-700 text-xs font-bold rounded-lg flex items-center gap-2 transition shadow-sm">
                                    <i class="ph-bold ph-download-simple"></i> Original
                                </button>
                            @endif
                            <button @click="fullscreen = !fullscreen" class="p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-800 rounded-lg transition ml-1">
                                <i class="text-2xl ph" :class="fullscreen ? 'ph-corners-in' : 'ph-corners-out'"></i>
                            </button>
                            <button wire:click="$set('modalDetalhesAberto', false)" class="p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-500 rounded-lg transition ml-1">
                                <i class="text-2xl ph-bold ph-x"></i>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4 shrink-0">
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 flex flex-col justify-center">
                            <span class="text-[10px] uppercase font-bold text-gray-500 block mb-1">Status Final</span>
                            <span class="text-xs font-bold px-2 py-0.5 rounded border {{ $importacaoDetalhes->status_visual['cor'] }} inline-flex items-center gap-1 w-max">
                                <i class="{{ $importacaoDetalhes->status_visual['icone'] }}"></i> {{ $importacaoDetalhes->status_visual['label'] }}
                            </span>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                            <span class="text-[10px] uppercase font-bold text-gray-500 block mb-1">Linhas Lidas</span>
                            <span class="text-lg font-bold text-gray-900">{{ number_format($totalProcessado, 0, '', '.') }} <span class="text-[10px] text-gray-400 font-medium">/ {{ number_format($importacaoDetalhes->total_linhas, 0, '', '.') }}</span></span>
                        </div>
                        <div class="bg-green-50 p-3 rounded-lg border border-green-200">
                            <span class="text-[10px] uppercase font-bold text-green-700 block mb-1">Sucessos (Inseridos)</span>
                            <span class="text-lg font-bold text-green-800">{{ number_format($totalSucesso, 0, '', '.') }}</span>
                        </div>
                        <div class="bg-red-50 p-3 rounded-lg border border-red-200">
                            <span class="text-[10px] uppercase font-bold text-red-700 block mb-1">Alertas / Falhas</span>
                            <span class="text-lg font-bold text-red-800">{{ number_format($totalFalhas, 0, '', '.') }}</span>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                            <span class="text-[10px] uppercase font-bold text-gray-500 block mb-1">Data</span>
                            <span class="text-sm font-bold text-gray-900">{{ $importacaoDetalhes->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-6 mb-2 border-b border-gray-100 shrink-0">
                        <button @click="tab = 'logs'" :class="tab === 'logs' ? 'text-purpura-600 border-b-2 border-purpura-600' : 'text-gray-500 hover:text-gray-700'" class="pb-2 font-bold text-sm transition-colors flex items-center gap-1">
                            <i class="ph-bold ph-terminal-window"></i> Eventos e Logs do Sistema
                        </button>
                    </div>
                    
                    <div x-show="tab === 'logs'" class="bg-gray-900 text-gray-300 rounded-lg text-xs shadow-inner border border-gray-800 flex-1 flex flex-col overflow-hidden" :class="fullscreen ? 'h-full min-h-[300px]' : 'h-64'">
                        <div class="overflow-y-auto custom-scrollbar flex-1">
                            @if(empty($erros) && $importacaoDetalhes->status === 'concluido')
                                <div class="p-8 text-center text-gray-500 italic flex flex-col items-center justify-center h-full">
                                    <i class="ph-fill ph-check-circle text-4xl mb-2 text-green-500"></i>
                                    <span class="font-bold text-gray-400 text-sm">Operação Perfeita</span>
                                    Nenhum alerta foi registrado.
                                </div>
                            @elseif(empty($erros))
                                <div class="p-8 text-center text-gray-500 italic flex flex-col items-center justify-center h-full">
                                    <i class="ph-fill ph-hourglass-high text-4xl mb-2 text-gray-600"></i>
                                    Aguardando processamento...
                                </div>
                            @else
                                <table class="w-full text-left border-collapse">
                                    <thead class="bg-gray-950 sticky top-0 border-b border-gray-700 shadow-sm z-10">
                                        <tr>
                                            <th class="p-3 font-bold uppercase tracking-wider text-[10px] text-gray-400 w-16 text-center">Linha</th>
                                            <th class="p-3 font-bold uppercase tracking-wider text-[10px] text-gray-400 w-40">Classificação</th>
                                            <th class="p-3 font-bold uppercase tracking-wider text-[10px] text-gray-400">Mensagem do Sistema</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800">
                                        @foreach($erros as $erro)
                                            @php
                                                $tipoErro = $erro['tipo'] ?? 'Geral';
                                                $corSelo = 'bg-red-500/20 text-red-400 border-red-500/30';
                                                if (str_contains($tipoErro, 'Alerta') || str_contains($tipoErro, 'Duplicata')) {
                                                    $corSelo = 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30';
                                                }
                                            @endphp
                                            <tr class="hover:bg-gray-800/50 transition-colors">
                                                <td class="p-3 text-center text-gray-500 font-mono">[{{ $erro['linha'] ?? '-' }}]</td>
                                                <td class="p-3"><span class="px-2 py-0.5 border text-[10px] font-bold rounded {{ $corSelo }}">{{ $tipoErro }}</span></td>
                                                <td class="p-3 font-medium text-gray-300 whitespace-pre-line">{{ $isDev ? ($erro['mensagem'] ?? 'Erro desconhecido') : ($erro['amigavel'] ?? $erro['mensagem'] ?? 'Falha ao processar registro.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL 4: ESCOLHA DE REPROCESSAMENTO -->
    @if($modalReprocessarAberto)
        <div class="fixed inset-0 z-[80] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/70 backdrop-blur-sm" wire:click="$set('modalReprocessarAberto', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <h3 class="mb-4 text-lg font-bold text-gray-900 border-b border-gray-100 pb-2 flex items-center gap-2">
                        <i class="ph-bold ph-arrows-clockwise text-orange-500 text-xl"></i> Reprocessar Arquivo #{{ $importacaoReprocessarId }}
                    </h3>
                    
                    <p class="text-sm text-gray-600 mb-4 font-medium">O arquivo original e o seu mapeamento de colunas estão salvos. Como você deseja executar o reprocessamento?</p>
                    
                    <!-- CHECKBOX: Refazer Mapeamento -->
                    <div class="mb-4 p-3 bg-purpura-50/50 border border-purpura-100 rounded-lg">
                        <label class="flex items-start gap-3 cursor-pointer group">
                            <div class="flex items-center h-5 mt-0.5">
                                <input type="checkbox" wire:model="refazerMapeamento" class="h-4 w-4 text-purpura-600 rounded border-gray-300 focus:ring-purpura-500">
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm text-purpura-900 font-bold group-hover:text-purpura-700 transition">Quero alterar o Mapeamento das Colunas</span>
                                <span class="text-[11px] text-purpura-600 leading-tight mt-0.5 font-medium">Abre a tela de cruzamento de dados preenchida com seu mapa anterior para você ajustar antes de enviar.</span>
                            </div>
                        </label>
                    </div>
                    
                    <div class="space-y-3">
                        <button wire:click="reprocessar('tudo')" class="w-full text-left flex items-start gap-3 p-4 border border-gray-200 hover:border-blue-500 hover:bg-blue-50 transition rounded-xl group shadow-sm">
                            <div class="bg-gray-50 group-hover:bg-blue-100 text-gray-400 group-hover:text-blue-600 p-2.5 rounded-lg shrink-0 transition">
                                <i class="ph-bold ph-files text-xl"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 text-sm">Reprocessar Arquivo Completo</h4>
                                <p class="text-xs text-gray-500 mt-1 font-medium leading-tight">O sistema lerá a planilha desde a primeira linha ignorando duplicatas com sucesso.</p>
                            </div>
                        </button>

                        <button wire:click="reprocessar('falhas')" class="w-full text-left flex items-start gap-3 p-4 border border-gray-200 hover:border-orange-500 hover:bg-orange-50 transition rounded-xl group shadow-sm">
                            <div class="bg-gray-50 group-hover:bg-orange-100 text-gray-400 group-hover:text-orange-600 p-2.5 rounded-lg shrink-0 transition">
                                <i class="ph-bold ph-warning text-xl"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 text-sm">Reprocessar Apenas as Falhas</h4>
                                <p class="text-xs text-gray-500 mt-1 font-medium leading-tight">O sistema pulará todas as linhas normais e tentará importar estritamente as linhas com erro.</p>
                            </div>
                        </button>
                    </div>

                    <div class="flex justify-end gap-3 pt-6 mt-4 border-t border-gray-100">
                        <button type="button" wire:click="$set('modalReprocessarAberto', false)" class="px-6 py-2.5 text-sm font-bold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition">Cancelar Operação</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL 5: MONITORAMENTO EM TEMPO REAL -->
    @if($modalMonitoramentoAberto)
        <div class="fixed inset-0 z-[90] overflow-y-auto" wire:poll.1s="monitorarProgresso">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/90 backdrop-blur-md"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block w-full max-w-2xl px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-2xl shadow-2xl sm:my-8 sm:align-middle sm:p-6 border border-purpura-100">
                    
                    @if($importacaoMonitoramento)
                        <div class="text-center mb-6">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-purpura-50 text-purpura-600 mb-4 animate-pulse">
                                <i class="ph-fill ph-rocket-launch text-3xl"></i>
                            </div>
                            <h3 class="text-xl font-black text-gray-900 mb-1">Processando Importação...</h3>
                            <p class="text-sm font-medium text-gray-500">Não feche esta janela. O sistema está salvando os dados no servidor.</p>
                        </div>
                        
                        <div class="bg-gray-50 rounded-xl p-5 border border-gray-200 mb-6 relative overflow-hidden">
                            <div class="flex justify-between items-end mb-2 relative z-10">
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Progresso Atual</span>
                                <span class="text-2xl font-black text-purpura-600">{{ $importacaoMonitoramento->progresso }}%</span>
                            </div>
                            
                            <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden relative z-10">
                                <div class="h-full bg-gradient-to-r from-purpura-500 to-indigo-500 transition-all duration-300" style="width: {{ $importacaoMonitoramento->progresso }}%"></div>
                            </div>
                            
                            <div class="flex justify-between items-center mt-3 relative z-10">
                                <span class="text-xs font-bold text-gray-600 bg-white px-2 py-1 rounded border shadow-sm">
                                    <i class="ph-bold ph-check text-green-500"></i> {{ number_format($importacaoMonitoramento->linhas_processadas, 0, ',', '.') }} linhas
                                </span>
                                <span class="text-xs font-bold text-gray-500">
                                    Total: {{ number_format($importacaoMonitoramento->total_linhas, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>

                        @php
                            $logRaw = json_decode($importacaoMonitoramento->erro_mensagem, true);
                            $ultimoLog = is_array($logRaw) ? end($logRaw) : null;
                        @endphp

                        <div class="bg-gray-900 rounded-xl p-4 shadow-inner border border-gray-800">
                            <div class="flex items-center gap-2 mb-2 text-gray-400 text-xs font-bold uppercase tracking-wider">
                                <i class="ph-bold ph-terminal-window text-green-400"></i> Terminal / Memória
                            </div>
                            
                            <div class="font-mono text-xs text-gray-300">
                                <div class="flex items-center gap-2 text-green-400">
                                    <span class="animate-pulse">▶</span> Lendo arquivo: {{ $importacaoMonitoramento->arquivo_nome }}
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-blue-400">ℹ</span> Lote atual processando a linha nº <span class="text-white font-bold">{{ $importacaoMonitoramento->linhas_processadas + 1 }}</span>...
                                </div>
                                
                                @if($ultimoLog)
                                    <div class="mt-3 pt-3 border-t border-gray-700">
                                        <span class="text-red-400 block mb-1">⚠️ Último alerta capturado:</span>
                                        <div class="text-gray-400 leading-tight">
                                            [Linha {{ $ultimoLog['linha'] ?? '?' }}] {{ $ultimoLog['mensagem'] ?? 'Erro desconhecido' }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                    @else
                        <div class="flex flex-col items-center justify-center p-8">
                            <i class="ph ph-spinner animate-spin text-4xl text-purpura-500 mb-4"></i>
                            <h3 class="text-lg font-bold text-gray-900">Iniciando conexão com o servidor...</h3>
                        </div>
                    @endif

                    <div class="mt-6 text-center">
                        <button wire:click="fecharMonitoramento" class="text-xs font-bold text-gray-400 hover:text-gray-600 transition underline decoration-dashed underline-offset-4">
                            Ocultar e processar em 2º plano
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>