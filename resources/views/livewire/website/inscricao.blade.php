<div class="flex flex-col md:flex-row w-full bg-brand-bg">
    
    {{-- Topo Mobile --}}
    <div class="flex md:hidden w-full bg-brand-escuro h-20 items-center justify-center shadow-md shrink-0">
        <img src="{{ Vite::asset('resources/images/logo-nav-white.svg') }}" class="max-h-11 object-contain" alt="Instituto Percorre">
    </div>

    <div class="w-full p-6 md:p-12 py-12 md:py-16 bg-brand-bg painel-direito">
        <div class="max-w-3xl mx-auto form-container">
            @if($inscricoesAbertas)
                
                @if($etapaAtual <= $totalEtapas)
                    <div class="mb-8 progresso-container">
                        <div class="font-bold text-gray-700 mb-2 text-sm">Passo {{ $etapaAtual }} de {{ $totalEtapas }}</div>
                        <div class="flex gap-2">
                            @for($i = 1; $i <= $totalEtapas; $i++)
                                <div class="h-2 rounded-full w-full {{ 1 >= $i ? 'bg-yellow-400' : 'bg-gray-200' }}"></div>
                            @endfor
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-md border-none p-6 md:p-10 card-form">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                            @if($etapaAtual === 1)
                                <div class="col-span-12 mb-2 border-b pb-2">
                                    <h4 class="text-xl font-bold text-gray-800">Dados Pessoais</h4>
                                </div>
                            
                                <div class="col-span-12">
                                    <label class="block text-sm font-semibold text-brand-textLabel mb-1">Nome Completo <span class="text-red-500">*</span></label>
                                    <input wire:model.live.debounce.500ms="nome" type="text" class="w-full rounded-md border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-purple/25 focus:border-brand-purple transition-colors @error('nome') border-red-500 bg-red-50 @else @if(!empty($nome)) border-green-500 bg-green-50 @else border-brand-border @endif @enderror">
                                    @error('nome') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-span-12">
                                    <label class="block text-sm font-semibold text-brand-textLabel mb-2">Possui Nome Social? <span class="text-red-500">*</span></label>
                                    <div class="flex gap-4">
                                        <label class="inline-flex items-center">
                                            <input wire:model.live="possui_nome_social" type="radio" value="sim" class="form-radio text-brand-purple focus:ring-brand-purple">
                                            <span class="ml-2">Sim</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input wire:model.live="possui_nome_social" type="radio" value="nao" class="form-radio text-brand-purple focus:ring-brand-purple">
                                            <span class="ml-2">Não</span>
                                        </label>
                                    </div>
                                </div>

                                @if($possui_nome_social === 'sim')
                                    <div class="col-span-12">
                                        <label class="block text-sm font-semibold text-brand-textLabel mb-1">Nome Social <span class="text-red-500">*</span></label>
                                        <input wire:model.live.debounce.500ms="nome_social" type="text" class="w-full rounded-md border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-purple/25 focus:border-brand-purple transition-colors @error('nome_social') border-red-500 bg-red-50 @else @if(!empty($nome_social)) border-green-500 bg-green-50 @else border-brand-border @endif @enderror">
                                        @error('nome_social') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                @endif

                                <div class="col-span-12 md:col-span-6">
                                    <label class="block text-sm font-semibold text-brand-textLabel mb-1">CPF <span class="text-red-500">*</span></label>
                                    <input wire:model.live.debounce.500ms="cpf" x-mask="999.999.999-99" type="text" placeholder="000.000.000-00" class="w-full rounded-md border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-purple/25 focus:border-brand-purple transition-colors @error('cpf') border-red-500 bg-red-50 @else @if(!empty($cpf) && strlen($cpf) >= 14) border-green-500 bg-green-50 @else border-brand-border @endif @enderror">
                                    @error('cpf') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-span-6 md:col-span-6">
                                    <label class="block text-sm font-semibold text-brand-textLabel mb-1">Data de Nascimento <span class="text-red-500">*</span></label>
                                    <input wire:model.live="data_nascimento" type="date" class="w-full rounded-md border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-purple/25 focus:border-brand-purple transition-colors @error('data_nascimento') border-red-500 bg-red-50 @else @if(!empty($data_nascimento)) border-green-500 bg-green-50 @else border-brand-border @endif @enderror">
                                    @error('data_nascimento') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-span-6 mt-2">
                                    <label class="block text-sm font-semibold text-brand-textLabel mb-2">Pessoa com Deficiência (PcD)? <span class="text-red-500">*</span></label>
                                    <div class="flex gap-4">
                                        <label class="inline-flex items-center">
                                            <input wire:model.live="possui_deficiencia" type="radio" value="sim" class="form-radio text-brand-purple focus:ring-brand-purple">
                                            <span class="ml-2">Sim</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input wire:model.live="possui_deficiencia" type="radio" value="nao" class="form-radio text-brand-purple focus:ring-brand-purple">
                                            <span class="ml-2">Não</span>
                                        </label>
                                    </div>
                                    @error('possui_deficiencia') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                @if($possui_deficiencia === 'sim')
                                    <div class="col-span-12">
                                        <label class="block text-sm font-semibold text-brand-textLabel mb-1">Qual a natureza da deficiência?</label>
                                        <select wire:model.live="nature_deficiencia" class="w-full rounded-md border px-3 py-2 focus:ring-brand-purple @error('unidade') border-red-500 @else @if(!empty($unidade)) border-green-500 @else border-brand-border @endif @enderror">
                                            <option value="">Selecione...</option>
                                            <option value="fisica">Física</option>
                                            <option value="auditiva">Auditiva</option>
                                            <option value="visual">Visual</option>
                                            <option value="intelectual">Intelectual</option>
                                            <option value="multipla">Múltipla</option>
                                        </select>
                                    </div>
                                @endif

                                <div class="col-span-12 md:col-span-6">
                                    <label class="block text-sm font-semibold text-brand-textLabel mb-1">Celular / Telefone</label>
                                    <input wire:model="celular" x-mask:dynamic="$input.length > 14 ? '(99) 99999-9999' : '(99) 9999-9999'" type="text" placeholder="(00) 00000-0000" class="w-full rounded-md border border-brand-border px-3 py-2 focus:outline-none focus:border-brand-purple focus:ring-2 focus:ring-brand-purple/25 @if(!empty($celular)) border-green-500 bg-green-50 @endif">
                                </div>

                                <div class="col-span-12 md:col-span-6">
                                    <label class="block text-sm font-semibold text-brand-textLabel mb-1">Email <span class="text-red-500">*</span></label>
                                    <input wire:model.live.debounce.300ms="email" type="email" placeholder="seu@exemplo.com" class="w-full rounded-md border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-purple/25 focus:border-brand-purple transition-colors @error('email') border-red-500 bg-red-50 @else @if(!empty($email)) border-green-500 bg-green-50 @else border-brand-border @endif @enderror">
                                    @error('email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-span-12 mt-6 pt-4 border-t border-gray-100 mb-2 border-b pb-2">
                                    <h4 class="text-xl font-bold text-gray-800">Endereço e Seleção de Curso</h4>
                                </div>

                                <div class="col-span-12 md:col-span-2">
                                    <label class="block text-sm font-semibold text-brand-textLabel mb-1">CEP <span class="text-red-500">*</span></label>
                                    <input wire:model.live.debounce.500ms="cep" x-mask="99999-999" type="text" placeholder="00000-000" class="w-full rounded-md border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-purple/25 focus:border-brand-purple transition-colors @error('cep') border-red-500 bg-red-50 @else @if(!empty($estado)) border-green-500 bg-green-50 @else border-brand-border @endif @enderror">
                                    @error('cep') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-span-12 md:col-span-8">
                                    <label class="block text-sm font-semibold text-brand-textLabel mb-1">Endereço</label>
                                    <input wire:model="logradouro" type="text" readonly class="w-full rounded-md border border-brand-border px-3 py-2 bg-brand-bgInput text-gray-500 focus:outline-none">
                                </div>

                                <div class="col-span-12 md:col-span-2">
                                    <label class="block text-sm font-semibold text-brand-textLabel mb-1">Número</label>
                                    <input wire:model="numero" type="number" min="0" class="w-full rounded-md border border-brand-border px-3 py-2 bg-brand-bgInput text-gray-500 focus:outline-none">
                                </div>

                                <div class="col-span-12 md:col-span-5">
                                    <label class="block text-sm font-semibold text-brand-textLabel mb-1">Bairro</label>
                                    <input wire:model="bairro" type="text" readonly class="w-full rounded-md border border-brand-border px-3 py-2 bg-brand-bgInput text-gray-500 focus:outline-none">
                                </div>

                                <div class="col-span-12 md:col-span-5">
                                    <label class="block text-sm font-semibold text-brand-textLabel mb-1">Cidade</label>
                                    <input wire:model="cidade" type="text" readonly class="w-full rounded-md border border-brand-border px-3 py-2 bg-brand-bgInput text-gray-500 focus:outline-none">
                                </div>

                                <div class="col-span-12 md:col-span-2">
                                    <label class="block text-sm font-semibold text-brand-textLabel mb-1">Estado</label>
                                    <input wire:model="estado" type="text" readonly class="w-full rounded-md border border-brand-border px-3 py-2 bg-brand-bgInput text-gray-500 focus:outline-none">
                                </div>

                                <div class="col-span-6 md:col-span-6">
                                    <label class="block text-sm font-semibold text-brand-textLabel mb-1">Complemento</label>
                                    <input wire:model="complemento" type="text" class="w-full rounded-md border border-brand-border px-3 py-2 bg-brand-bgInput text-gray-500 focus:outline-none">
                                </div>

                                <div class="col-span-6 md:col-span-6">
                                    <label class="block text-sm font-semibold text-brand-textLabel mb-1">Região</label>
                                    <select wire:model.live="regiao" class="w-full rounded-md border px-3 py-2 focus:ring-brand-purple @error('unidade') border-red-500 @else @if(!empty($unidade)) border-green-500 @else border-brand-border @endif @enderror">
                                        <option value="">Selecione...</option>
                                        <option value="centro">Centro</option>
                                        <option value="norte">Norte</option>
                                        <option value="sul">Sul</option>
                                        <option value="leste">Leste</option>
                                        <option value="oeste">Oeste</option>
                                    </select>
                                </div>
                            
                                {{-- BLOCO INTELIGENTE: SELEÇÃO DE CURSO/UNIDADE --}}
                                @if(empty($data_nascimento) || empty($estado))
                                    <div class="col-span-12 mt-4 p-5 bg-gray-50 border border-gray-200 rounded-md text-center">
                                        <p class="text-gray-500 text-sm m-0">Preencha sua <b>Data de Nascimento</b> e o <b>CEP</b> acima para visualizarmos os cursos disponíveis para o seu perfil e região.</p>
                                    </div>
                                @elseif(count($unidadesDisponiveis) > 0)
                                    <div class="col-span-12 mt-4 p-5 bg-purple-50 rounded-md border border-purple-100 grid grid-cols-1 gap-4">
                                        <h5 class="font-bold text-brand-purple">Cursos Disponíveis para o seu perfil</h5>
                                        
                                        <div>
                                            <label class="block text-sm font-semibold text-brand-textLabel mb-1">Unidade <span class="text-red-500">*</span></label>
                                            <select wire:model.live="unidade" class="w-full rounded-md border px-3 py-2 focus:ring-brand-purple @error('unidade') border-red-500 @else @if(!empty($unidade)) border-green-500 @else border-brand-border @endif @enderror">
                                                <option value="">Selecione a Unidade...</option>
                                                @foreach($unidadesDisponiveis as $id => $nome)
                                                    <option value="{{ $id }}">{{ $nome }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        @if(count($cursosDisponiveis) > 0)
                                            <div>
                                                <label class="block text-sm font-semibold text-brand-textLabel mb-1">Curso de Interesse <span class="text-red-500">*</span></label>
                                                <select wire:model.live="curso" class="w-full rounded-md border px-3 py-2 focus:ring-brand-purple @error('curso') border-red-500 @else @if(!empty($curso)) border-green-500 @else border-brand-border @endif @enderror">
                                                    <option value="">Selecione o Curso...</option>
                                                    @foreach($cursosDisponiveis as $id => $nome)
                                                        <option value="{{ $id }}">{{ $nome }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endif

                                        @if(count($turnosDisponiveis) > 0)
                                            <div>
                                                <label class="block text-sm font-semibold text-brand-textLabel mb-1">Turno <span class="text-red-500">*</span></label>
                                                <select wire:model.live="turno" class="w-full rounded-md border px-3 py-2 focus:ring-brand-purple @error('turno') border-red-500 @else @if(!empty($turno)) border-green-500 @else border-brand-border @endif @enderror">
                                                    <option value="">Selecione o Turno...</option>
                                                    @foreach($turnosDisponiveis as $id => $nome)
                                                        <option value="{{ $id }}">{{ $nome }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="col-span-12 mt-4 p-5 bg-red-50 border border-red-200 rounded-md text-center">
                                        <p class="text-red-600 font-bold m-0">Não há vagas disponíveis para a sua idade na localidade selecionada.</p>
                                    </div>
                                @endif
                            @endif

                            @if($camposDinamicos && $camposDinamicos->where('etapa', $etapaAtual)->count() > 0)
                                <div class="col-span-12 mt-6 pt-4 border-t border-gray-100 mb-2 border-b pb-2">
                                    <h4 class="text-xl font-bold text-gray-800">
                                        {{ $etapaAtual === 1 ? 'Informações Adicionais' : 'Informações Complementares' }}
                                    </h4>
                                </div>

                                @include('livewire.website.partials.render-dinamico', ['etapa' => $etapaAtual])
                            @endif
                            
                            {{-- ========================================== --}}
                            {{-- 3. TERMOS E CONDIÇÕES (Apenas Último Passo) --}}
                            {{-- ========================================== --}}
                            @if($etapaAtual === $totalEtapas)
                                <div class="col-span-12 mt-6 pt-4 border-t border-gray-200">
                                    <h4 class="text-xl font-bold text-gray-800 mb-4">Informações Finais</h4>
                                    <div class="p-4 bg-brand-bgInput border border-brand-border rounded-md">
                                        <div class="flex items-start">
                                            <div class="flex items-center h-5">
                                                <input wire:model="autorizacao_uso_infos" id="checkTermos" type="checkbox" class="focus:ring-brand-purple h-4 w-4 text-brand-purple border-brand-border rounded">
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <label for="checkTermos" class="font-bold text-gray-800 text-xs uppercase cursor-pointer">
                                                    Autorizo uso das informações para fins educacionais e de empregabilidade, conforme política de privacidade.
                                                </label>
                                                @error('autorizacao_uso_infos') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        
                        {{-- Botões de Navegação --}}
                        <div class="flex justify-between mt-8 pt-6 border-t border-gray-200">
                            @if($etapaAtual > 1)
                                <button type="button" wire:click="voltarEtapa" class="border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-bold py-2 px-6 rounded-md transition duration-200">Voltar</button>
                            @else
                                <div></div>
                            @endif
                            
                            <button type="button" wire:click="avancarEtapa" class="{{ $etapaAtual === $totalEtapas ? 'text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600' : 'text-white transition-colors rounded-lg shadow-sm bg-purpura-500 hover:bg-purpura-600' }} text-white font-bold py-2 px-6 rounded-md transition duration-200 shadow-sm">
                                {{ $etapaAtual === $totalEtapas ? 'Finalizar Inscrição' : 'Próximo Passo' }}
                            </button>
                        </div>
                    </div>
                @endif
                {{-- TELA 99: SUCESSO --}}
                @if($etapaAtual === 99)
                <div class="text-center p-8 md:p-12">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 text-green-600 mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <h2 class="text-3xl font-bold text-green-600 mb-4">Inscrição Concluída!</h2>
                    <p class="text-gray-600 mb-8 leading-relaxed">
                        Olá! Recebemos a sua inscrição!<br>
                        Fique de olho em nossas redes sociais, em breve divulgaremos a lista de selecionados.<br>
                        Qualquer dúvida, acione nossa Central de Atendimento. Desejamos muito sucesso!
                    </p>
                    <button type="button" onclick="window.location.reload()" class="bg-brand-purple hover:bg-brand-purpleHover text-white font-bold py-3 px-8 rounded-md shadow-md transition duration-200">Voltar ao Início</button>
                </div>
                @endif

                {{-- TELA 100: LISTA DE ESPERA --}}
                @if($etapaAtual === 100)
                <div class="text-center p-8 md:p-12">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-yellow-100 text-yellow-600 mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <h2 class="text-3xl font-bold text-yellow-600 mb-4">Inscrição na Lista de Espera!</h2>
                    <p class="text-gray-600 mb-8 leading-relaxed">
                        Seus dados foram salvos no banco de dados. Enviaremos uma notificação assim que novas vagas forem abertas para o seu perfil e localidade.
                    </p>
                    <button type="button" onclick="window.location.reload()" class="bg-brand-purple hover:bg-brand-purpleHover text-white font-bold py-3 px-8 rounded-md shadow-md transition duration-200">Voltar ao Início</button>
                </div>
                @endif
                
            @else
                <div class="bg-white rounded-xl shadow-md border-none p-8 md:p-12 card-form text-center mt-10">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-red-50 text-red-500 mb-6">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    
                    <h2 class="text-3xl font-bold text-gray-800 mb-4">Inscrições Fechadas</h2>
                    <p class="text-gray-600 mb-8 leading-relaxed text-lg">
                        No momento, não temos nenhum processo seletivo com inscrições abertas.<br>
                        Fique de olho em nossas redes sociais para não perder as próximas datas!
                    </p>
                    <a href="/" class="inline-block bg-brand-purple hover:bg-brand-purpleHover text-white font-bold py-3 px-8 rounded-md transition duration-200 shadow-sm">
                        Voltar ao Início
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>