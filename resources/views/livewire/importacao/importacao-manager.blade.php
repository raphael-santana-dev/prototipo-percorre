<div class="p-6 max-w-7xl mx-auto font-sans relative">

    <x-page-header 
        title="Gerenciador de Integrações (I/O)" 
        icon="ph ph-arrows-left-right"
        badge=""
        :breadcrumbs="$breadcrumbs ?? []">

        {{-- AREA DE FILTROS APLICADA --}}
        <x-slot name="filters">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                
                <!-- Tipo de Importação -->
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

                <!-- Status da Importação -->
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

                <!-- Usuário -->
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

                <!-- Data De -->
                <div class="md:col-span-2" x-data="{
                    initZero(e) {
                        if (!e.target.value) {
                            let d = new Date();
                            let y = d.getFullYear();
                            let m = String(d.getMonth() + 1).padStart(2, '0');
                            let day = String(d.getDate()).padStart(2, '0');
                            e.target.value = `${y}-${m}-${day}T00:00`;
                            e.target.dispatchEvent(new Event('input'));
                        }
                    }
                }">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 flex items-center gap-1">
                        <i class="ph ph-calendar-plus text-purpura-500"></i> De (Data)
                    </label>
                    <input type="datetime-local" wire:model.live="filtro_data_inicio" @focus="initZero" class="w-full rounded-md border-gray-300 shadow-sm px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                </div>

                <!-- Data Até -->
                <div class="md:col-span-2" x-data="{
                    initEnd(e) {
                        if (!e.target.value) {
                            let d = new Date();
                            let y = d.getFullYear();
                            let m = String(d.getMonth() + 1).padStart(2, '0');
                            let day = String(d.getDate()).padStart(2, '0');
                            e.target.value = `${y}-${m}-${day}T23:59`;
                            e.target.dispatchEvent(new Event('input'));
                        }
                    }
                }">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 flex items-center gap-1">
                        <i class="ph ph-calendar-check text-purpura-500"></i> Até (Data)
                    </label>
                    <input type="datetime-local" wire:model.live="filtro_data_fim" @focus="initEnd" class="w-full rounded-md border-gray-300 shadow-sm px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
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
            
            {{-- EXPORTAR --}}
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
                            <button wire:click="solicitarExportacao('campos', 'xlsx')" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-purpura-600 font-medium text-left">
                                Estrutura de Formulários
                            </button>
                        </div>
                    </div>
                </div>
            @endif


            {{-- IMPORTAR / TEMPLATES --}}
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
                            <button wire:click="baixarTemplate('usuarios')" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-purpura-600 gap-2 font-medium">
                                <i class="ph ph-file-csv text-lg text-green-600"></i> Modelo: Usuários Internos
                            </button>
                            <button wire:click="baixarTemplate('campos')" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-purpura-600 gap-2 font-medium">
                                <i class="ph ph-file-csv text-lg text-green-600"></i> Modelo: Blocos de Formulário
                            </button>
                            <button wire:click="baixarTemplate('unidades')" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-purpura-600 gap-2 font-medium">
                                <i class="ph ph-file-csv text-lg text-green-600"></i> Modelo: Sedes / Unidades
                            </button>
                            <button wire:click="baixarTemplate('cursos')" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-purpura-600 gap-2 font-medium">
                                <i class="ph ph-file-csv text-lg text-green-600"></i> Modelo: Cursos Ativos
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
                                <span class="text-sm font-bold text-gray-900 truncate max-w-[200px]" title="{{ $log->arquivo_nome }}">{{ $log->arquivo_nome ?? 'Exportação de Sistema' }}</span>
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
                            @if(in_array($log->status, ['erro', 'erro_parcial']))
                                <button wire:click="verErro({{ $log->id }})" class="p-1.5 text-orange-500 transition-colors rounded hover:bg-orange-50" title="Ver Relatório de Erros">
                                    <i class="text-lg ph-fill ph-warning"></i>
                                </button>
                            @endif

                            @if($log->operacao === 'exportacao' && $log->status === 'concluido' && $log->arquivo_gerado_caminho)
                                <button wire:click="baixarExportacao({{ $log->id }})" class="p-1.5 text-green-600 transition-colors rounded hover:bg-green-50" title="Baixar Planilha">
                                    <i class="text-lg ph-bold ph-download-simple"></i>
                                </button>
                            @endif

                            <button wire:click="excluirImportacao({{ $log->id }})" class="p-1.5 text-gray-400 transition-colors rounded hover:text-red-500 hover:bg-red-50" title="Excluir Registro" onclick="confirm('Excluir este log e apagar os arquivos do servidor?') || event.stopImmediatePropagation()">
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
                        <p class="text-xs mt-1">Os processos em andamento ou concluídos aparecerão aqui.</p>
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
                                <option value="inscricoes">Base de Dados: Inscrições de Estudantes</option>
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
                                @error('cicloSelecionadoId') <span class="text-xs text-red-500 font-bold block mt-1">{{ $message }}</span> @enderror
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

    <!-- MODAL 2: AUTO-MAPEAMENTO (Apenas para Inscrições) -->
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
                        @foreach($cabecalhos as $coluna)
                            <div class="flex items-center gap-4 bg-gray-50 p-3 rounded-lg border border-gray-200">
                                <div class="w-1/3 flex items-center gap-2">
                                    <i class="ph-fill ph-file-xls text-gray-400 text-xl"></i>
                                    <span class="text-sm font-bold text-gray-700 truncate" title="{{ $coluna }}">{{ Str::limit($coluna, 25) }}</span>
                                </div>
                                <div class="text-gray-400"><i class="ph-bold ph-arrow-right"></i></div>
                                <div class="w-1/3">
                                    <select wire:model="mapeamento.{{ $coluna }}.destino" class="w-full text-xs font-bold rounded bg-white border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500">
                                        <option value="ignorar" class="text-red-500">-- Ignorar Coluna --</option>
                                        <option value="dados_dinamicos" class="text-blue-600">-- Salvar como Dado Customizado (JSON) --</option>
                                        <optgroup label="Campos Nativos do Sistema">
                                            @foreach($opcoesMapeamento as $chave => $label)
                                                <option value="{{ $chave }}">{{ $label }}</option>
                                            @endforeach
                                        </optgroup>
                                    </select>
                                </div>
                                <div class="w-1/4">
                                    <select wire:model="mapeamento.{{ $coluna }}.tipo" class="w-full text-xs font-medium rounded bg-white border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500">
                                        <option value="texto">Formato: Texto/Número</option>
                                        <option value="data">Formato: Data Simples</option>
                                        <option value="data_hora">Formato: Data e Hora</option>
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

    <!-- MODAL 3: RELATÓRIO DE ERROS -->
    @if($modalErroAberto)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="$set('modalErroAberto', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
                    <h3 class="mb-2 text-lg font-extrabold text-red-600 flex items-center gap-2 border-b border-gray-100 pb-3">
                        <i class="ph-fill ph-warning-circle text-2xl"></i> Relatório de Falhas
                    </h3>
                    <p class="text-xs text-gray-500 font-medium mb-4">Erros detectados durante o processamento do arquivo: <b>{{ $arquivoErroAtual }}</b></p>
                    
                    <div class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-[11px] h-64 overflow-y-auto custom-scrollbar shadow-inner">
                        @php $erros = json_decode($mensagemErroAtual, true) ?? []; @endphp
                        @forelse($erros as $erro)
                            <div class="mb-2 border-b border-gray-700 pb-2">
                                <span class="text-red-400 font-bold">[Linha {{ $erro['linha'] ?? 'Geral' }}]</span> 
                                <span class="text-gray-300">{{ $erro['mensagem'] ?? 'Erro desconhecido' }}</span>
                            </div>
                        @empty
                            <div class="text-gray-500 italic">Nenhum log de erro legível encontrado.</div>
                        @endforelse
                    </div>

                    <div class="flex justify-end gap-3 pt-4 mt-4 border-t border-gray-100">
                        <button type="button" wire:click="$set('modalErroAberto', false)" class="px-6 py-2 text-sm font-bold bg-gray-100 rounded-lg text-gray-700 hover:bg-gray-200 transition">Fechar Detalhes</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>