<div class="p-6 max-w-7xl mx-auto font-sans relative">

    <x-page-header 
        title="Gestor de Processamento (Background)" 
        icon="ph ph-cpu"
        :breadcrumbs="$breadcrumbs ?? []">

        <x-slot name="filters">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 flex items-center gap-1">
                        <i class="ph ph-activity text-purpura-500"></i> Status do Processo
                    </label>
                    <select wire:model.live="filtro_status" class="w-full rounded-md border-gray-300 shadow-sm px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                        <option value="">Todos</option>
                        <option value="mapeamento">Configuração Inicial</option>
                        <option value="na_fila">Na Fila</option>
                        <option value="processando">Processando</option>
                        <option value="concluido">Concluído</option>
                        <option value="erro_parcial">Concluído c/ Alertas</option>
                        <option value="erro">Falha Crítica</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 flex items-center gap-1">
                        <i class="ph ph-user text-purpura-500"></i> Solicitante
                    </label>
                    <select wire:model.live="filtro_usuario" class="w-full rounded-md border-gray-300 shadow-sm px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                        <option value="">Todos os Usuários</option>
                        @foreach($usuariosDisponiveis as $id => $nome)
                            <option value="{{ $id }}">{{ Str::limit($nome, 20) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 flex items-center gap-1">
                        <i class="ph ph-calendar-plus text-purpura-500"></i> De (Data)
                    </label>
                    <input type="datetime-local" wire:model.live="filtro_data_inicio" class="w-full rounded-md border-gray-300 shadow-sm px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 flex items-center gap-1">
                        <i class="ph ph-calendar-check text-purpura-500"></i> Até (Data)
                    </label>
                    <input type="datetime-local" wire:model.live="filtro_data_fim" class="w-full rounded-md border-gray-300 shadow-sm px-3 py-2 text-sm focus:ring-purpura-500 focus:border-purpura-500">
                </div>
                @if($filtro_status !== '' || $filtro_usuario !== '' || $filtro_data_inicio !== '' || $filtro_data_fim !== '')
                    <div class="md:col-span-12 flex justify-end mt-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button wire:click="limparFiltros" class="px-4 py-2 text-sm font-bold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-2 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            <i class="ph-bold ph-x"></i> Limpar Filtros
                        </button>
                    </div>
                @endif
            </div>
        </x-slot>

        <x-slot name="actions">
            @if(feature('tarefas.exportar') && (auth()->user()->hasRole('dev') || auth()->user()->can('tarefas.exportar')))
                <button wire:click="solicitarExportacao" class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 mr-2">
                    <i class="text-lg ph ph-export"></i> Solicitar Exportação
                </button>
            @endif

            @if(feature('tarefas.acessar') && (auth()->user()->hasRole('dev') || auth()->user()->can('tarefas.acessar')))
                <button wire:click="baixarTemplate" class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 mr-2" title="Baixar Planilha Modelo">
                    <i class="text-lg ph ph-download-simple"></i> Modelo Base
                </button>
                
                <button wire:click="abrirModalUpload" class="flex items-center gap-2 px-4 py-2 text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600 font-bold text-sm">
                    <i class="ph ph-plus-circle text-lg"></i> Iniciar Tarefa
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
            
            @forelse($registros as $task)
                @php $visual = $task->status_visual; @endphp
                <tr class="hover:bg-gray-50 transition-colors duration-200">
                    <td class="px-4 py-2.5 whitespace-nowrap text-sm font-medium text-gray-500">
                        #{{ $task->id }}
                    </td>
                    <td class="px-4 py-2.5 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-gray-50 rounded-lg border border-gray-200">
                                <i class="ph-fill ph-cpu text-2xl {{ $task->formato_icone }}"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-gray-900 truncate max-w-[200px]">{{ $task->arquivo_nome ?? 'Processamento de Sistema' }}</span>
                                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">{{ $task->operacao }} • {{ $task->tipo }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-2.5">
                        <div class="flex flex-col gap-1.5 w-full max-w-xs">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold flex items-center gap-1.5 px-2 py-0.5 rounded border {{ $visual['cor'] }}">
                                    <i class="text-sm {{ $visual['icone'] }}"></i> {{ $visual['label'] }}
                                </span>
                                <span class="font-bold text-gray-600">{{ $task->progresso }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-1.5 overflow-hidden">
                                <div class="h-1.5 rounded-full transition-all duration-500 bg-purpura-500" style="width: {{ $task->progresso }}%"></div>
                            </div>
                            <span class="text-[10px] text-gray-400 font-medium">{{ number_format($task->linhas_processadas, 0, ',', '.') }} de {{ number_format($task->total_linhas, 0, ',', '.') }} ciclos processados</span>

                            <!-- Exibição do Tempo de Execução -->
                            <div class="text-[10px] text-gray-500 font-medium mt-1">
                                ⏱️ Duração: <span class="font-bold text-gray-700 dark:text-gray-300">{{ $task->tempo_execucao }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-2.5 whitespace-nowrap">
                        <div class="text-sm font-bold text-gray-800">{{ $task->created_at->format('d/m/Y H:i') }}</div>
                        <div class="text-[10px] font-bold text-gray-500 flex items-center gap-1 mt-0.5"><i class="ph-fill ph-user"></i> Solicitado por: {{ $task->user->name ?? 'Sistema' }}</div>
                    </td>
                    <td class="px-4 py-2.5 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-1">
                            @if(in_array($task->status, ['na_fila', 'processando']) && $task->operacao === 'importacao')
                                <div x-data="{ openCancel: false }" class="relative inline-block text-left">
                                    <button @click="openCancel = !openCancel" @click.away="openCancel = false" class="p-1.5 text-red-500 transition-colors rounded hover:bg-red-50 dark:hover:bg-red-900/30" title="Cancelar Tarefa">
                                        <i class="text-lg ph-bold ph-stop-circle"></i>
                                    </button>
                                    <div x-show="openCancel" x-cloak class="absolute right-0 z-50 w-48 mt-2 origin-top-right bg-white border border-gray-200 divide-y divide-gray-100 rounded-md shadow-lg dark:bg-gray-800 dark:border-gray-700">
                                        <div class="py-1">
                                            <button wire:click="cancelarImportacaoListagem({{ $task->id }}, false)" class="flex items-center w-full px-4 py-2 text-xs font-bold text-red-600 hover:bg-red-50 dark:hover:bg-gray-700">
                                                <i class="ph-bold ph-stop mr-2"></i> Apenas Parar
                                            </button>
                                            <button wire:click="cancelarImportacaoListagem({{ $task->id }}, true)" class="flex items-center w-full px-4 py-2 text-xs font-bold text-red-800 hover:bg-red-50 dark:hover:bg-gray-700" onclick="confirm('Isso apagará os dados já inseridos. Confirmar?') || event.stopImmediatePropagation()">
                                                <i class="ph-bold ph-trash mr-2"></i> Parar e Apagar Dados
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            <!-- Botão de Reprocessar / Tentar Novamente para tarefas com erro ou presas -->
                            @if(in_array($task->status, ['erro', 'erro_parcial', 'na_fila', 'processando']))
                                <button wire:click="reentrarNaFila({{ $task->id }})" class="p-1.5 text-blue-500 transition-colors rounded hover:bg-blue-50 dark:hover:bg-gray-700" title="Tentar Novamente / Reprocessar">
                                    <i class="text-lg ph-bold ph-arrow-clockwise"></i>
                                </button>
                            @endif

                            <button wire:click="verDetalhes({{ $task->id }})" class="p-1.5 text-gray-500 transition-colors rounded hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-gray-700" title="Ver Relatório">
                                <i class="text-lg ph-fill ph-info"></i>
                            </button>

                            @if(in_array($task->status, ['erro', 'erro_parcial']) && $task->operacao === 'importacao')
                                <button wire:click="abrirModalReprocessar({{ $task->id }})" class="p-1.5 text-orange-500 transition-colors rounded hover:text-orange-600 hover:bg-orange-50 dark:hover:bg-gray-700" title="Reprocessar Tarefa (Mapeamento)">
                                    <i class="text-lg ph-bold ph-arrows-clockwise"></i>
                                </button>
                            @endif

                            @if($task->operacao === 'exportacao' && $task->status === 'concluido' && $task->arquivo_gerado_caminho)
                                <button wire:click="baixarExportacao({{ $task->id }})" class="p-1.5 text-green-600 transition-colors rounded hover:bg-green-50" title="Baixar Ficheiro Gerado">
                                    <i class="text-lg ph-bold ph-download-simple"></i>
                                </button>
                            @endif

                            <button wire:click="excluirImportacao({{ $task->id }})" class="p-1.5 text-gray-400 transition-colors rounded-lg hover:text-red-500 hover:bg-red-50" title="Excluir Registo" onclick="confirm('Excluir este log e apagar os ficheiros do servidor?') || event.stopImmediatePropagation()">
                                <i class="text-lg ph ph-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center text-gray-500 text-sm border-t border-gray-100">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3 border border-gray-200">
                            <i class="ph ph-cpu text-3xl text-gray-400"></i>
                        </div>
                        <p class="font-bold text-gray-600">Nenhuma tarefa de processamento encontrada.</p>
                    </td>
                </tr>
            @endforelse
        </x-table>
    </div>

    @if($modalUploadAberto)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="$set('modalUploadAberto', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <h3 class="mb-4 text-lg font-bold text-gray-900 border-b border-gray-100 pb-2 flex items-center gap-2">
                        <i class="ph-fill ph-upload-simple text-purpura-500 text-xl"></i> Fornecimento de Dados
                    </h3>
                    
                    <form wire:submit.prevent="processarUpload" class="space-y-5">
                        
                        <div>
                            <label class="block mb-1 text-xs font-bold text-gray-700 uppercase tracking-wider">Ciclo Vinculado <span class="text-red-500">*</span></label>
                            <select wire:model="cicloSelecionadoId" class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-purpura-500 focus:ring-purpura-500">
                                <option value="">Selecione o Ciclo de Inscrição...</option>
                                @foreach($ciclosDisponiveis as $ciclo)
                                    <option value="{{ $ciclo->id }}">{{ $ciclo->nome }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                            <label class="block text-xs font-bold text-gray-800 mb-2 uppercase tracking-wider">Ficheiro de Origem</label>
                            
                            <div class="flex items-center justify-center w-full">
                                <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-purpura-300 bg-purpura-50 rounded-lg cursor-pointer hover:bg-purpura-100 transition">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6 text-purpura-600">
                                        <i class="ph ph-file-xls text-3xl mb-1"></i>
                                        <p class="text-xs font-bold">Clique ou arraste a planilha</p>
                                        <p class="text-[10px] mt-1 font-medium text-purpura-500">.CSV ou .XLSX (Máx: 50MB)</p>
                                    </div>
                                    <input type="file" wire:model="arquivo" class="hidden" accept=".csv, .xlsx, .xls">
                                </label>
                            </div>
                            
                            <div wire:loading wire:target="arquivo" class="mt-2 text-xs font-bold text-purpura-600 flex items-center justify-center gap-2">
                                <i class="ph ph-spinner animate-spin text-lg"></i> A ler ficheiro...
                            </div>
                            
                            @if($arquivo)
                                <div class="mt-3 bg-white p-2 border border-green-200 rounded-md flex items-center gap-2 text-green-700 text-xs font-bold shadow-sm">
                                    <i class="ph-fill ph-check-circle text-lg"></i> {{ $arquivo->getClientOriginalName() }} anexado.
                                </div>
                            @endif
                            @error('arquivo') <span class="text-xs text-red-500 mt-2 block font-bold text-center">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                            <label class="flex items-start gap-3 p-3 border border-purpura-200 bg-purpura-50/50 rounded-lg cursor-pointer hover:bg-purpura-50 transition">
                                <div class="flex items-center h-5 mt-0.5">
                                    <input type="checkbox" wire:model="permitirAutoCadastro" class="h-4 w-4 text-purpura-600 rounded border-gray-300 focus:ring-purpura-500">
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-sm text-purpura-900 font-bold">Auto-cadastrar Entidades</span>
                                    <span class="text-[10px] text-purpura-600 leading-tight mt-0.5">Cria dependências (Cursos, Unidades, Turnos) se não existirem na base de dados.</span>
                                </div>
                            </label>

                            <label class="flex items-start gap-3 p-3 border border-blue-200 bg-blue-50/50 rounded-lg cursor-pointer hover:bg-blue-50 transition">
                                <div class="flex items-center h-5 mt-0.5">
                                    <input type="checkbox" wire:model="mesclarDuplicadas" class="h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-sm text-blue-900 font-bold">Mesclar Duplicadas</span>
                                    <span class="text-[10px] text-blue-600 leading-tight mt-0.5">Atualiza registos (CPFs) que já existam para o mesmo Ciclo.</span>
                                </div>
                            </label>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 mt-2 border-t border-gray-100">
                            <button type="button" wire:click="$set('modalUploadAberto', false)" class="px-4 py-2.5 text-sm font-bold border rounded-lg text-gray-600 hover:bg-gray-50 transition">Cancelar</button>
                            <button type="submit" class="px-6 py-2.5 text-sm font-bold text-white rounded-lg shadow-sm bg-purpura-600 hover:bg-purpura-700 transition flex items-center gap-2">
                                Analisar Estrutura <i class="ph-bold ph-arrow-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if($modalMapeamentoAberto)
        <div class="fixed inset-0 z-[60] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/80 backdrop-blur-sm"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6">
                    
                    <div class="flex justify-between items-center mb-6 border-b border-gray-100 pb-4">
                        <div>
                            <h3 class="text-xl font-extrabold text-gray-900 flex items-center gap-2"><i class="ph-fill ph-git-merge text-ponkan-500"></i> Mapeamento Estrutural</h3>
                            <p class="text-sm text-gray-500 font-medium">Faça a equivalência entre os dados submetidos e a arquitetura do sistema.</p>
                        </div>
                        <span class="bg-blue-100 text-blue-700 font-bold px-3 py-1 rounded text-xs">Etapa 2 de 2</span>
                    </div>
                    
                    <div class="max-h-[50vh] overflow-y-auto custom-scrollbar pr-2 space-y-3">
                        @foreach($mapeamento as $index => $item)
                            <div class="flex items-center gap-4 bg-gray-50 p-3 rounded-lg border border-gray-200" wire:key="map-{{ $index }}">
                                <div class="w-1/3 flex items-center gap-2">
                                    <i class="ph-fill ph-table text-gray-400 text-xl"></i>
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
                        <button type="button" wire:click="excluirImportacao({{ $taskAtualId }})" class="px-4 py-2.5 text-sm font-bold border rounded-lg text-gray-600 hover:bg-gray-50 transition">Cancelar e descartar</button>
                        <button type="button" wire:click="iniciarImportacao" wire:loading.attr="disabled" class="px-6 py-2.5 text-sm font-bold text-white rounded-lg shadow-sm bg-ponkan-500 hover:bg-ponkan-600 transition flex items-center gap-2">
                            <span wire:loading.remove wire:target="iniciarImportacao" class="flex items-center gap-2">
                                <i class="ph-bold ph-rocket-launch"></i> Confirmar e Despachar Tarefa
                            </span>
                            <span wire:loading wire:target="iniciarImportacao" class="flex items-center gap-2">
                                <i class="ph-bold ph-spinner animate-spin"></i> A alocar recursos...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($modalDetalhesAberto && $taskDetalhes)
        <div class="fixed inset-0 z-[70] overflow-y-auto" x-data="{ fullscreen: false, tab: 'logs' }">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" wire:click="$set('modalDetalhesAberto', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div :class="fullscreen ? 'fixed inset-0 z-50 flex flex-col bg-white' : 'relative z-10 inline-block w-full max-w-5xl my-8 overflow-hidden text-left align-bottom transition-all transform bg-white shadow-2xl rounded-xl sm:align-middle'"
                     class="px-4 pt-5 pb-4 sm:p-6">
                    
                    @php 
                        $erros = json_decode($taskDetalhes->erro_mensagem, true) ?? []; 
                        $isDev = auth()->user()->hasRole('dev');
                        $totalProcessado = $taskDetalhes->linhas_processadas;
                        $totalFalhas = count($erros);
                        $totalSucesso = max(0, $totalProcessado - $totalFalhas);
                    @endphp

                    <div class="flex justify-between items-start mb-4 border-b border-gray-100 pb-4 shrink-0">
                        <div>
                            <h3 class="text-xl font-extrabold text-gray-900 flex items-center gap-2">
                                <i class="ph-fill ph-terminal-window text-purpura-500"></i> Relatório da Tarefa #{{ $taskDetalhes->id }}
                            </h3>
                            <p class="text-xs text-gray-500 font-medium mt-1">Origem de Dados: <b class="text-gray-700">{{ $taskDetalhes->arquivo_nome ?? 'Sem origem de ficheiro' }}</b></p>
                        </div>
                        <div class="flex gap-2 items-center">
                            @if(count($erros) > 0 && $taskDetalhes->operacao === 'importacao')
                                <button wire:click="baixarErros({{ $taskDetalhes->id }})" class="px-3 py-1.5 bg-red-50 border border-red-200 hover:bg-red-100 text-red-600 text-xs font-bold rounded-lg flex items-center gap-2 transition shadow-sm mr-2">
                                    <i class="ph-bold ph-download-simple"></i> Descarregar Anomalias
                                </button>
                            @endif
                            @if($taskDetalhes->operacao === 'importacao')
                                <button wire:click="baixarArquivoOriginal({{ $taskDetalhes->id }})" class="px-3 py-1.5 bg-gray-50 border border-gray-200 hover:bg-gray-100 text-gray-700 text-xs font-bold rounded-lg flex items-center gap-2 transition shadow-sm">
                                    <i class="ph-bold ph-download-simple"></i> Ficheiro Original
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
                            <span class="text-xs font-bold px-2 py-0.5 rounded border {{ $taskDetalhes->status_visual['cor'] }} inline-flex items-center gap-1 w-max">
                                <i class="{{ $taskDetalhes->status_visual['icone'] }}"></i> {{ $taskDetalhes->status_visual['label'] }}
                            </span>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                            <span class="text-[10px] uppercase font-bold text-gray-500 block mb-1">Ciclos da Tarefa</span>
                            <span class="text-lg font-bold text-gray-900">{{ number_format($totalProcessado, 0, '', '.') }} <span class="text-[10px] text-gray-400 font-medium">/ {{ number_format($taskDetalhes->total_linhas, 0, '', '.') }}</span></span>
                        </div>
                        <div class="bg-green-50 p-3 rounded-lg border border-green-200">
                            <span class="text-[10px] uppercase font-bold text-green-700 block mb-1">Cargas com Sucesso</span>
                            <span class="text-lg font-bold text-green-800">{{ number_format($totalSucesso, 0, '', '.') }}</span>
                        </div>
                        <div class="bg-red-50 p-3 rounded-lg border border-red-200">
                            <span class="text-[10px] uppercase font-bold text-red-700 block mb-1">Desvios / Rejeições</span>
                            <span class="text-lg font-bold text-red-800">{{ number_format($totalFalhas, 0, '', '.') }}</span>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                            <span class="text-[10px] uppercase font-bold text-gray-500 block mb-1">Data / Hora</span>
                            <span class="text-sm font-bold text-gray-900">{{ $taskDetalhes->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="text-[10px] text-gray-500 font-medium mt-1">
                            Duração: <span class="font-bold text-gray-700 dark:text-gray-300">{{ $task->tempo_execucao }}</span>
                        </div>

                    </div>

                    <div class="flex items-center gap-6 mb-2 border-b border-gray-100 shrink-0">
                        <button @click="tab = 'logs'" :class="tab === 'logs' ? 'text-purpura-600 border-b-2 border-purpura-600' : 'text-gray-500 hover:text-gray-700'" class="pb-2 font-bold text-sm transition-colors flex items-center gap-1">
                            <i class="ph-bold ph-code"></i> Saída de Console (Logs)
                        </button>
                    </div>
                    
                    <div x-show="tab === 'logs'" class="bg-gray-900 text-gray-300 rounded-lg text-xs shadow-inner border border-gray-800 flex-1 flex flex-col overflow-hidden" :class="fullscreen ? 'h-full min-h-[300px]' : 'h-64'">
                        <div class="overflow-y-auto custom-scrollbar flex-1">
                            @if(empty($erros) && $taskDetalhes->status === 'concluido')
                                <div class="p-8 text-center text-gray-500 italic flex flex-col items-center justify-center h-full">
                                    <i class="ph-fill ph-check-circle text-4xl mb-2 text-green-500"></i>
                                    <span class="font-bold text-gray-400 text-sm">Execução Limpa</span>
                                    A rotina terminou sem gerar eventos anómalos.
                                </div>
                            @elseif(empty($erros))
                                <div class="p-8 text-center text-gray-500 italic flex flex-col items-center justify-center h-full">
                                    <i class="ph-fill ph-hourglass-high text-4xl mb-2 text-gray-600"></i>
                                    Aguardando *workers* finalizarem...
                                </div>
                            @else
                                <table class="w-full text-left border-collapse">
                                    <thead class="bg-gray-950 sticky top-0 border-b border-gray-700 shadow-sm z-10">
                                        <tr>
                                            <th class="p-3 font-bold uppercase tracking-wider text-[10px] text-gray-400 w-16 text-center">Índice</th>
                                            <th class="p-3 font-bold uppercase tracking-wider text-[10px] text-gray-400 w-40">Classificação</th>
                                            <th class="p-3 font-bold uppercase tracking-wider text-[10px] text-gray-400">Mensagem da *Exception*</th>
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
                                                <td class="p-3 font-medium text-gray-300 whitespace-pre-line">{{ $isDev ? ($erro['mensagem'] ?? 'Erro desconhecido') : ($erro['amigavel'] ?? $erro['mensagem'] ?? 'Falha ao processar o registo da iteração atual.') }}</td>
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

    @if($modalReprocessarAberto)
        <div class="fixed inset-0 z-[80] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/70 backdrop-blur-sm" wire:click="$set('modalReprocessarAberto', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <h3 class="mb-4 text-lg font-bold text-gray-900 border-b border-gray-100 pb-2 flex items-center gap-2">
                        <i class="ph-bold ph-arrows-clockwise text-orange-500 text-xl"></i> Reprocessar Tarefa #{{ $taskReprocessarId }}
                    </h3>
                    
                    <p class="text-sm text-gray-600 mb-4 font-medium">Os metadados originais e o mapeamento encontram-se em cache. Selecione o vetor de execução:</p>
                    
                    <div class="mb-4 p-3 bg-purpura-50/50 border border-purpura-100 rounded-lg">
                        <label class="flex items-start gap-3 cursor-pointer group">
                            <div class="flex items-center h-5 mt-0.5">
                                <input type="checkbox" wire:model="refazerMapeamento" class="h-4 w-4 text-purpura-600 rounded border-gray-300 focus:ring-purpura-500">
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm text-purpura-900 font-bold group-hover:text-purpura-700 transition">Intervir no Mapeamento de Colunas</span>
                                <span class="text-[11px] text-purpura-600 leading-tight mt-0.5 font-medium">Suspende a execução e abre a tabela de equivalências para calibração manual antes de despachar o Job.</span>
                            </div>
                        </label>
                    </div>
                    
                    <div class="space-y-3">
                        <button wire:click="reprocessar('tudo')" class="w-full text-left flex items-start gap-3 p-4 border border-gray-200 hover:border-blue-500 hover:bg-blue-50 transition rounded-xl group shadow-sm">
                            <div class="bg-gray-50 group-hover:bg-blue-100 text-gray-400 group-hover:text-blue-600 p-2.5 rounded-lg shrink-0 transition">
                                <i class="ph-bold ph-files text-xl"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 text-sm">Executar do Zero (Full Scan)</h4>
                                <p class="text-xs text-gray-500 mt-1 font-medium leading-tight">Reinicia a *queue* lendo o dataset desde o índice 0. Entradas repetidas sem a flag de mesclagem serão ignoradas.</p>
                            </div>
                        </button>

                        <button wire:click="reprocessar('falhas')" class="w-full text-left flex items-start gap-3 p-4 border border-gray-200 hover:border-orange-500 hover:bg-orange-50 transition rounded-xl group shadow-sm">
                            <div class="bg-gray-50 group-hover:bg-orange-100 text-gray-400 group-hover:text-orange-600 p-2.5 rounded-lg shrink-0 transition">
                                <i class="ph-bold ph-warning text-xl"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 text-sm">Reprocessar Delta (Apenas Anomalias)</h4>
                                <p class="text-xs text-gray-500 mt-1 font-medium leading-tight">Isola os índices que reportaram *Exceptions* no último ciclo e tenta executar exclusivamente esses blocos.</p>
                            </div>
                        </button>
                    </div>

                    <div class="flex justify-end gap-3 pt-6 mt-4 border-t border-gray-100">
                        <button type="button" wire:click="$set('modalReprocessarAberto', false)" class="px-6 py-2.5 text-sm font-bold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition">Abortar Ação</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($modalMonitoramentoAberto)
        <div class="fixed inset-0 z-[90] overflow-y-auto" wire:poll.1s="monitorarProgresso">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/90 backdrop-blur-md"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div class="relative z-10 inline-block w-full max-w-2xl px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-2xl shadow-2xl sm:my-8 sm:align-middle sm:p-6 border border-purpura-100">
                    
                    @if($taskMonitoramento)
                        <div class="text-center mb-6">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-purpura-50 text-purpura-600 mb-4 animate-pulse">
                                <i class="ph-fill ph-cpu text-3xl"></i>
                            </div>
                            <h3 class="text-xl font-black text-gray-900 mb-1">Processamento em Andamento...</h3>
                            <p class="text-sm font-medium text-gray-500">Mantenha esta janela aberta para acompanhar o progresso da *thread* principal em tempo real.</p>
                        </div>
                        
                        <div class="bg-gray-50 rounded-xl p-5 border border-gray-200 mb-6 relative overflow-hidden">
                            <div class="flex justify-between items-end mb-2 relative z-10">
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Estado da Carga</span>
                                <span class="text-2xl font-black text-purpura-600">{{ $taskMonitoramento->progresso }}%</span>
                            </div>
                            
                            <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden relative z-10">
                                <div class="h-full bg-gradient-to-r from-purpura-500 to-indigo-500 transition-all duration-300" style="width: {{ $taskMonitoramento->progresso }}%"></div>
                            </div>
                            
                            <div class="flex justify-between items-center mt-3 relative z-10">
                                <span class="text-xs font-bold text-gray-600 bg-white px-2 py-1 rounded border shadow-sm">
                                    <i class="ph-bold ph-check text-green-500"></i> {{ number_format($taskMonitoramento->linhas_processadas, 0, ',', '.') }} ciclos completos
                                </span>
                                <span class="text-xs font-bold text-gray-500">
                                    Total Estimado: {{ number_format($taskMonitoramento->total_linhas, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>

                        @php
                            $logRaw = json_decode($taskMonitoramento->erro_mensagem, true);
                            $ultimoLog = is_array($logRaw) ? end($logRaw) : null;
                        @endphp

                        <div class="bg-gray-900 rounded-xl p-4 shadow-inner border border-gray-800">
                            <div class="flex items-center gap-2 mb-2 text-gray-400 text-xs font-bold uppercase tracking-wider">
                                <i class="ph-bold ph-terminal-window text-green-400"></i> Consola Remota (Workers)
                            </div>
                            
                            <div class="font-mono text-xs text-gray-300">
                                <div class="flex items-center gap-2 text-green-400">
                                    <span class="animate-pulse">▶</span> Acesso garantido ao dataset: {{ $taskMonitoramento->arquivo_nome ?? 'Stream interno' }}
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-blue-400">ℹ</span> Extração do bloco iterativo <span class="text-white font-bold">#{{ $taskMonitoramento->linhas_processadas + 1 }}</span> na fila de execução...
                                </div>
                                
                                @if($ultimoLog)
                                    <div class="mt-3 pt-3 border-t border-gray-700">
                                        <span class="text-red-400 block mb-1">⚠️ Exceção detetada no fluxo:</span>
                                        <div class="text-gray-400 leading-tight truncate">
                                            [Índice {{ $ultimoLog['linha'] ?? '?' }}] {{ $ultimoLog['mensagem'] ?? 'Runtime Exception não categorizada.' }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                    @else
                        <div class="flex flex-col items-center justify-center p-8">
                            <i class="ph ph-spinner animate-spin text-4xl text-purpura-500 mb-4"></i>
                            <h3 class="text-lg font-bold text-gray-900">A invocar os workers na cloud...</h3>
                        </div>
                    @endif

                    <div class="mt-6 border-t border-gray-100 pt-4" x-data="{ showCancelOptions: false }">
                        
                        <div x-show="!showCancelOptions" class="flex justify-between items-center w-full">
                            <button wire:click="fecharMonitoramento" class="text-xs font-bold text-gray-500 hover:text-gray-800 transition underline decoration-dashed underline-offset-4">
                                Desacoplar Monitor (Correr em Background)
                            </button>
                            <button @click="showCancelOptions = true" class="text-xs font-bold text-red-500 hover:text-red-700 transition flex items-center gap-1 bg-red-50 px-3 py-1.5 rounded-lg border border-red-100">
                                <i class="ph-bold ph-x-circle"></i> Interromper Processamento (Kill Job)
                            </button>
                        </div>

                        <div x-show="showCancelOptions" x-cloak class="flex flex-col items-center bg-red-50 p-4 rounded-xl border border-red-100 w-full animate-fade-in-up">
                            <span class="text-sm font-bold text-red-800 mb-3 flex items-center gap-2">
                                <i class="ph-fill ph-warning-circle text-lg"></i> Confirmar encerramento forçado da tarefa?
                            </span>
                            
                            <div class="flex flex-wrap gap-2 w-full justify-center">
                                <button @click="showCancelOptions = false" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 rounded-lg text-xs font-bold transition shadow-sm">
                                    Abortar comando de cancelamento
                                </button>
                                
                                <button wire:click="cancelarImportacao(false)" wire:loading.attr="disabled" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-bold transition shadow-sm flex items-center gap-1">
                                    <span wire:loading.remove wire:target="cancelarImportacao(false)"><i class="ph-bold ph-stop"></i> Apenas Parar o Worker</span>
                                    <span wire:loading wire:target="cancelarImportacao(false)"><i class="ph-bold ph-spinner animate-spin"></i> Parando rotina...</span>
                                </button>
                                
                                @if(isset($taskMonitoramento) && $taskMonitoramento->operacao === 'importacao')
                                    <button wire:click="cancelarImportacao(true)" wire:loading.attr="disabled" class="px-4 py-2 bg-red-800 hover:bg-red-900 text-white rounded-lg text-xs font-bold transition shadow-sm flex items-center gap-1">
                                        <span wire:loading.remove wire:target="cancelarImportacao(true)"><i class="ph-bold ph-trash"></i> Parar e Reverter Banco de Dados</span>
                                        <span wire:loading wire:target="cancelarImportacao(true)"><i class="ph-bold ph-spinner animate-spin"></i> Executando Rollback...</span>
                                    </button>
                                @endif
                            </div>
                            
                            <p class="text-[10px] text-red-600 font-medium mt-3 text-center leading-tight">
                                <b>"Apenas Parar o Worker":</b> Corta a execução no lote atual, preservando as inserções completadas na BD.<br>
                                @if(isset($taskMonitoramento) && $taskMonitoramento->operacao === 'importacao')
                                    <b>"Reverter Banco":</b> Emite um comando massivo de eliminação para quaisquer registos originados por esta tarefa específica.
                                @endif
                            </p>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>