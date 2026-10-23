@php
    $formBgUrl = !empty($formSettings['bg_image']) ? asset($formSettings['bg_image']) : null;
    $formBgColor = $formSettings['bg_color'] ?? '#f3f4f6'; 
    $formBgOpacity = $formSettings['bg_opacity'] ?? '0.0';
    $bgSize = $formSettings['bg_size'] ?? 'cover';
    $formWidth = $formSettings['form_width'] ?? 'max-w-4xl';
    $isTranslucent = filter_var($formSettings['translucent_card'] ?? false, FILTER_VALIDATE_BOOLEAN);
    
    $cardClass = $isTranslucent ? 'bg-white/80 dark:bg-gray-900/80 backdrop-blur-md shadow-2xl border border-gray-100 dark:border-gray-800' : 'bg-white dark:bg-gray-900 shadow-xl border border-gray-100 dark:border-gray-800';
    $textoForm = $isTranslucent ? 'text-gray-900 dark:text-white drop-shadow-sm' : 'text-gray-900 dark:text-white';
    
    $inputClassBase = "w-full rounded-md border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purpura-500/25 focus:border-purpura-500 transition-colors bg-white dark:bg-gray-800 text-gray-900 dark:text-white border-gray-300 dark:border-gray-700";
@endphp

<div class="min-h-screen flex flex-col relative w-full font-sans bg-transparent"
     x-data="{
        storageKey: 'percorre_inscricao_ciclo_{{ $cicloAtivoId ?? 'default' }}{{ $isTeste ? '_TESTE' : '' }}',
        readyToSave: false,
        init() {
            if (@js($isPreview) || !@js($inscricoesAbertas)) return;

            const raw = localStorage.getItem(this.storageKey);
            if (raw) {
                try {
                    const parsed = JSON.parse(raw);
                    if (parsed && typeof parsed === 'object') {
                        $wire.restaurarRascunhoLocal(parsed).then(() => {
                            this.readyToSave = true;
                        });
                        return;
                    }
                } catch (e) {
                    localStorage.removeItem(this.storageKey);
                }
            }
            this.readyToSave = true;
        },
        salvarNoLocalStorage() {
            if (!this.readyToSave || @js($isPreview) || @js($isTeste)) return;
            if ([99, 100].includes(Number($wire.etapaAtual))) {
                localStorage.removeItem(this.storageKey);
                return;
            }

            const payload = {
                inscricaoId: $wire.inscricaoId,
                etapaAtual: $wire.etapaAtual,
                nome: $wire.nome,
                possui_nome_social: $wire.possui_nome_social,
                nome_social: $wire.nome_social,
                cpf: $wire.cpf,
                email: $wire.email,
                celular: $wire.celular,
                data_nascimento: $wire.data_nascimento,
                possui_deficiencia: $wire.possui_deficiencia,
                natureza_deficiencia: $wire.natureza_deficiencia,
                cep: $wire.cep,
                logradouro: $wire.logradouro,
                numero: $wire.numero,
                bairro: $wire.bairro,
                cidade: $wire.cidade,
                estado: $wire.estado,
                complemento: $wire.complemento,
                regiao: $wire.regiao,
                unidade: $wire.unidade,
                curso: $wire.curso,
                turno: $wire.turno,
                deseja_informar: $wire.deseja_informar,
                unidade_interesse: $wire.unidade_interesse,
                curso_interesse: $wire.curso_interesse,
                turno_interesse: $wire.turno_interesse,
                autorizacao_uso_infos: $wire.autorizacao_uso_infos,
                respostas: $wire.respostas
            };

            try {
                localStorage.setItem(this.storageKey, JSON.stringify(payload));
            } catch (e) {}
        }
     }"
     @input.debounce.400ms="salvarNoLocalStorage()"
     @change="salvarNoLocalStorage()"
     @mouseup.debounce.300ms="salvarNoLocalStorage()"
     @touchend.debounce.300ms="salvarNoLocalStorage()"
     @limpar-storage-inscricao.window="localStorage.removeItem(storageKey)">

    @if($formBgUrl)
        <div class="fixed inset-0 z-0 bg-center bg-no-repeat" style="background-image: url('{{ $formBgUrl }}'); background-size: {{ $bgSize }};"></div>
    @endif
    <div class="fixed inset-0 z-0 pointer-events-none" style="background-color: {{ $formBgColor }}; opacity: {{ $formBgOpacity }};"></div>

    @if(!request()->query('embed'))
        <div class="flex md:hidden relative z-10 w-full bg-[#2b0940] h-20 items-center justify-center shadow-md shrink-0">
            <img src="{{ Vite::asset('resources/images/logo-nav-white.svg') }}" class="max-h-11 object-contain" alt="Instituto Percorre">
        </div>
    @endif

    <div class="relative z-10 w-full {{ request()->query('embed') ? 'py-4' : 'py-12' }} px-4 sm:px-6 flex-1 flex flex-col justify-center">
        <div class="w-full mx-auto {{ $formWidth }} form-container">
            @if($inscricoesAbertas)
                @if($isPreview)
                    <div class="mb-6 p-4 bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-300 rounded-xl text-xs font-bold flex items-center justify-between shadow-sm">
                        <span class="flex items-center gap-2">
                            <i class="ph-fill ph-eye text-lg text-amber-600"></i> 
                            Modo de Pré-visualização: Pode avançar e voltar entre as etapas livremente sem preenchimento obrigatório.
                        </span>
                        <span class="px-2 py-0.5 bg-amber-500/20 text-amber-800 dark:text-amber-200 rounded text-[10px] font-extrabold uppercase tracking-wider">
                            Simulação
                        </span>
                    </div>
                @endif

                @if($isTeste)
                    <div class="mb-6 p-4 bg-amber-400 text-amber-900 rounded-xl text-xs font-bold flex items-center justify-between shadow-md ring-2 ring-amber-500 ring-offset-2 ring-offset-gray-50 dark:ring-offset-gray-900 z-50">
                        <span class="flex items-center gap-2">
                            <i class="ph-fill ph-flask text-lg text-amber-800"></i> 
                            MODO DE TESTE ATIVADO: A testar como um candidato real, mas nenhum dado será gravado na base de dados.
                        </span>
                        <button onclick="window.close()" class="px-3 py-1 bg-amber-900 text-amber-100 hover:bg-black hover:text-white transition-colors rounded text-[10px] font-extrabold uppercase tracking-wider">
                            Encerrar Teste
                        </button>
                    </div>
                @endif

                @if($etapaAtual <= $totalEtapas)
                    
                    @php
                        $totalExibido = $totalEtapas;
                        
                        if (!$temVagasDisponiveis && !empty($data_nascimento) && !empty($estado)) {
                            $totalExibido = 1;
                        } else {
                            if ($cicloAtivoId) {
                                $fasesFuturas = \App\Models\FormularioFase::where('ciclo_id', $cicloAtivoId)
                                    ->where('data_liberacao', '>', now())
                                    ->get();
                                
                                $paginasBloqueadas = [];
                                foreach ($fasesFuturas as $fase) {
                                    $paginas = is_string($fase->paginas) ? json_decode($fase->paginas, true) : $fase->paginas;
                                    if (is_array($paginas)) {
                                        $paginasBloqueadas = array_merge($paginasBloqueadas, $paginas);
                                    }
                                }
                                
                                if (!empty($paginasBloqueadas)) {
                                    $primeiraBloqueada = min($paginasBloqueadas);
                                    if ($primeiraBloqueada <= $totalEtapas && $primeiraBloqueada > 1) {
                                        $totalExibido = $primeiraBloqueada - 1;
                                    }
                                }
                            }
                        }

                        $totalExibido = max(1, max($etapaAtual, $totalExibido));
                    @endphp

                    <div class="mb-8 progresso-container flex flex-col items-center sm:items-start">
                        <div class="inline-flex items-center gap-2 bg-white/80 dark:bg-gray-900/80 backdrop-blur-md border border-gray-200/50 dark:border-white/10 shadow-lg text-gray-800 dark:text-white px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest mb-3 transition-colors duration-300">
                            <i class="ph-bold ph-list-numbers text-purpura-600 dark:text-yellow-400"></i>
                            Passo {{ $etapaAtual }} de {{ $totalExibido }}
                        </div>
                        
                        <div class="flex gap-2 w-full bg-white/60 dark:bg-gray-900/40 p-1.5 rounded-full backdrop-blur-md border border-gray-200/50 dark:border-white/10 shadow-inner transition-colors duration-300">
                            @for($i = 1; $i <= $totalExibido; $i++)
                                <div class="h-2 rounded-full w-full transition-all duration-500 {{ $etapaAtual >= $i ? 'bg-purpura-600 shadow-[0_0_8px_rgba(147,51,234,0.5)] dark:bg-yellow-400 dark:shadow-[0_0_10px_rgba(250,204,21,0.6)]' : 'bg-gray-300/80 dark:bg-white/20' }}"></div>
                            @endfor
                        </div>
                    </div>
                    
                    <div class="{{ $cardClass }} rounded-xl p-6 md:p-10 card-form transition-all duration-300">
                        
                        @if($this->bloqueioFaseAtual)
                            <div class="mb-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl text-xs font-bold flex items-start gap-3 shadow-sm text-amber-900 dark:text-amber-300">
                                <i class="ph-fill ph-lock-key text-xl text-amber-600 shrink-0 mt-0.5"></i>
                                <div>
                                    <p class="text-sm font-extrabold mb-1">Acesso Temporariamente Restrito</p>
                                    <p class="font-medium leading-relaxed">
                                        Esta fase <strong>({{ $this->bloqueioFaseAtual->nome }})</strong> 
                                        somente será liberada em <strong>{{ \Carbon\Carbon::parse($this->bloqueioFaseAtual->data_liberacao)->format('d/m/Y \à\s H:i') }}</strong>. 
                                        Os campos abaixo encontram-se em modo de visualização.
                                    </p>
                                </div>
                            </div>
                        @endif

                        <fieldset @if($this->bloqueioFaseAtual) disabled class="opacity-75 pointer-events-none" @endif>
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                                @if($etapaAtual === 1)
                                    @php
                                        $camposTopo = $camposDinamicos->where('etapa', 1)->filter(function($c) {
                                            $cfg = is_string($c->configuracoes) ? json_decode($c->configuracoes, true) : ($c->configuracoes ?? []);
                                            return isset($cfg['exibir_no_topo']) && $cfg['exibir_no_topo'] == true;
                                        });
                                        $camposRodape = $camposDinamicos->where('etapa', 1)->filter(function($c) {
                                            $cfg = is_string($c->configuracoes) ? json_decode($c->configuracoes, true) : ($c->configuracoes ?? []);
                                            return !isset($cfg['exibir_no_topo']) || $cfg['exibir_no_topo'] == false;
                                        });
                                    @endphp

                                    @if($camposTopo->count() > 0)
                                        <div class="col-span-12 mb-2 border-b border-gray-200 dark:border-gray-800 pb-2">
                                            <h4 class="text-xl font-bold {{ $textoForm }}">Informações Preliminares</h4>
                                        </div>
                                        
                                        @include('livewire.website.partials.render-dinamico', ['camposVigentes' => $camposTopo])
                                        
                                        @if($exibirDadosPessoaisFixos)
                                            <div class="col-span-12 mt-6 pt-4 border-t border-gray-200 dark:border-gray-800 mb-2 border-b pb-2">
                                                <h4 class="text-xl font-bold {{ $textoForm }}">Dados Pessoais</h4>
                                            </div>
                                        @endif
                                    @else
                                        @if($exibirDadosPessoaisFixos)
                                            <div class="col-span-12 mb-2 border-b border-gray-200 dark:border-gray-800 pb-2">
                                                <h4 class="text-xl font-bold {{ $textoForm }}">Dados Pessoais</h4>
                                            </div>
                                        @endif
                                    @endif

                                    @if($exibirDadosPessoaisFixos)
                                        @if(!empty($emailOriginal) || !empty($celularOriginal))
                                            <div class="col-span-12 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 flex items-start gap-3 shadow-xs">
                                                <i class="ph-fill ph-shield-check text-blue-600 dark:text-blue-400 text-2xl mt-0.5 shrink-0"></i>
                                                <div>
                                                    <h5 class="text-sm font-bold text-blue-900 dark:text-blue-200">Inscrição em andamento recuperada com segurança</h5>
                                                    <p class="text-xs text-blue-700 dark:text-blue-300 mt-0.5">
                                                        Para proteção dos seus dados (LGPD), seu e-mail e telefone aparecem parcialmente ocultos. Caso precise alterá-los, clique em <strong>"Alterar"</strong> acima do respectivo campo.
                                                    </p>
                                                </div>
                                            </div>
                                        @endif
                                    
                                        <div class="col-span-12">
                                            <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Nome Completo <span class="text-red-500">*</span></label>
                                            <input wire:model.live.debounce.500ms="nome" type="text" class="{{ $inputClassBase }} @error('nome') !border-red-500 !bg-red-50 dark:!bg-red-900/30 @else @if(!empty($nome)) !border-green-500 !bg-green-50 dark:!bg-green-900/20 @endif @enderror">
                                            @error('nome') <span class="text-red-500 dark:text-red-400 text-xs mt-1 font-bold">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="col-span-12">
                                            <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Possui Nome Social? <span class="text-red-500">*</span></label>
                                            <div class="flex gap-4">
                                                <label class="inline-flex items-center">
                                                    <input wire:model.live="possui_nome_social" type="radio" value="sim" class="form-radio text-purpura-600 focus:ring-purpura-500 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700">
                                                    <span class="ml-2 text-gray-700 dark:text-gray-300">Sim</span>
                                                </label>
                                                <label class="inline-flex items-center">
                                                    <input wire:model.live="possui_nome_social" type="radio" value="nao" class="form-radio text-purpura-600 focus:ring-purpura-500 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700">
                                                    <span class="ml-2 text-gray-700 dark:text-gray-300">Não</span>
                                                </label>
                                            </div>
                                        </div>

                                        @if($possui_nome_social === 'sim')
                                            <div class="col-span-12">
                                                <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Nome Social <span class="text-red-500">*</span></label>
                                                <input wire:model.live.debounce.500ms="nome_social" type="text" class="{{ $inputClassBase }} @error('nome_social') !border-red-500 !bg-red-50 dark:!bg-red-900/30 @else @if(!empty($nome_social)) !border-green-500 !bg-green-50 dark:!bg-green-900/20 @endif @enderror">
                                                @error('nome_social') <span class="text-red-500 dark:text-red-400 text-xs mt-1 font-bold">{{ $message }}</span> @enderror
                                            </div>
                                        @endif

                                        <div class="col-span-12 md:col-span-6">
                                            <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">CPF <span class="text-red-500">*</span></label>
                                            <input wire:model.live.debounce.500ms="cpf" x-mask="999.999.999-99" type="text" placeholder="000.000.000-00" class="{{ $inputClassBase }} @error('cpf') !border-red-500 !bg-red-50 dark:!bg-red-900/30 @else @if(!empty($cpf) && strlen($cpf) >= 14) !border-green-500 !bg-green-50 dark:!bg-green-900/20 @endif @enderror">
                                            @error('cpf') <span class="text-red-500 dark:text-red-400 text-xs mt-1 font-bold">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="col-span-12 md:col-span-6">
                                            <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Data de Nascimento <span class="text-red-500">*</span></label>
                                            <input wire:model.live="data_nascimento" type="date" class="{{ $inputClassBase }} @error('data_nascimento') !border-red-500 !bg-red-50 dark:!bg-red-900/30 @else @if(!empty($data_nascimento)) !border-green-500 !bg-green-50 dark:!bg-green-900/20 @endif @enderror">
                                            @error('data_nascimento') <span class="text-red-500 dark:text-red-400 text-xs mt-1 font-bold">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="col-span-12 md:col-span-6">
                                            <div class="flex items-center justify-between mb-1">
                                                <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200">Celular / Telefone <span class="text-red-500">*</span></label>
                                                @if(isset($editarCelular) && !$editarCelular && !empty($celularOriginal))
                                                    <button type="button" wire:click="$set('editarCelular', true); $set('celular', '');" class="text-xs font-bold text-purpura-600 hover:underline">
                                                        Alterar telefone
                                                    </button>
                                                @endif
                                            </div>
                                            @if(isset($editarCelular) && !$editarCelular && !empty($celularOriginal))
                                                <input type="text" value="{{ $celular }}" readonly class="w-full rounded-md border border-gray-200 dark:border-gray-700 px-3 py-2 bg-gray-100 dark:bg-gray-800/70 text-gray-600 dark:text-gray-300 font-mono cursor-not-allowed">
                                            @else
                                                <input wire:model.live.debounce.500ms="celular" x-mask:dynamic="$input.length > 14 ? '(99) 99999-9999' : '(99) 9999-9999'" type="text" placeholder="(00) 00000-0000" class="{{ $inputClassBase }} @if(!empty($celular)) !border-green-500 !bg-green-50 dark:!bg-green-900/20 @endif">
                                            @endif
                                            @error('celular') <span class="text-red-500 dark:text-red-400 text-xs mt-1 font-bold">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="col-span-12 md:col-span-6">
                                            <div class="flex items-center justify-between mb-1">
                                                <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200">Email <span class="text-red-500">*</span></label>
                                                @if(isset($editarEmail) && !$editarEmail && !empty($emailOriginal))
                                                    <button type="button" wire:click="$set('editarEmail', true); $set('email', '');" class="text-xs font-bold text-purpura-600 hover:underline">
                                                        Alterar e-mail
                                                    </button>
                                                @endif
                                            </div>
                                            @if(isset($editarEmail) && !$editarEmail && !empty($emailOriginal))
                                                <input type="text" value="{{ $email }}" readonly class="w-full rounded-md border border-gray-200 dark:border-gray-700 px-3 py-2 bg-gray-100 dark:bg-gray-800/70 text-gray-600 dark:text-gray-300 font-mono cursor-not-allowed">
                                            @else
                                                <input wire:model.live.debounce.300ms="email" type="email" placeholder="seu@exemplo.com" class="{{ $inputClassBase }} @error('email') !border-red-500 !bg-red-50 dark:!bg-red-900/30 @else @if(!empty($email)) !border-green-500 !bg-green-50 dark:!bg-green-900/20 @endif @enderror">
                                            @endif
                                            @error('email') <span class="text-red-500 dark:text-red-400 text-xs mt-1 font-bold">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="col-span-6 mt-2">
                                            <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Pessoa com Deficiência (PcD)? <span class="text-red-500">*</span></label>
                                            <div class="flex gap-4">
                                                <label class="inline-flex items-center">
                                                    <input wire:model.live="possui_deficiencia" type="radio" value="sim" class="form-radio text-purpura-600 focus:ring-purpura-500 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700">
                                                    <span class="ml-2 text-gray-700 dark:text-gray-300">Sim</span>
                                                </label>
                                                <label class="inline-flex items-center">
                                                    <input wire:model.live="possui_deficiencia" type="radio" value="nao" class="form-radio text-purpura-600 focus:ring-purpura-500 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700">
                                                    <span class="ml-2 text-gray-700 dark:text-gray-300">Não</span>
                                                </label>
                                            </div>
                                            @error('possui_deficiencia') <span class="text-red-500 dark:text-red-400 text-xs mt-1 font-bold">{{ $message }}</span> @enderror
                                        </div>

                                        @if($possui_deficiencia === 'sim')
                                            <div class="col-span-12">
                                                <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Qual a natureza da deficiência?</label>
                                                <select wire:model.live="natureza_deficiencia" class="{{ $inputClassBase }}">
                                                    <option value="">Selecione...</option>
                                                    <option value="fisica">Física</option>
                                                    <option value="auditiva">Auditiva</option>
                                                    <option value="visual">Visual</option>
                                                    <option value="intelectual">Intelectual</option>
                                                    <option value="multipla">Múltipla</option>
                                                </select>
                                            </div>
                                        @endif
                                    @endif

                                    @if($exibirEnderecoFixo)
                                        <div class="col-span-12 mt-6 pt-4 border-t border-gray-200 dark:border-gray-800 mb-2 border-b pb-2">
                                            <h4 class="text-xl font-bold {{ $textoForm }}">Endereço @if($exibirSelecaoCursoFixo) e Seleção de Curso @endif</h4>
                                        </div>

                                        <div class="col-span-12 md:col-span-2">
                                            <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">CEP <span class="text-red-500">*</span></label>
                                            <input wire:model.live.debounce.500ms="cep" x-mask="99999-999" type="text" placeholder="00000-000" class="{{ $inputClassBase }} @error('cep') !border-red-500 !bg-red-50 dark:!bg-red-900/30 @else @if(!empty($estado)) !border-green-500 !bg-green-50 dark:!bg-green-900/20 @endif @enderror">
                                            @error('cep') <span class="text-red-500 dark:text-red-400 text-xs mt-1 font-bold">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="col-span-12 md:col-span-8">
                                            <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Endereço</label>
                                            <input wire:model="logradouro" type="text" readonly class="w-full rounded-md border border-gray-200 dark:border-gray-700 px-3 py-2 bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400 focus:outline-none cursor-not-allowed">
                                        </div>

                                        <div class="col-span-12 md:col-span-2">
                                            <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Número</label>
                                            <input wire:model.live.debounce.500ms="numero" type="number" min="0" class="{{ $inputClassBase }}">
                                        </div>

                                        <div class="col-span-12 md:col-span-5">
                                            <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Bairro</label>
                                            <input wire:model="bairro" type="text" readonly class="w-full rounded-md border border-gray-200 dark:border-gray-700 px-3 py-2 bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400 focus:outline-none cursor-not-allowed">
                                        </div>

                                        <div class="col-span-12 md:col-span-5">
                                            <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Cidade</label>
                                            <input wire:model="cidade" type="text" readonly class="w-full rounded-md border border-gray-200 dark:border-gray-700 px-3 py-2 bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400 focus:outline-none cursor-not-allowed">
                                        </div>

                                        <div class="col-span-12 md:col-span-2">
                                            <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Estado</label>
                                            <input wire:model="estado" type="text" readonly class="w-full rounded-md border border-gray-200 dark:border-gray-700 px-3 py-2 bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400 focus:outline-none cursor-not-allowed">
                                        </div>

                                        <div class="col-span-6 md:col-span-6">
                                            <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Complemento</label>
                                            <input wire:model.live.debounce.500ms="complemento" type="text" class="{{ $inputClassBase }}">
                                        </div>

                                        <div class="col-span-6 md:col-span-6">
                                            <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Região</label>
                                            <select wire:model.live="regiao" class="{{ $inputClassBase }}">
                                                <option value="">Selecione...</option>
                                                <option value="centro">Centro</option>
                                                <option value="norte">Norte</option>
                                                <option value="sul">Sul</option>
                                                <option value="leste">Leste</option>
                                                <option value="oeste">Oeste</option>
                                            </select>
                                        </div>
                                    @endif

                                    @if($exibirSelecaoCursoFixo)
                                        @if(!$exibirEnderecoFixo)
                                            <div class="col-span-12 mt-6 pt-4 border-t border-gray-200 dark:border-gray-800 mb-2 border-b pb-2">
                                                <h4 class="text-xl font-bold {{ $textoForm }}">Seleção de Curso</h4>
                                            </div>
                                        @endif

                                        @if(!empty($cursosBarradosPorIdade))
                                            <div class="col-span-12 mt-2 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl space-y-3">
                                                <div class="flex items-start gap-2.5 text-amber-800 dark:text-amber-300">
                                                    <i class="ph-fill ph-cake text-xl text-amber-600 shrink-0 mt-0.5"></i>
                                                    <div class="text-xs leading-relaxed">
                                                        <p class="font-bold text-sm mb-1">Atenção ao limite de idade até o encerramento das matrículas:</p>
                                                        @foreach($cursosBarradosPorIdade as $b)
                                                            <p>• O curso <strong>{{ $b['curso_nome'] }}</strong> ({{ $b['unidade_nome'] }}) permite candidatos de até <strong>{{ $b['idade_max'] }} anos</strong>, porém você completará <strong>{{ $b['idade_futura'] }} anos</strong> até o término do período de matrícula ({{ $b['data_limite'] }}).</p>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                @if(!empty($sugestoesCursosIdade))
                                                    <div class="pt-2 border-t border-amber-200/70 dark:border-amber-800/60">
                                                        <p class="text-xs font-bold text-amber-900 dark:text-amber-200 mb-2">
                                                            Sugestões de cursos disponíveis compatíveis com a sua idade:
                                                        </p>
                                                        <div class="flex flex-wrap gap-2">
                                                            @foreach($sugestoesCursosIdade as $sug)
                                                                <button type="button"
                                                                        wire:click="selecionarSugestaoCurso({{ $sug['unidade_id'] }}, {{ $sug['curso_id'] }})"
                                                                        class="px-3 py-1.5 bg-white dark:bg-gray-800 hover:bg-purpura-50 border border-amber-300 hover:border-purpura-400 rounded-lg text-xs font-bold text-gray-800 dark:text-gray-200 shadow-sm transition flex items-center gap-1.5">
                                                                    <i class="ph-bold ph-graduation-cap text-purpura-600"></i>
                                                                    <span>{{ $sug['curso_nome'] }} — {{ $sug['unidade_nome'] }}</span>
                                                                    <span class="text-[10px] text-gray-400">({{ $sug['faixa'] }})</span>
                                                                </button>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    
                                        @if(empty($data_nascimento) || empty($estado))
                                            <div class="col-span-12 mt-4 p-5 bg-gray-100 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-md text-center">
                                                <p class="text-gray-600 dark:text-gray-300 text-sm m-0">Preencha sua <b>Data de Nascimento</b> e o <b>CEP</b> acima para visualizarmos os cursos disponíveis para o seu perfil e região.</p>
                                            </div>
                                        @elseif(count($unidadesDisponiveis) > 0)
                                            <div class="col-span-12 mt-4 p-5 bg-purpura-50/50 dark:bg-purpura-900/10 rounded-md border border-purpura-100 dark:border-purpura-900/50 grid grid-cols-1 gap-4">
                                                <h5 class="font-bold text-purpura-700 dark:text-purpura-400">Cursos Disponíveis para o seu perfil</h5>
                                                
                                                <div>
                                                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Unidade <span class="text-red-500">*</span></label>
                                                    <select wire:model.live="unidade" class="{{ $inputClassBase }} @error('unidade') !border-red-500 !bg-red-50 dark:!bg-red-900/30 @else @if(!empty($unidade)) !border-green-500 !bg-green-50 dark:!bg-green-900/20 @endif @enderror">
                                                        <option value="">Selecione a Unidade...</option>
                                                        @foreach($unidadesDisponiveis as $id => $nome)
                                                            <option value="{{ $id }}">{{ $nome }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                @if(count($cursosDisponiveis) > 0)
                                                    <div>
                                                        <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Curso de Interesse <span class="text-red-500">*</span></label>
                                                        <select wire:model.live="curso" class="{{ $inputClassBase }} @error('curso') !border-red-500 !bg-red-50 dark:!bg-red-900/30 @else @if(!empty($curso)) !border-green-500 !bg-green-50 dark:!bg-green-900/20 @endif @enderror">
                                                            <option value="">Selecione o Curso...</option>
                                                            @foreach($cursosDisponiveis as $id => $nome)
                                                                <option value="{{ $id }}">{{ $nome }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                @endif

                                                @if(count($turnosDisponiveis) > 0)
                                                    <div>
                                                        <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Turno <span class="text-red-500">*</span></label>
                                                        <select wire:model.live="turno" class="{{ $inputClassBase }} @error('turno') !border-red-500 !bg-red-50 dark:!bg-red-900/30 @else @if(!empty($turno)) !border-green-500 !bg-green-50 dark:!bg-green-900/20 @endif @enderror">
                                                            <option value="">Selecione o Turno...</option>
                                                            @foreach($turnosDisponiveis as $id => $nome)
                                                                <option value="{{ $id }}">{{ $nome }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="col-span-12 mt-4 p-5 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-900/50 rounded-md transition-all duration-300">
                                                <p class="text-red-600 dark:text-red-400 font-bold m-0 text-center">
                                                    Não há vagas disponíveis para o seu perfil e localidade selecionada no momento.
                                                </p>
                                                
                                                <div class="mt-4 pt-4 border-t border-red-200 dark:border-red-800/50 flex justify-center">
                                                    <label class="flex items-center gap-3 cursor-pointer group">
                                                        <input type="checkbox" wire:model.live="deseja_informar" class="w-5 h-5 text-purpura-600 border-gray-300 rounded focus:ring-purpura-500 bg-white dark:bg-gray-800 dark:border-gray-600 transition-colors">
                                                        <span class="text-sm font-bold text-gray-800 dark:text-gray-200 group-hover:text-purpura-600 dark:group-hover:text-purpura-400 transition-colors">Desejo registrar meu interesse para aberturas de futuras turmas</span>
                                                    </label>
                                                </div>

                                                @if($deseja_informar)
                                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6 animate-fade-in-down">
                                                        <div>
                                                            <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Unidade de Interesse</label>
                                                            <select wire:model.live="unidade_interesse" class="{{ $inputClassBase }} @error('unidade_interesse') !border-red-500 !bg-red-50 dark:!bg-red-900/30 @enderror">
                                                                <option value="">Selecione a Unidade...</option>
                                                                @foreach($unidadesInteresseDb ?? [] as $u)
                                                                    <option value="{{ $u->id }}">{{ $u->nome }}</option>
                                                                @endforeach
                                                            </select>
                                                            @error('unidade_interesse') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div>
                                                            <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Curso de Interesse <span class="text-red-500">*</span></label>
                                                            <select wire:model.live="curso_interesse" class="{{ $inputClassBase }} @error('curso_interesse') !border-red-500 !bg-red-50 dark:!bg-red-900/30 @enderror">
                                                                <option value="">Selecione o Curso...</option>
                                                                @foreach($cursosInteresseDb ?? [] as $c)
                                                                    <option value="{{ $c->id }}">{{ $c->nome }}</option>
                                                                @endforeach
                                                            </select>
                                                            @error('curso_interesse') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div>
                                                            <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Turno de Interesse <span class="text-red-500">*</span></label>
                                                            <select wire:model.live="turno_interesse" class="{{ $inputClassBase }} @error('turno_interesse') !border-red-500 !bg-red-50 dark:!bg-red-900/30 @enderror">
                                                                <option value="">Selecione o Turno...</option>
                                                                @foreach($turnosInteresseDb ?? [] as $t)
                                                                    <option value="{{ $t->id }}">{{ $t->nome }}</option>
                                                                @endforeach
                                                            </select>
                                                            @error('turno_interesse') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    @endif
                                @endif

                                @if($etapaAtual === 1 && isset($camposRodape) && $camposRodape->count() > 0)
                                    <div class="col-span-12 mt-6 pt-4 border-t border-gray-200 dark:border-gray-800 mb-2 border-b pb-2">
                                        <h4 class="text-xl font-bold {{ $textoForm }}">Informações Adicionais</h4>
                                    </div>
                                    @include('livewire.website.partials.render-dinamico', ['camposVigentes' => $camposRodape])
                                    
                                @elseif($etapaAtual > 1 && $camposDinamicos && $camposDinamicos->where('etapa', $etapaAtual)->count() > 0)
                                    <div class="col-span-12 mt-6 pt-4 border-t border-gray-200 dark:border-gray-800 mb-2 border-b pb-2">
                                        <h4 class="text-xl font-bold {{ $textoForm }}">Informações Complementares</h4>
                                    </div>
                                    @include('livewire.website.partials.render-dinamico', ['camposVigentes' => $camposDinamicos->where('etapa', $etapaAtual)])
                                @endif
                                
                                @if($etapaAtual === $totalEtapas)
                                    <div class="col-span-12 mt-6 pt-4 border-t border-gray-200 dark:border-gray-800">
                                        <h4 class="text-xl font-bold {{ $textoForm }} mb-4">Informações Finais</h4>
                                        <div class="p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md">
                                            <div class="flex items-start">
                                                <div class="flex items-center h-5">
                                                    <input wire:model.live="autorizacao_uso_infos" id="checkTermos" type="checkbox" class="focus:ring-purpura-500 h-4 w-4 text-purpura-600 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded">
                                                </div>
                                                <div class="ml-3 text-sm">
                                                    <label for="checkTermos" class="font-bold text-gray-800 dark:text-gray-200 text-xs uppercase cursor-pointer">
                                                        Autorizo uso das informações para fins educacionais e de empregabilidade, conforme política de privacidade.
                                                    </label>
                                                    @error('autorizacao_uso_infos') <p class="text-red-500 dark:text-red-400 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </fieldset>
                        
                        <div class="flex justify-between mt-8 pt-6 border-t border-gray-200 dark:border-gray-800">
                            @if($etapaAtual > 1)
                                <button type="button" wire:click="voltarEtapa" wire:loading.attr="disabled" wire:target="avancarEtapa, voltarEtapa, uploads" class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 font-bold py-2 px-6 rounded-md transition duration-200 disabled:opacity-50">Voltar</button>
                            @else
                                <div></div>
                            @endif
                            
                            @if($this->bloqueioFaseAtual)
                                <div></div>
                            @elseif($this->bloqueioFaseSeguinte)
                                <div class="px-4 py-3 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-xs font-bold flex items-center gap-2 shadow-sm dark:bg-amber-900/30 dark:border-amber-800 dark:text-amber-300 w-full sm:w-auto">
                                    <i class="ph-fill ph-lock-key text-lg text-amber-500 shrink-0"></i>
                                    <span>
                                        O seu progresso está salvo! A fase seguinte <strong>({{ $this->bloqueioFaseSeguinte->nome }})</strong> 
                                        será liberada em {{ \Carbon\Carbon::parse($this->bloqueioFaseSeguinte->data_liberacao)->format('d/m/Y \à\s H:i') }}. O botão de avançar reaparecerá no horário estipulado.
                                    </span>
                                </div>
                            @else
                                <button type="button" wire:click="avancarEtapa" wire:loading.attr="disabled" wire:target="avancarEtapa, uploads" class="text-white transition-colors rounded-lg shadow-sm bg-purpura-600 hover:bg-purpura-700 font-bold py-2.5 px-6 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span wire:loading.remove wire:target="avancarEtapa, uploads">
                                        @if(($isPreview || $isTeste) && $etapaAtual === $totalEtapas)
                                            Finalizar Simulação
                                        @else
                                            {{ $etapaAtual === $totalEtapas ? 'Finalizar Inscrição' : 'Próximo Passo' }}
                                        @endif
                                    </span>
                                    <span wire:loading wire:target="uploads" class="inline-flex items-center gap-2">
                                        <i class="ph-bold ph-spinner animate-spin"></i> A carregar anexo(s)...
                                    </span>
                                    <span wire:loading wire:target="avancarEtapa" class="inline-flex items-center gap-2">
                                        <i class="ph-bold ph-spinner animate-spin"></i> A processar...
                                    </span>
                                </button>
                            @endif
                        </div>
                    </div>
                @endif
                
                @if($etapaAtual === 99)
                <div class="{{ $cardClass }} text-center p-8 md:p-12 rounded-xl border-t-4 border-green-500 mt-10">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 text-green-600 mb-4 shadow-sm">
                        <i class="ph-bold ph-check text-3xl"></i>
                    </div>
                    <h2 class="text-3xl font-extrabold {{ $textoForm }} mb-4">Inscrição Concluída!</h2>
                    <p class="text-gray-700 dark:text-gray-300 mb-8 leading-relaxed font-medium">
                        Olá! Recebemos a sua inscrição!<br>
                        Fique de olho em nossas redes sociais, em breve divulgaremos a lista de selecionados.<br>
                        Qualquer dúvida, acione nossa Central de Atendimento. Desejamos muito sucesso!
                    </p>
                    <button type="button" onclick="window.location.reload()" class="bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-black dark:hover:bg-gray-200 text-white font-bold py-3 px-8 rounded-lg shadow-md transition duration-200">Voltar ao Início</button>
                </div>
                @endif

                @if($etapaAtual === 100)
                <div class="{{ $cardClass }} text-center p-8 md:p-12 rounded-xl border-t-4 border-yellow-500 mt-10">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-yellow-100 text-yellow-600 mb-4 shadow-sm">
                        <i class="ph-fill ph-warning-circle text-3xl"></i>
                    </div>
                    <h2 class="text-3xl font-extrabold {{ $textoForm }} mb-4">Inscrição na Lista de Espera!</h2>
                    <p class="text-gray-700 dark:text-gray-300 mb-8 leading-relaxed font-medium">
                        Seus dados foram salvos no banco de dados. Enviaremos uma notificação assim que novas vagas forem abertas para o seu perfil e localidade.
                    </p>
                    <button type="button" onclick="window.location.reload()" class="bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-black dark:hover:bg-gray-200 text-white font-bold py-3 px-8 rounded-lg shadow-md transition duration-200">Voltar ao Início</button>
                </div>
                @endif
                
            @else
                <div class="{{ $cardClass }} rounded-xl border-t-4 border-red-500 p-8 md:p-12 text-center mt-10">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-red-50 text-red-500 mb-6 shadow-sm">
                        <i class="ph-fill ph-x-circle text-4xl"></i>
                    </div>
                    
                    <h2 class="text-3xl font-extrabold {{ $textoForm }} mb-4">Inscrições Fechadas</h2>
                    <p class="text-gray-700 dark:text-gray-300 mb-8 leading-relaxed text-lg font-medium">
                        No momento, não temos nenhum processo seletivo com inscrições abertas.<br>
                        Fique de olho em nossas redes sociais para não perder as próximas datas!
                    </p>
                    @if(!request()->query('embed'))
                        <a href="/" class="inline-block bg-purpura-600 hover:bg-purpura-700 text-white font-bold py-3 px-8 rounded-lg transition duration-200 shadow-sm">
                            Voltar ao Início
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>