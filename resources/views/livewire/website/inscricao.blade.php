@if($isEmbed ?? request()->boolean('embed'))
    <style>
        header, footer, nav, aside, .navbar, .site-header, .site-footer, .js-topnav, .js-sidebar {
            display: none !important;
        }
        body, main {
            padding-top: 0 !important;
            margin-top: 0 !important;
            background: transparent !important;
        }
    </style>
@endif

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
                                @php
                                    $paginaFixos = (int) ($etapaCamposFixos ?? ($formSettings['fixed_fields_etapa'] ?? 1));
                                @endphp

                                @if((int) $etapaAtual === $paginaFixos)
                                    @php
                                        $camposTopo = $camposDinamicos->where('etapa', $etapaAtual)->filter(function($c) {
                                            $cfg = is_string($c->configuracoes) ? json_decode($c->configuracoes, true) : ($c->configuracoes ?? []);
                                            return !empty($cfg['exibir_no_topo']);
                                        });
                                        $camposRodape = $camposDinamicos->where('etapa', $etapaAtual)->filter(function($c) {
                                            $cfg = is_string($c->configuracoes) ? json_decode($c->configuracoes, true) : ($c->configuracoes ?? []);
                                            return empty($cfg['exibir_no_topo']);
                                        });
                                    @endphp

                                    @if($camposTopo->count() > 0)
                                        <div class="col-span-12 mb-2 border-b border-gray-200 dark:border-gray-800 pb-2">
                                            <h4 class="text-xl font-bold {{ $textoForm }}">Informações Preliminares</h4>
                                        </div>
                                        @include('livewire.website.partials.render-dinamico', ['camposVigentes' => $camposTopo])
                                    @endif

                                    {{-- PARTIAL DOS CAMPOS FIXOS (PODE ESTAR EM QUALQUER PÁGINA ESCOLHIDA) --}}
                                    @include('livewire.website.partials.campos-fixos-inscricao')

                                    @if($camposRodape->count() > 0)
                                        <div class="col-span-12 mt-6 pt-4 border-t border-gray-200 dark:border-gray-800 mb-2 border-b pb-2">
                                            <h4 class="text-xl font-bold {{ $textoForm }}">Informações Adicionais</h4>
                                        </div>
                                        @include('livewire.website.partials.render-dinamico', ['camposVigentes' => $camposRodape])
                                    @endif

                                @elseif($camposDinamicos && $camposDinamicos->where('etapa', $etapaAtual)->count() > 0)
                                    <div class="col-span-12 mb-2 border-b border-gray-200 dark:border-gray-800 pb-2">
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
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                        <button type="button" wire:click="baixarFichaPdf" wire:loading.attr="disabled" class="bg-purpura-600 hover:bg-purpura-700 text-white font-bold py-3 px-6 rounded-lg shadow-md transition duration-200 inline-flex items-center gap-2">
                            <i class="ph-bold ph-file-pdf text-lg" wire:loading.remove wire:target="baixarFichaPdf"></i>
                            <i class="ph-bold ph-spinner animate-spin text-lg" wire:loading wire:target="baixarFichaPdf"></i>
                            <span>Baixar Ficha de Inscrição (PDF)</span>
                        </button>
                        @if(!($isEmbed ?? false))
                            <button type="button" onclick="window.location.reload()" class="bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-black dark:hover:bg-gray-200 text-white font-bold py-3 px-8 rounded-lg shadow-md transition duration-200">
                                Voltar ao Início
                            </button>
                        @endif
                    </div>
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
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                        <button type="button" wire:click="baixarFichaPdf" wire:loading.attr="disabled" class="bg-purpura-600 hover:bg-purpura-700 text-white font-bold py-3 px-6 rounded-lg shadow-md transition duration-200 inline-flex items-center gap-2">
                            <i class="ph-bold ph-file-pdf text-lg" wire:loading.remove wire:target="baixarFichaPdf"></i>
                            <i class="ph-bold ph-spinner animate-spin text-lg" wire:loading wire:target="baixarFichaPdf"></i>
                            <span>Baixar Comprovante (PDF)</span>
                        </button>
                        <button type="button" onclick="window.location.reload()" class="bg-gray-900 dark:bg-white dark:text-gray-900 hover:bg-black dark:hover:bg-gray-200 text-white font-bold py-3 px-8 rounded-lg shadow-md transition duration-200">
                            Voltar ao Início
                        </button>
                    </div>
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