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
                            <button wire:click="verDetalhes({{ $log->id }})" class="p-1.5 text-blue-600 transition-colors rounded hover:bg-blue-50 dark:hover:bg-gray-700" title="Ver Detalhes do Processamento">
                                <i class="text-lg ph-fill ph-info"></i>
                            </button>

                            @if($log->operacao === 'exportacao' && $log->status === 'concluido' && $log->arquivo_gerado_caminho)
                                <button wire:click="baixarExportacao({{ $log->id }})" class="p-1.5 text-green-600 transition-colors rounded hover:bg-green-50" title="Baixar Planilha Gerada">
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

                        @if($tipoImportacao === 'inscricoes')
                            <label class="flex items-start gap-3 p-3 border border-purpura-200 bg-purpura-50/50 rounded-lg cursor-pointer hover:bg-purpura-50 transition">
                                <div class="flex items-center h-5 mt-0.5">
                                    <input type="checkbox" wire:model="permitirAutoCadastro" class="h-4 w-4 text-purpura-600 rounded border-gray-300 focus:ring-purpura-500">
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-sm text-purpura-900 font-bold">Auto-cadastrar Vínculos Ausentes</span>
                                    <span class="text-[10px] text-purpura-600 leading-tight mt-0.5">Se a sua planilha tiver um Curso, Unidade ou Turno que não exista no sistema, o sistema criará o cadastro deles automaticamente para você.</span>
                                </div>
                            </label>
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
                                        <option value="data">Formato: Data Simples</option>
                                        <option value="data_hora">Formato: Data e Hora</option>
                                        <option value="monetario">Formato: Monetário (R$)</option>
                                        <option value="booleano">Formato: Booleano (Sim/Não)</option>
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
        <div class="fixed inset-0 z-[70] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="$set('modalDetalhesAberto', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6">
                    
                    <div class="flex justify-between items-start mb-4 border-b border-gray-100 pb-4">
                        <div>
                            <h3 class="text-xl font-extrabold text-gray-900 flex items-center gap-2">
                                <i class="ph-fill ph-file-text text-purpura-500"></i> Relatório de Operação #{{ $importacaoDetalhes->id }}
                            </h3>
                            <p class="text-xs text-gray-500 font-medium mt-1">Nome do Arquivo Original: <b class="text-gray-700">{{ $importacaoDetalhes->arquivo_nome ?? 'Integração sem arquivo' }}</b></p>
                        </div>
                        <div class="flex gap-2 items-center">
                            @if($importacaoDetalhes->operacao === 'importacao')
                                <button wire:click="baixarArquivoOriginal({{ $importacaoDetalhes->id }})" class="px-3 py-1.5 bg-gray-50 border border-gray-200 hover:bg-gray-100 text-gray-700 text-xs font-bold rounded-lg flex items-center gap-2 transition shadow-sm">
                                    <i class="ph-bold ph-download-simple"></i> Baixar Original
                                </button>
                            @endif
                            <button wire:click="$set('modalDetalhesAberto', false)" class="text-gray-400 hover:text-red-500 transition ml-2"><i class="text-2xl ph ph-x"></i></button>
                        </div>
                    </div>

                    <div class="grid grid-cols-4 gap-4 mb-6">
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 flex flex-col justify-center">
                            <span class="text-[10px] uppercase font-bold text-gray-500 block mb-1">Status Final</span>
                            <span class="text-xs font-bold px-2 py-0.5 rounded border {{ $importacaoDetalhes->status_visual['cor'] }} inline-flex items-center gap-1 w-max">
                                <i class="{{ $importacaoDetalhes->status_visual['icone'] }}"></i> {{ $importacaoDetalhes->status_visual['label'] }}
                            </span>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                            <span class="text-[10px] uppercase font-bold text-gray-500 block mb-1">Módulo Alvo</span>
                            <span class="text-sm font-bold text-gray-900 uppercase tracking-wider">{{ $importacaoDetalhes->tipo }}</span>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                            <span class="text-[10px] uppercase font-bold text-gray-500 block mb-1">Progresso (Linhas)</span>
                            <span class="text-lg font-bold text-gray-900">{{ number_format($importacaoDetalhes->linhas_processadas, 0, '', '.') }} <span class="text-sm text-gray-400">/ {{ number_format($importacaoDetalhes->total_linhas, 0, '', '.') }}</span></span>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                            <span class="text-[10px] uppercase font-bold text-gray-500 block mb-1">Data da Solicitação</span>
                            <span class="text-sm font-bold text-gray-900">{{ $importacaoDetalhes->created_at->format('d/m/Y \à\s H:i') }}</span>
                        </div>
                    </div>

                    <h4 class="font-bold text-gray-800 text-sm mb-2 flex items-center gap-1"><i class="ph-bold ph-terminal-window text-gray-400"></i> Eventos e Observações do Sistema</h4>
                    <div class="bg-gray-900 text-gray-300 rounded-lg text-xs h-64 overflow-y-auto custom-scrollbar shadow-inner border border-gray-800">
                        @php 
                            $erros = json_decode($importacaoDetalhes->erro_mensagem, true) ?? []; 
                            $isDev = auth()->user()->hasRole('dev');
                        @endphp
                        
                        @if(empty($erros) && $importacaoDetalhes->status === 'concluido')
                            <div class="p-4 text-center text-gray-500 italic flex flex-col items-center justify-center h-full">
                                <i class="ph-fill ph-check-circle text-4xl mb-2 text-green-500"></i>
                                <span class="font-bold text-gray-400 text-sm">Operação Perfeita</span>
                                Nenhum alerta, duplicata ou erro foi registrado durante o processamento.
                            </div>
                        @elseif(empty($erros))
                            <div class="p-4 text-center text-gray-500 italic flex flex-col items-center justify-center h-full">
                                <i class="ph-fill ph-hourglass-high text-4xl mb-2 text-gray-600"></i>
                                Aguardando o processamento em background...
                            </div>
                        @else
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-gray-950 sticky top-0 border-b border-gray-700">
                                    <tr>
                                        <th class="p-2.5 font-bold uppercase tracking-wider text-[10px] text-gray-400 w-16 text-center">Linha</th>
                                        <th class="p-2.5 font-bold uppercase tracking-wider text-[10px] text-gray-400 w-36">Classificação</th>
                                        <th class="p-2.5 font-bold uppercase tracking-wider text-[10px] text-gray-400">O que aconteceu?</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-800">
                                    @foreach($erros as $erro)
                                        @php
                                            $tipoErro = $erro['tipo'] ?? 'Geral';
                                            $corSelo = 'bg-red-500/20 text-red-400 border-red-500/30'; // Padrão Erro Crítico
                                            if (str_contains($tipoErro, 'Alerta') || str_contains($tipoErro, 'Duplicata')) {
                                                $corSelo = 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30';
                                            }
                                        @endphp
                                        <tr class="hover:bg-gray-800/50 transition-colors">
                                            <td class="p-3 text-center text-gray-500 font-mono">[{{ $erro['linha'] ?? '-' }}]</td>
                                            <td class="p-3">
                                                <span class="px-2 py-0.5 border text-[10px] font-bold rounded {{ $corSelo }}">{{ $tipoErro }}</span>
                                            </td>
                                            <td class="p-3 font-medium text-gray-300">
                                                {{ $isDev ? ($erro['mensagem'] ?? 'Erro desconhecido') : ($erro['amigavel'] ?? $erro['mensagem'] ?? 'Falha ao processar o registro.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                    
                </div>
            </div>
        </div>
    @endif
</div>