{{-- O calc(100vh - 80px) garante o encaixe perfeito da tela sem gerar scroll duplo --}}
<div class="flex flex-col md:flex-row h-[calc(100vh-80px)] bg-brand-bg overflow-hidden">
    {{-- Topo Mobile --}}
    <div class="flex md:hidden w-100 bg-brand-escuro h-20 items-center justify-center sticky top-0 z-[1050] shadow-md shrink-0">
        <img src="{{ Vite::asset('resources/images/logo-nav-white.svg') }}" class="max-h-11 object-contain" alt="Instituto Percorre">
    </div>

    <div class="w-full p-6 md:p-12 bg-brand-bg overflow-y-auto h-full painel-direito">
        <div class="max-w-3xl mx-auto form-container">
            <div class="mb-8 progresso-container">
                <div class="font-bold text-gray-700 mb-2 text-sm">Passo {{ 1 }} de {{ 5 }}</div>
                <div class="flex gap-2">
                    @for($i = 1; $i <= 5; $i++)
                        <div class="h-2 rounded-full w-full {{ 1 >= $i ? 'bg-yellow-400' : 'bg-gray-200' }}"></div>
                    @endfor
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-md border-none p-6 md:p-10 card-form">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
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
                    
                    <div class="col-span-12">
                        <label class="block text-sm font-semibold text-brand-textLabel mb-1">Nome Social <span class="text-red-500">*</span></label>
                        <input wire:model.live.debounce.500ms="nome_social" type="text" class="w-full rounded-md border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-purple/25 focus:border-brand-purple transition-colors @error('nome_social') border-red-500 bg-red-50 @else @if(!empty($nome_social)) border-green-500 bg-green-50 @else border-brand-border @endif @enderror">
                        @error('nome_social') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

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

                    <div class="col-span-12">
                        <label class="block text-sm font-semibold text-brand-textLabel mb-1">Qual a natureza da deficiência?</label>
                        <input wire:model="natureza_deficiencia" type="text" class="w-full rounded-md border border-brand-border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-purple/25 focus:border-brand-purple">
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="block text-sm font-semibold text-brand-textLabel mb-1">Celular / Telefone</label>
                        <input wire:model="celular" x-mask="(99) 99999-9999" type="text" placeholder="(00) 00000-0000" class="w-full rounded-md border border-brand-border px-3 py-2 focus:outline-none focus:border-brand-purple focus:ring-2 focus:ring-brand-purple/25 @if(!empty($celular)) border-green-500 bg-green-50 @endif">
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

                    <div class="col-span-12 md:col-span-12">
                        <label class="block text-sm font-semibold text-brand-textLabel mb-1">Complemento</label>
                        <input wire:model="complemento" type="text" class="w-full rounded-md border border-brand-border px-3 py-2 bg-brand-bgInput text-gray-500 focus:outline-none">
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>