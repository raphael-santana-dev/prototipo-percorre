@if($exibirDadosPessoaisFixos)
    <div class="col-span-12 {{ isset($camposTopo) && $camposTopo->count() > 0 ? 'mt-6 pt-4 border-t' : '' }} mb-2 border-b border-gray-200 dark:border-gray-800 pb-2">
        <h4 class="text-xl font-bold {{ $textoForm }}">Dados Pessoais</h4>
    </div>

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
            <label class="inline-flex items-center cursor-pointer">
                <input wire:model.live="possui_nome_social" type="radio" value="sim" class="form-radio text-purpura-600 focus:ring-purpura-500 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700">
                <span class="ml-2 text-gray-700 dark:text-gray-300">Sim</span>
            </label>
            <label class="inline-flex items-center cursor-pointer">
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
            <input wire:model.live.debounce.500ms="celular" x-mask:dynamic="$input.length > 14 ? '(99) 99999-9999' : '(99) 9999-9999'" type="text" placeholder="(00) 00000-0000" class="{{ $inputClassBase }} @error('celular') !border-red-500 !bg-red-50 dark:!bg-red-900/30 @else @if(!empty($celular) && strlen($celular) >= 14) !border-green-500 !bg-green-50 dark:!bg-green-900/20 @endif @enderror">
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

    <div class="col-span-12 mt-2">
        <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Pessoa com Deficiência (PcD)? <span class="text-red-500">*</span></label>
        <div class="flex gap-4">
            <label class="inline-flex items-center cursor-pointer">
                <input wire:model.live="possui_deficiencia" type="radio" value="sim" class="form-radio text-purpura-600 focus:ring-purpura-500 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700">
                <span class="ml-2 text-gray-700 dark:text-gray-300">Sim</span>
            </label>
            <label class="inline-flex items-center cursor-pointer">
                <input wire:model.live="possui_deficiencia" type="radio" value="nao" class="form-radio text-purpura-600 focus:ring-purpura-500 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700">
                <span class="ml-2 text-gray-700 dark:text-gray-300">Não</span>
            </label>
        </div>
        @error('possui_deficiencia') <span class="text-red-500 dark:text-red-400 text-xs mt-1 font-bold">{{ $message }}</span> @enderror
    </div>

    @if($possui_deficiencia === 'sim')
        <div class="col-span-12 md:col-span-6">
            <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Qual a natureza da deficiência? <span class="text-red-500">*</span></label>
            <select wire:model.live="natureza_deficiencia" class="{{ $inputClassBase }} @error('natureza_deficiencia') !border-red-500 !bg-red-50 dark:!bg-red-900/30 @enderror">
                <option value="">Selecione...</option>
                <option value="fisica">Física</option>
                <option value="auditiva">Auditiva</option>
                <option value="visual">Visual</option>
                <option value="intelectual">Intelectual</option>
                <option value="multipla">Múltipla</option>
            </select>
            @error('natureza_deficiencia') <span class="text-red-500 dark:text-red-400 text-xs mt-1 font-bold">{{ $message }}</span> @enderror
        </div>

        {{-- NOVO CAMPO: UPLOAD DO LAUDO MÉDICO (OPCIONAL, PDF OU IMAGEM) --}}
        <div class="col-span-12 md:col-span-6">
            <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                Anexar Laudo Médico (Opcional — PDF ou Imagem)
            </label>

            <div class="space-y-2">
                <div class="relative border border-dashed border-gray-300 dark:border-gray-700 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 hover:border-purpura-400 transition">
                    <input type="file"
                           wire:model="laudo_pcd_upload"
                           accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/*"
                           class="block w-full text-xs text-gray-600 dark:text-gray-300 file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-bold file:bg-purpura-50 file:text-purpura-700 hover:file:bg-purpura-100 cursor-pointer">
                </div>

                <div wire:loading wire:target="laudo_pcd_upload" class="text-xs text-purpura-600 dark:text-purpura-400 font-semibold flex items-center gap-1.5">
                    <i class="ph-bold ph-spinner animate-spin"></i> Carregando laudo, aguarde...
                </div>

                @if(!empty($respostas['laudo_pcd']))
                    <div class="flex items-center justify-between p-2 rounded-md bg-emerald-50/70 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-xs">
                        <div class="flex items-center gap-2 truncate">
                            <i class="ph-fill ph-check-circle text-emerald-600 text-base shrink-0"></i>
                            <span class="truncate text-gray-800 dark:text-gray-200 font-medium">{{ basename($respostas['laudo_pcd']) }}</span>
                        </div>
                        <div class="flex items-center gap-2 shrink-0 ml-2">
                            <a href="{{ asset('storage/' . $respostas['laudo_pcd']) }}" target="_blank" class="text-purpura-600 dark:text-purpura-400 font-bold hover:underline">
                                Ver ↗
                            </a>
                            <button type="button" wire:click="removerLaudoPcd" class="text-red-500 hover:text-red-700 p-1" title="Remover laudo">
                                <i class="ph-bold ph-trash"></i>
                            </button>
                        </div>
                    </div>
                @endif

                <p class="text-xs text-amber-700 dark:text-amber-400 leading-relaxed flex items-start gap-1.5 mt-1">
                    <i class="ph-fill ph-info text-sm shrink-0 mt-0.5"></i>
                    <span>Caso não envie o arquivo do laudo neste momento, ele será exigido futuramente para a efetivação da matrícula. Formatos aceitos: PDF, JPG ou PNG (máx. 10MB).</span>
                </p>

                @error('laudo_pcd_upload') <span class="text-red-500 dark:text-red-400 text-xs font-bold block">{{ $message }}</span> @enderror
            </div>
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
            <p class="text-gray-600 dark:text-gray-300 text-sm m-0">Preencha sua <b>Data de Nascimento</b> e o <b>CEP</b> para visualizarmos os cursos disponíveis para o seu perfil e região.</p>
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
                @error('unidade') <span class="text-red-500 text-xs mt-1 font-bold block">{{ $message }}</span> @enderror
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
                    @error('curso') <span class="text-red-500 text-xs mt-1 font-bold block">{{ $message }}</span> @enderror
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
                    @error('turno') <span class="text-red-500 text-xs mt-1 font-bold block">{{ $message }}</span> @enderror
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