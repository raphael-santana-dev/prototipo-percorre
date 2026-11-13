<aside class="w-80 xl:w-96 bg-white dark:bg-gray-900 border-l border-gray-200 dark:border-gray-800 flex flex-col shrink-0 h-full overflow-hidden relative z-20">
    @if(count($camposSelecionados) > 1)
        <div class="flex-1 flex flex-col items-center justify-center p-8 text-center">
            <span class="px-2.5 py-1 rounded bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 text-xs font-semibold mb-2">
                {{ count($camposSelecionados) }} Inputs Selecionados
            </span>
            <h4 class="text-sm font-bold text-gray-900 dark:text-white">Modo de Movimentação em Lote</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 leading-relaxed">
                Utilize a barra superior escura para mover ou excluir os itens selecionados em bloco.
            </p>
            <button type="button" wire:click="limparSelecaoCampos" class="mt-4 btn btn--secondary btn--small">
                Desmarcar Todos
            </button>
        </div>

    @elseif($painelCampoAtivo || $campoId)
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between shrink-0">
            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">
                {{ $campoId ? 'Editar Input' : 'Novo Input' }}
            </span>
            <div class="flex items-center gap-2">
                @if($campoId)
                    <span class="bg-gray-100 text-gray-700 text-[10px] font-semibold px-2 py-0.5 rounded border border-gray-200">Em Edição</span>
                @endif
                <button type="button" wire:click="cancelarEdicao" class="text-gray-400 hover:text-gray-700 p-1 rounded transition" title="Fechar">
                    <i class="ph ph-x text-sm"></i>
                </button>
            </div>
        </div>

        <form wire:submit.prevent="salvar" class="flex-1 flex flex-col overflow-hidden">
            <div class="p-4 overflow-y-auto flex-1 custom-scrollbar space-y-4">
                
                {{-- PÁGINA E ORDEM --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Página (Fase) e Posição</label>
                    <div class="grid grid-cols-2 gap-2">
                        <select wire:model.live="etapa" class="w-full text-xs">
                            @foreach($etapasDisponiveis as $et)
                                <option value="{{ $et->numero }}">{{ $et->nome }}</option>
                            @endforeach
                        </select>
                        <input type="number" wire:model="ordem" min="1" max="{{ $this->limiteOrdem }}" class="w-full text-xs text-center">
                    </div>
                    @error('ordem') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror

                    @if($etapa == 1 && $contextoTipo === 'ciclo')
                        <label class="flex items-center gap-2 p-2.5 mt-2 border border-gray-200 rounded-lg cursor-pointer bg-gray-50">
                            <input type="checkbox" wire:model="configuracoes.exibir_no_topo">
                            <div class="flex flex-col">
                                <span class="text-xs text-gray-900 font-semibold">Exibir no Topo da Etapa 1</span>
                                <span class="text-[10px] text-gray-500">Antes dos dados pessoais fixos.</span>
                            </div>
                        </label>
                    @endif
                </div>

                {{-- TÍTULO / LABEL COM EDITOR QUILL COMPLETO --}}
                @if(!in_array($tipo, ['divider', 'spacer']))
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ in_array($tipo, ['html', 'media']) ? 'Conteúdo Principal (Texto/Título)' : ($tipo === 'link' ? 'Texto de Exibição do Link / Botão' : 'Título da Pergunta') }} <span class="text-red-500">*</span>
                        </label>
                        <div class="builder-quill"
                             wire:ignore
                             x-data="{
                                val: @entangle('label').live,
                                quill: null,
                                init() {
                                    this.quill = new Quill(this.$refs.editorLabel, {
                                        theme: 'snow',
                                        placeholder: 'Digite e formate o texto...',
                                        modules: {
                                            toolbar: [
                                                ['bold', 'italic', 'underline', 'strike'],
                                                [{ 'size': ['small', false, 'large', 'huge'] }],
                                                [{ 'color': [] }, { 'background': [] }],
                                                ['clean']
                                            ]
                                        }
                                    });
                                    this.quill.root.innerHTML = this.val || '';
                                    this.quill.on('text-change', () => {
                                        let html = this.quill.root.innerHTML;
                                        this.val = (html === '<p><br></p>') ? '' : html;
                                    });
                                    window.addEventListener('sincronizar-quill-builder', () => {
                                        setTimeout(() => {
                                            if (this.quill.root.innerHTML !== (this.val || '')) this.quill.root.innerHTML = this.val || '';
                                        }, 50);
                                    });
                                }
                             }">
                            <div x-ref="editorLabel"></div>
                        </div>
                    </div>
                @endif

                {{-- ID E LARGURA --}}
                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">ID no Banco <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="name" class="w-full text-xs font-mono bg-gray-50">
                        @error('name') <span class="text-[11px] text-red-600 mt-0.5 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Largura na Grelha</label>
                        <select wire:model="largura" class="w-full text-xs">
                            <option value="12">100% (12/12)</option>
                            <option value="9">75% (9/12)</option>
                            <option value="8">66% (8/12)</option>
                            <option value="6">50% (6/12)</option>
                            <option value="4">33% (4/12)</option>
                            <option value="3">25% (3/12)</option>
                            <option value="2">16% (2/12)</option>
                            <option value="1">8% (1/12)</option>
                            <option value="0">Ocupar Restante</option>
                        </select>
                    </div>
                </div>

                {{-- DATA DE NASCIMENTO (CALCULAR IDADE) --}}
                @if($tipo === 'text' && $subtipo === 'date')
                    <label class="flex items-start gap-2.5 p-3 bg-gray-50 border border-gray-200 rounded-lg cursor-pointer">
                        <input type="checkbox" wire:model.live="configuracoes.calcular_idade" class="mt-0.5">
                        <div class="text-xs">
                            <span class="font-semibold text-gray-900 block">Data de Nascimento (Calcular Idade)</span>
                            <span class="text-[11px] text-gray-500">Calcula e exibe automaticamente a idade em anos ao preencher esta data.</span>
                        </div>
                    </label>
                @endif

                {{-- DICAS E ORIENTAÇÕES --}}
                @if(!in_array($tipo, ['divider', 'spacer']))
                    <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-800 mb-1">Dicas e Orientações</label>
                            <select wire:model.live="configuracoes.dica_modo" class="w-full text-xs">
                                <option value="nenhum">Sem dica / orientação</option>
                                <option value="legenda">Abaixo do input (Legenda)</option>
                                <option value="tooltip">Ícone ao lado do título (Tooltip)</option>
                                <option value="ambos">Ambos (Legenda + Tooltip)</option>
                            </select>
                        </div>

                        @if(($configuracoes['dica_modo'] ?? 'nenhum') !== 'nenhum')
                            <div class="p-2.5 bg-white border border-gray-200 rounded-lg space-y-2">
                                <span class="text-[11px] font-semibold text-gray-700 block">
                                    {{ ($configuracoes['dica_modo'] ?? '') === 'ambos' ? '1ª Dica / Orientação' : 'Texto da Dica / Orientação' }}
                                </span>

                                @if(($configuracoes['dica_modo'] ?? '') === 'ambos')
                                    <select wire:model.live="configuracoes.dica_1_tipo" class="w-full text-xs">
                                        <option value="legenda">Legenda (Abaixo do Input)</option>
                                        <option value="tooltip">Tooltip (Ícone Interativo)</option>
                                    </select>
                                @endif

                                @if((($configuracoes['dica_modo'] ?? '') === 'tooltip') || (($configuracoes['dica_modo'] ?? '') === 'ambos' && ($configuracoes['dica_1_tipo'] ?? 'legenda') === 'tooltip'))
                                    <select wire:model.live="configuracoes.dica_1_trigger" class="w-full text-xs">
                                        <option value="hover">Acionar ao passar o mouse (Hover)</option>
                                        <option value="click">Acionar ao clicar no ícone (Click)</option>
                                    </select>
                                @endif

                                <div class="builder-quill"
                                     wire:ignore
                                     x-data="{
                                        val: @entangle('configuracoes.dica_1_texto').live,
                                        quill: null,
                                        init() {
                                            this.quill = new Quill(this.$refs.editorDica1, {
                                                theme: 'snow',
                                                placeholder: 'Escreva a orientação aqui...',
                                                modules: { toolbar: [['bold', 'italic', 'underline'], [{ 'color': [] }], ['clean']] }
                                            });
                                            this.quill.root.innerHTML = this.val || '';
                                            this.quill.on('text-change', () => {
                                                let html = this.quill.root.innerHTML;
                                                this.val = (html === '<p><br></p>') ? '' : html;
                                            });
                                            window.addEventListener('sincronizar-quill-builder', () => {
                                                setTimeout(() => {
                                                    if (this.quill.root.innerHTML !== (this.val || '')) this.quill.root.innerHTML = this.val || '';
                                                }, 50);
                                            });
                                        }
                                     }">
                                    <div x-ref="editorDica1"></div>
                                </div>
                            </div>

                            @if(($configuracoes['dica_modo'] ?? '') === 'ambos')
                                <div class="p-2.5 bg-white border border-gray-200 rounded-lg space-y-2">
                                    <span class="text-[11px] font-semibold text-gray-700 block">2ª Dica / Orientação</span>
                                    <select wire:model.live="configuracoes.dica_2_tipo" class="w-full text-xs">
                                        <option value="tooltip">Tooltip (Ícone Interativo)</option>
                                        <option value="legenda">Legenda (Abaixo do Input)</option>
                                    </select>

                                    @if(($configuracoes['dica_2_tipo'] ?? 'tooltip') === 'tooltip')
                                        <select wire:model.live="configuracoes.dica_2_trigger" class="w-full text-xs">
                                            <option value="hover">Acionar ao passar o mouse (Hover)</option>
                                            <option value="click">Acionar ao clicar no ícone (Click)</option>
                                        </select>
                                    @endif

                                    <div class="builder-quill"
                                         wire:ignore
                                         x-data="{
                                            val: @entangle('configuracoes.dica_2_texto').live,
                                            quill: null,
                                            init() {
                                                this.quill = new Quill(this.$refs.editorDica2, {
                                                    theme: 'snow',
                                                    placeholder: 'Escreva a segunda orientação...',
                                                    modules: { toolbar: [['bold', 'italic', 'underline'], [{ 'color': [] }], ['clean']] }
                                                });
                                                this.quill.root.innerHTML = this.val || '';
                                                this.quill.on('text-change', () => {
                                                    let html = this.quill.root.innerHTML;
                                                    this.val = (html === '<p><br></p>') ? '' : html;
                                                });
                                                window.addEventListener('sincronizar-quill-builder', () => {
                                                    setTimeout(() => {
                                                        if (this.quill.root.innerHTML !== (this.val || '')) this.quill.root.innerHTML = this.val || '';
                                                    }, 50);
                                                });
                                            }
                                         }">
                                        <div x-ref="editorDica2"></div>
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                @endif

                {{-- CONFIGURAÇÕES POR TIPO --}}
                @if($tipo === 'signature')
                    <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg space-y-2">
                        <label class="block text-xs font-medium text-gray-700">Quem irá assinar este campo?</label>
                        <select wire:model.live="configuracoes.tipo_assinante" class="w-full text-xs">
                            <option value="usuario">Assinatura do Utilizador ("Sua assinatura")</option>
                            <option value="responsavel">Assinatura do Responsável ("Assinatura do responsável")</option>
                        </select>
                    </div>
                @endif

                @if($tipo === 'link')
                    <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Tipo de Destino</label>
                            <select wire:model.live="configuracoes.tipo_destino" class="w-full text-xs">
                                <option value="externo">Link Externo (URL Completa)</option>
                                <option value="interno">Página ou Rota Interna do Sistema</option>
                                <option value="arquivo">Arquivo Interno (Upload PDF, Word ou Excel)</option>
                            </select>
                        </div>

                        @if(($configuracoes['tipo_destino'] ?? 'externo') === 'arquivo')
                            <input type="file" wire:model="link_file_upload" accept=".pdf,.doc,.docx,.xls,.xlsx" class="block w-full text-xs text-gray-600">
                            <div wire:loading wire:target="link_file_upload" class="text-[11px] text-purpura-600 font-semibold">Carregando arquivo...</div>
                            @error('link_file_upload') <span class="text-xs text-red-600 block">{{ $message }}</span> @enderror

                            @if(!empty($configuracoes['arquivo_nome']) && !$link_file_upload)
                                <div class="p-2 bg-white border border-gray-200 rounded flex items-center justify-between text-[11px]">
                                    <span class="font-medium text-gray-700 truncate">{{ $configuracoes['arquivo_nome'] }}</span>
                                    <span class="uppercase font-mono text-[10px] px-1.5 py-0.5 bg-gray-100 rounded">{{ $configuracoes['arquivo_extensao'] ?? '' }}</span>
                                </div>
                            @endif

                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="configuracoes.abrir_preview">
                                <span class="text-xs text-gray-700">Abrir pré-visualização no navegador</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="configuracoes.permitir_download">
                                <span class="text-xs text-gray-700">Permitir download do arquivo</span>
                            </label>
                        @else
                            <input type="text" wire:model="configuracoes.url" placeholder="{{ ($configuracoes['tipo_destino'] ?? 'externo') === 'interno' ? '/portal' : 'https://exemplo.com.br' }}" class="w-full text-xs font-mono">
                            @error('configuracoes.url') <span class="text-xs text-red-600 block">{{ $message }}</span> @enderror
                        @endif
                    </div>
                @endif

                @if($tipo === 'text' && $subtipo === 'cep')
                    <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg space-y-2.5">
                        <label class="flex items-start gap-2 cursor-pointer">
                            <input type="checkbox" wire:model.live="configuracoes.viacep" class="mt-0.5">
                            <div class="text-xs">
                                <span class="font-semibold text-gray-900 block">Consultar ViaCEP automaticamente?</span>
                                <span class="text-[11px] text-gray-500">Preenche os campos de endereço ao digitar o CEP.</span>
                            </div>
                        </label>
                        @if(!empty($configuracoes['viacep']) && !$campoId)
                            <label class="flex items-start gap-2 cursor-pointer pt-2 border-t border-gray-200">
                                <input type="checkbox" wire:model="configuracoes.criar_campos_endereco" class="mt-0.5">
                                <span class="text-xs text-gray-700 font-medium">Gerar campos de endereço (Logradouro, Nº, Complemento, Bairro, Cidade e UF)</span>
                            </label>
                        @endif
                    </div>
                @endif

                @if($tipo === 'text' && in_array($subtipo, ['text', 'number', 'cpf', 'cnpj', 'tel', 'email']))
                    <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg space-y-3">
                        @if($subtipo === 'number')
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[11px] text-gray-600 mb-1">Valor Mínimo</label>
                                    <input type="number" wire:model="tamanho_min" class="w-full text-xs">
                                </div>
                                <div>
                                    <label class="block text-[11px] text-gray-600 mb-1">Valor Máximo</label>
                                    <input type="number" wire:model="tamanho_max" class="w-full text-xs">
                                </div>
                            </div>
                        @else
                            <div>
                                <label class="block text-[11px] text-gray-600 mb-1">Máscara (x-mask)</label>
                                <input type="text" wire:model="regex_mascara" placeholder="Ex: 999.999.999-99" class="w-full text-xs font-mono">
                            </div>
                        @endif

                        @if(in_array($subtipo, ['cpf', 'email']))
                            <div class="border-t border-gray-200 pt-2.5 space-y-2">
                                @if($subtipo === 'cpf')
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" wire:model.live="configuracoes.validar_cpf">
                                        <span class="text-xs text-gray-800">Validar CPF (Cálculo Real)</span>
                                    </label>
                                @endif
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model.live="configuracoes.unico">
                                    <span class="text-xs text-gray-800">Travar Resposta Única</span>
                                </label>
                            </div>
                        @endif
                    </div>
                @endif

                @if($tipo === 'file')
                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 space-y-2.5">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Categoria de Arquivos</label>
                            <select wire:model.live="configuracoes.tipo_arquivo" class="w-full text-xs">
                                <option value="todos">Todos os Tipos</option>
                                <option value="imagem">Apenas Imagens</option>
                                <option value="video">Apenas Vídeos</option>
                                <option value="documento">Apenas Documentos</option>
                                <option value="custom">Personalizado</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Extensões Permitidas</label>
                            <input type="text" wire:model="configuracoes.extensoes_permitidas" class="w-full text-xs font-mono">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Tamanho Máx (MB)</label>
                                <input type="number" min="1" max="100" wire:model="configuracoes.max_size_mb" class="w-full text-xs">
                            </div>
                            @if(!empty($configuracoes['aceita_multiplos']))
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Qtd. Máxima</label>
                                    <input type="number" min="2" max="20" wire:model="configuracoes.max_arquivos" class="w-full text-xs">
                                </div>
                            @endif
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer pt-1">
                            <input type="checkbox" wire:model.live="configuracoes.aceita_multiplos">
                            <span class="text-xs text-gray-700 font-medium">Permitir múltiplos arquivos?</span>
                        </label>
                    </div>
                @endif

                @if(in_array($tipo, ['select', 'radio', 'check']))
                    <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg space-y-2.5">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Opções de Resposta (1 por linha)</label>
                            <textarea wire:model="opcoes" rows="4" placeholder="Sim&#10;Não" class="w-full text-xs"></textarea>
                        </div>
                        @if(in_array($tipo, ['radio', 'check']))
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Alinhamento</label>
                                <select wire:model.live="configuracoes.layout_opcoes" class="w-full text-xs">
                                    <option value="horizontal">Horizontal (Lado a Lado)</option>
                                    <option value="vertical">Vertical (Lista)</option>
                                </select>
                            </div>
                        @endif
                    </div>
                @elseif($tipo === 'matriz')
                    <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg space-y-2.5">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Linhas (1 por linha)</label>
                            <textarea wire:model.live.debounce.300ms="matriz_linhas" rows="3" class="w-full text-xs"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Colunas (separadas por vírgula)</label>
                            <input type="text" wire:model.live.debounce.300ms="matriz_colunas" class="w-full text-xs">
                        </div>
                    </div>
                @elseif($tipo === 'system')
                    @php
                        $qtdUniCfg = count($configuracoes['unidades_permitidas'] ?? []);
                        $qtdCurCfg = count($configuracoes['cursos_permitidos'] ?? []);
                        $qtdTurCfg = count($configuracoes['turnos_permitidos'] ?? []);
                    @endphp
                    <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-800">Opções Disponíveis</span>
                            @if($qtdUniCfg > 0 || $qtdCurCfg > 0 || $qtdTurCfg > 0)
                                <button type="button" wire:click="limparRestricaoComboAcademico" class="text-[11px] font-medium text-red-600 hover:underline">
                                    Usar Todas
                                </button>
                            @endif
                        </div>

                        @if($qtdUniCfg > 0 || $qtdCurCfg > 0 || $qtdTurCfg > 0)
                            <div class="flex flex-wrap gap-1.5 text-[10px] font-semibold">
                                <span class="px-2 py-0.5 rounded bg-white border border-gray-200 text-gray-700">{{ $qtdUniCfg }} Unidade(s)</span>
                                <span class="px-2 py-0.5 rounded bg-white border border-gray-200 text-gray-700">{{ $qtdCurCfg }} Curso(s)</span>
                                <span class="px-2 py-0.5 rounded bg-white border border-gray-200 text-gray-700">{{ $qtdTurCfg }} Turno(s)</span>
                            </div>
                        @endif

                        <button type="button" wire:click="abrirModalConfigCombo" class="btn btn--secondary btn--small w-full">
                            Configurar Unidades / Cursos / Turnos
                        </button>

                        @if(in_array($subtipo, ['unidade', 'combo_academico']))
                            <label class="flex items-center gap-2 cursor-pointer pt-1">
                                <input type="checkbox" wire:model.live="configuracoes.filtrar_por_uf">
                                <span class="text-xs text-gray-700">Filtrar Unidades pela UF do usuário</span>
                            </label>
                        @endif
                        @if(in_array($subtipo, ['curso', 'combo_academico']))
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model.live="configuracoes.vincular_curso_unidade">
                                <span class="text-xs text-gray-700">Relacionar Cursos à Unidade escolhida</span>
                            </label>
                        @endif
                        @if(in_array($subtipo, ['turno', 'combo_academico']))
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model.live="configuracoes.vincular_turno_curso">
                                <span class="text-xs text-gray-700">Vincular Turnos ao Curso escolhido</span>
                            </label>
                        @endif
                    </div>
                @elseif($tipo === 'html')
                    <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg space-y-2.5">
                        <label class="block text-xs font-medium text-gray-700">Estilo do Texto</label>
                        <select wire:model.live="subtipo" class="w-full text-xs">
                            <option value="h1">Título Grande (H1)</option>
                            <option value="h2">Título Médio (H2)</option>
                            <option value="h3">Título Pequeno (H3)</option>
                            <option value="p">Parágrafo Normal</option>
                            <option value="info_card">Caixa de Aviso (Azul)</option>
                        </select>
                        @if($subtipo === 'info_card')
                            <div>
                                <label class="block text-[11px] font-medium text-gray-700 mb-1">Texto Adicional do Aviso</label>
                                <div class="builder-quill"
                                     wire:ignore
                                     x-data="{
                                        val: @entangle('configuracoes.descricao').live,
                                        quill: null,
                                        init() {
                                            this.quill = new Quill(this.$refs.editorAvisoDesc, {
                                                theme: 'snow',
                                                placeholder: 'Texto complementar do aviso...',
                                                modules: { toolbar: [['bold', 'italic', 'underline'], ['clean']] }
                                            });
                                            this.quill.root.innerHTML = this.val || '';
                                            this.quill.on('text-change', () => {
                                                let html = this.quill.root.innerHTML;
                                                this.val = (html === '<p><br></p>') ? '' : html;
                                            });
                                            window.addEventListener('sincronizar-quill-builder', () => {
                                                setTimeout(() => {
                                                    if (this.quill.root.innerHTML !== (this.val || '')) this.quill.root.innerHTML = this.val || '';
                                                }, 50);
                                            });
                                        }
                                     }">
                                    <div x-ref="editorAvisoDesc"></div>
                                </div>
                            </div>
                        @endif
                    </div>
                @elseif($tipo === 'media')
                    <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg space-y-2">
                        <select wire:model.live="subtipo" class="w-full text-xs">
                            <option value="image">Imagem Estática</option>
                            <option value="video">Vídeo Externo</option>
                        </select>
                        <input type="url" wire:model="configuracoes.url" placeholder="URL da Imagem ou YouTube" class="w-full text-xs">
                    </div>
                @elseif($tipo === 'social')
                    <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg space-y-2">
                        <label class="block text-xs font-medium text-gray-700">Redes Solicitadas</label>
                        <div class="grid grid-cols-2 gap-1.5">
                            @foreach(['instagram' => 'Instagram', 'facebook' => 'Facebook', 'youtube' => 'YouTube', 'tiktok' => 'TikTok', 'vsco' => 'VSCO', 'linkedin' => 'LinkedIn'] as $k => $nomeRede)
                                <label class="flex items-center gap-1.5 cursor-pointer bg-white p-2 rounded border border-gray-200 text-xs">
                                    <input type="checkbox" wire:model="configuracoes.redes_permitidas" value="{{ $k }}">
                                    <span>{{ $nomeRede }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @elseif($tipo === 'rating')
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Total de Estrelas</label>
                        <input type="number" wire:model="configuracoes.max_stars" min="3" max="10" placeholder="5" class="w-full text-xs">
                    </div>
                @elseif($tipo === 'scale')
                    <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg space-y-2">
                        <select wire:model="configuracoes.escala_maxima" class="w-full text-xs">
                            <option value="5">Escala de 0 a 5</option>
                            <option value="10">Escala de 0 a 10</option>
                        </select>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" wire:model="configuracoes.label_min" placeholder="Rótulo Mín (0)" class="w-full text-xs">
                            <input type="text" wire:model="configuracoes.label_max" placeholder="Rótulo Máx" class="w-full text-xs">
                        </div>
                    </div>
                @endif

                {{-- OBRIGATORIEDADE --}}
                @if(!in_array($tipo, ['html', 'divider', 'media', 'social', 'spacer', 'link']))
                    <label class="flex items-center gap-2.5 p-2.5 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" wire:model="obrigatorio">
                        <div class="flex flex-col">
                            <span class="text-xs font-semibold text-gray-900">Campo Obrigatório</span>
                            <span class="text-[10px] text-gray-500">Impede o avanço sem preenchimento.</span>
                        </div>
                    </label>
                @endif

                {{-- CONDIÇÃO DE VISIBILIDADE --}}
                @php $diagPainel = $this->diagnosticoSimulacaoCondicional; @endphp
                <div class="p-3 border border-gray-200 rounded-lg space-y-3 bg-gray-50/60">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-800">Condição de Visibilidade</span>
                        @if($modoCondicional !== 'nenhum')
                            <span class="px-2 py-0.5 text-[10px] font-medium rounded bg-gray-200 text-gray-800">
                                {{ $modoCondicional === 'avancado' ? 'Avançado' : 'Simples' }}
                            </span>
                        @endif
                    </div>

                    <select wire:model.live="modoCondicional" class="w-full text-xs">
                        <option value="nenhum">Sempre visível (Sem condição)</option>
                        <option value="simples">Condição Simples (1 Campo)</option>
                        <option value="avancado">Condição Avançada (Múltiplos Grupos E / OU)</option>
                    </select>

                    @if($modoCondicional === 'simples')
                        <div class="space-y-2 pt-2 border-t border-gray-200">
                            <select wire:model.live="depende_de" class="w-full text-xs">
                                <option value="">Selecione o campo gatilho...</option>
                                @foreach($this->camposDisponiveisParaCondicao as $cKey => $cLabel)
                                    <option value="{{ $cKey }}">{{ $cLabel }}</option>
                                @endforeach
                            </select>

                            @if(!empty($depende_de))
                                <select wire:model.live="depende_operador" class="w-full text-xs">
                                    @foreach($this->operadoresCondicionais as $opKey => $opNome)
                                        <option value="{{ $opKey }}">{{ $opNome }}</option>
                                    @endforeach
                                </select>

                                @if(!in_array($depende_operador, ['empty', 'not_empty']))
                                    @if($depende_operador === 'between')
                                        <div class="grid grid-cols-2 gap-2">
                                            <input type="text" wire:model.live.debounce.300ms="depende_valor" placeholder="Mínimo" class="w-full text-xs">
                                            <input type="text" wire:model.live.debounce.300ms="depende_valor_max" placeholder="Máximo" class="w-full text-xs">
                                        </div>
                                    @else
                                        <input type="text" wire:model.live.debounce.300ms="depende_valor" placeholder="Valor esperado..." class="w-full text-xs">
                                    @endif
                                @endif
                            @endif
                        </div>

                    @elseif($modoCondicional === 'avancado')
                        <div class="space-y-2 pt-2 border-t border-gray-200">
                            <div class="p-2 bg-white rounded border border-gray-200 font-mono text-[11px] text-gray-600 break-words">
                                {{ $diagPainel['expressao_humana'] }}
                            </div>
                            <button type="button" wire:click="abrirModalCondicoesAvancadas" class="btn btn--secondary btn--small w-full">
                                Abrir Construtor de Condições ({{ count($gruposCondicionais) }} grupo/s)
                            </button>
                        </div>
                    @endif
                </div>

            </div>

            <div class="p-3.5 bg-gray-50 border-t border-gray-200 shrink-0 flex gap-2">
                <button type="button" wire:click="cancelarEdicao" class="flex-1 btn btn--secondary btn--small">Cancelar</button>
                <button type="submit" class="flex-1 btn btn--primary btn--small bg-purpura-600 hover:bg-purpura-700 border-none">
                    {{ $campoId ? 'Atualizar Bloco' : 'Inserir Bloco' }}
                </button>
            </div>
        </form>
    @else
        <div class="flex-1 flex flex-col items-center justify-center p-8 text-center text-gray-400">
            <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">Nenhum Input Selecionado</p>
            <p class="text-[11px] text-gray-400 mt-1">
                Escolha um tipo de campo à esquerda para adicionar ou clique sobre um item no centro para editá-lo.
            </p>
        </div>
    @endif
</aside>