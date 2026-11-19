<div class="max-w-4xl mx-auto px-4 py-6 space-y-6 font-sans">
    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-800 pb-4">
        <div>
            <a href="{{ route('student.dashboard') }}" wire:navigate class="text-purpura-600 dark:text-purpura-400 hover:underline text-xs font-semibold inline-flex items-center gap-1 mb-1">
                <i class="ph ph-arrow-left"></i> Voltar ao Painel
            </a>
            <h1 class="text-lg font-bold text-gray-900 dark:text-white">
                Envio de Documentação e Laudo
            </h1>
        </div>
    </div>

    @if(!$this->inscricaoAtual)
        <div class="p-8 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 text-center text-xs text-gray-500">
            Nenhuma inscrição vinculada ao seu perfil.
        </div>
    @elseif(!$this->uploadHabilitadoPeloStatus)
        <div class="p-6 bg-amber-50 dark:bg-amber-950/30 rounded-lg border border-amber-200 dark:border-amber-800 flex items-start gap-3">
            <i class="ph-fill ph-lock-key text-xl text-amber-600 shrink-0 mt-0.5"></i>
            <div class="text-xs leading-relaxed text-amber-900 dark:text-amber-200">
                <span class="font-bold block text-sm mb-1">Envio de Documentação Aguardando Liberação</span>
                O envio dos documentos para o seu ciclo será liberado assim que sua inscrição atingir o status
                <strong>{{ $this->nomeStatusExigido ?: 'de matrícula' }}</strong>.
                Acompanhe o andamento pelo seu painel principal.
            </div>
        </div>
    @else
        {{-- BLOCO 1: UPLOAD DE LAUDO MÉDICO PCD (SE O CANDIDATO DECLAROU DEFICIÊNCIA) --}}
        @php
            $insc = $this->inscricaoAtual;
            $isPcd = in_array(strtolower((string) ($insc->possui_deficiencia ?? 'nao')), ['sim', '1', 'true', 's'], true);
            $din = is_array($insc->dados_dinamicos) ? $insc->dados_dinamicos : (json_decode($insc->dados_dinamicos ?? '{}', true) ?: []);
            $laudoSalvo = $din['laudo_pcd'] ?? null;
        @endphp

        @if($isPcd)
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-5 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-gray-100 dark:border-gray-700 pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                            Laudo Médico PcD ({{ ucfirst($insc->natureza_deficiencia ?: 'Deficiência declarada') }})
                        </h3>
                        <p class="text-xs text-gray-500">Envie ou atualize o seu laudo médico em formato PDF ou Imagem (máx. 10MB).</p>
                    </div>
                    @if($laudoSalvo)
                        <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold uppercase rounded w-max">
                            Laudo Anexado
                        </span>
                    @else
                        <span class="px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold uppercase rounded w-max">
                            Pendente de Envio
                        </span>
                    @endif
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
                    @if($laudoSalvo)
                        <div class="flex items-center gap-2 text-xs">
                            <i class="ph ph-file-text text-purpura-600 text-base"></i>
                            <span class="font-mono text-gray-700 dark:text-gray-300 truncate max-w-xs">{{ basename($laudoSalvo) }}</span>
                            <a href="{{ asset('storage/' . $laudoSalvo) }}" target="_blank" class="text-purpura-600 font-semibold hover:underline ml-2">
                                Visualizar ↗
                            </a>
                        </div>
                    @else
                        <span class="text-xs text-gray-500">Nenhum arquivo de laudo enviado até o momento.</span>
                    @endif

                    <label class="px-3.5 py-2 bg-gray-900 hover:bg-black text-white rounded-lg text-xs font-semibold cursor-pointer transition inline-flex items-center gap-1.5 w-max">
                        <i class="ph ph-upload-simple"></i>
                        <span wire:loading.remove wire:target="laudoPcdUpload">{{ $laudoSalvo ? 'Substituir Laudo' : 'Enviar Laudo PcD' }}</span>
                        <span wire:loading wire:target="laudoPcdUpload">Enviando...</span>
                        <input type="file" wire:model="laudoPcdUpload" accept=".pdf,.jpg,.jpeg,.png,.webp" class="hidden">
                    </label>
                </div>
                @error('laudoPcdUpload') <span class="text-xs text-red-600 font-semibold block">{{ $message }}</span> @enderror
            </div>
        @endif

        {{-- BLOCO 2: DOCUMENTOS EXIGIDOS DO CICLO --}}
        @if(count($this->documentosExigidos) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Documentos de Matrícula</h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Curso: <strong class="text-gray-800 dark:text-gray-200">{{ $insc->curso->nome ?? 'Não definido' }}</strong>
                    </p>
                </div>

                <div class="p-5 space-y-3">
                    @php $podeFinalizar = true; @endphp

                    @foreach($this->documentosExigidos as $doc)
                        @php
                            $statusInfo = $arquivosEnviados[$doc->id] ?? ['status' => 'pendente'];
                            $status = $statusInfo['status'];
                            if ($doc->is_obrigatorio && in_array($status, ['pendente', 'invalido_ia', 'reprovado_manual'])) {
                                $podeFinalizar = false;
                            }
                        @endphp

                        <div class="p-4 rounded-lg border {{ in_array($status, ['valido_ia', 'aprovado_manual']) ? 'border-emerald-200 bg-emerald-50/20 dark:border-emerald-800' : 'border-gray-200 dark:border-gray-700 bg-gray-50/40 dark:bg-gray-900/40' }} space-y-2.5">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-xs font-bold text-gray-900 dark:text-white">{{ $doc->nome }}</h4>
                                        @if($doc->is_obrigatorio)
                                            <span class="px-1.5 py-0.5 text-[9px] font-bold uppercase bg-red-50 text-red-600 border border-red-200 rounded">Obrigatório</span>
                                        @endif
                                    </div>
                                    @if($doc->descricao)
                                        <p class="text-[11px] text-gray-500 mt-0.5">{{ $doc->descricao }}</p>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    @if(in_array($status, ['valido_ia', 'aprovado_manual']))
                                        <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold uppercase rounded">
                                            Aprovado
                                        </span>
                                    @elseif($status === 'analise_manual')
                                        <span class="px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold uppercase rounded">
                                            Em Análise
                                        </span>
                                    @else
                                        <label class="px-3 py-1.5 bg-white dark:bg-gray-800 hover:bg-gray-50 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 rounded-lg text-xs font-semibold cursor-pointer transition inline-flex items-center gap-1.5">
                                            <i class="ph ph-upload-simple"></i>
                                            <span wire:loading.remove wire:target="uploads.{{ $doc->id }}">Enviar Arquivo</span>
                                            <span wire:loading wire:target="uploads.{{ $doc->id }}">Analisando...</span>
                                            <input type="file" wire:model="uploads.{{ $doc->id }}" accept=".pdf,.jpg,.jpeg,.png,.webp" class="hidden">
                                        </label>
                                    @endif
                                </div>
                            </div>

                            @if($status === 'invalido_ia' && !empty($statusInfo['motivo_rejeicao']))
                                <div class="p-2.5 rounded bg-red-50 border border-red-200 text-xs text-red-700">
                                    <strong>Documento recusado (Tentativa {{ $statusInfo['tentativas'] }}/3):</strong> {{ $statusInfo['motivo_rejeicao'] }}
                                </div>
                            @elseif($status === 'reprovado_manual')
                                <div class="p-2.5 rounded bg-red-50 border border-red-200 text-xs text-red-700">
                                    <strong>Reprovado pela Secretaria:</strong> {{ $statusInfo['motivo_rejeicao_humana'] ?: 'Envie um novo documento legível.' }}
                                </div>
                            @endif
                        </div>
                    @endforeach

                    <div class="pt-3 flex justify-end">
                        <button type="button"
                                wire:click="finalizarMatricula"
                                @disabled(!$podeFinalizar)
                                class="px-5 py-2.5 rounded-lg text-xs font-semibold transition {{ $podeFinalizar ? 'bg-purpura-600 hover:bg-purpura-700 text-white cursor-pointer' : 'bg-gray-100 text-gray-400 border border-gray-200 cursor-not-allowed' }}">
                            Concluir Envio para a Secretaria
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>