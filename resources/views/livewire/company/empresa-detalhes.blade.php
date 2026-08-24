<div class="p-6 max-w-7xl mx-auto font-sans relative">

    <x-page-header 
        title="Dossiê da Empresa Parceira" 
        icon="ph ph-buildings"
        badge="Integração ERP"
        :breadcrumbs="$breadcrumbs">

        <x-slot name="actions">
            <a href="{{ route('empresas.index') }}" wire:navigate class="px-4 py-2 text-sm font-bold border rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 flex items-center gap-2">
                <i class="ph-bold ph-arrow-left"></i> Voltar à Listagem
            </a>
        </x-slot>

    </x-page-header>

    {{-- DADOS CADASTRAIS (SOMENTE LEITURA) --}}
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                <i class="ph-fill ph-file-text text-purpura-500"></i> Dados da Integração
            </h3>
            @if($empresa->is_active)
                <span class="px-3 py-1 bg-green-50 text-green-700 border border-green-200 font-bold text-xs uppercase tracking-wider rounded-md">Cadastro Ativo</span>
            @else
                <span class="px-3 py-1 bg-red-50 text-red-700 border border-red-200 font-bold text-xs uppercase tracking-wider rounded-md">Cadastro Inativo</span>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Razão Social</label>
                <input type="text" readonly value="{{ $empresa->razao_social }}" class="w-full text-sm rounded-lg border-gray-200 bg-gray-50 text-gray-600 dark:bg-gray-900/50 dark:border-gray-700 dark:text-gray-400 cursor-not-allowed">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Nome Fantasia</label>
                <input type="text" readonly value="{{ $empresa->nome_fantasia }}" class="w-full text-sm rounded-lg border-gray-200 bg-gray-50 text-gray-600 dark:bg-gray-900/50 dark:border-gray-700 dark:text-gray-400 cursor-not-allowed">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">CNPJ</label>
                <input type="text" readonly value="{{ preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', str_pad($empresa->cnpj, 14, '0', STR_PAD_LEFT)) }}" class="w-full text-sm rounded-lg border-gray-200 bg-gray-50 text-gray-600 dark:bg-gray-900/50 dark:border-gray-700 dark:text-gray-400 cursor-not-allowed font-mono font-bold">
            </div>
        </div>
    </div>

    {{-- NAVEGAÇÃO DE ABAS --}}
    <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
            <li class="mr-2">
                <button wire:click="setAba('aprendizes')" class="inline-flex items-center gap-2 p-4 border-b-2 rounded-t-lg transition-colors {{ $abaAtual === 'aprendizes' ? 'text-purpura-600 border-purpura-600 dark:text-purpura-400 dark:border-purpura-400' : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300' }}">
                    <i class="ph-fill ph-student text-lg"></i> Aprendizes Vinculados
                    <span class="px-2 py-0.5 text-[10px] bg-gray-100 dark:bg-gray-700 rounded-full">{{ $empresa->aprendizes->count() }}</span>
                </button>
            </li>
            <li class="mr-2">
                <button wire:click="setAba('contatos')" class="inline-flex items-center gap-2 p-4 border-b-2 rounded-t-lg transition-colors {{ $abaAtual === 'contatos' ? 'text-purpura-600 border-purpura-600 dark:text-purpura-400 dark:border-purpura-400' : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300' }}">
                    <i class="ph-fill ph-users-three text-lg"></i> Equipe e Avaliadores
                    <span class="px-2 py-0.5 text-[10px] bg-gray-100 dark:bg-gray-700 rounded-full">{{ $empresa->companyUsers->count() }}</span>
                </button>
            </li>
        </ul>
    </div>

    {{-- CONTEÚDO DAS ABAS --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        
        {{-- ABA 1: APRENDIZES --}}
        @if($abaAtual === 'aprendizes')
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 font-bold uppercase text-[10px] tracking-wider border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-6 py-4">Nome do Aprendiz</th>
                            <th class="px-6 py-4">Unidade Instituto</th>
                            <th class="px-6 py-4">Gestor Responsável</th>
                            <th class="px-6 py-4 text-center">Status Matrícula</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($empresa->aprendizes as $aluno)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900 dark:text-white">{{ $aluno->name }}</div>
                                    <div class="text-xs text-gray-500 font-mono">CPF: {{ preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $aluno->cpf) }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1 text-xs font-bold text-gray-600 dark:text-gray-300">
                                        <i class="ph-fill ph-map-pin text-purpura-500"></i> {{ $aluno->unidade->nome ?? 'Não Alocado' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($aluno->gestor)
                                        <div class="text-sm font-bold text-gray-700 dark:text-gray-300"><i class="ph-fill ph-user-circle text-gray-400"></i> {{ $aluno->gestor->name }}</div>
                                    @else
                                        <span class="text-xs font-bold italic text-yellow-600 bg-yellow-50 px-2 py-1 rounded">Aguardando Vínculo</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($aluno->is_active)
                                        <span class="px-2.5 py-1 text-[10px] font-bold rounded border bg-green-50 text-green-700 border-green-200">Ativa</span>
                                    @else
                                        <span class="px-2.5 py-1 text-[10px] font-bold rounded border bg-red-50 text-red-700 border-red-200">Inativa</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                    <i class="ph ph-student text-4xl mb-3 text-gray-300"></i>
                                    <p class="font-bold">Nenhum aprendiz alocado nesta empresa.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ABA 2: EQUIPE E AVALIADORES --}}
        @if($abaAtual === 'contatos')
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 font-bold uppercase text-[10px] tracking-wider border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-6 py-4">Nome e Documento</th>
                            <th class="px-6 py-4">E-mail de Acesso</th>
                            <th class="px-6 py-4">Tipo de Perfil</th>
                            <th class="px-6 py-4 text-center">Acesso</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($empresa->companyUsers as $user)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900 dark:text-white">{{ $user->name }}</div>
                                    <div class="text-xs text-gray-500 font-mono">Doc: {{ $user->documento }}</div>
                                </td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                    {{ $user->email }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($user->tipo_acesso === 'contato_principal')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-md text-xs font-bold uppercase tracking-wider">
                                            <i class="ph-fill ph-star"></i> Contato Principal
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 text-blue-700 border border-blue-200 rounded-md text-xs font-bold uppercase tracking-wider">
                                            <i class="ph-fill ph-user"></i> Gestor / Avaliador
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($user->is_active)
                                        <span class="px-2.5 py-1 text-[10px] font-bold rounded border bg-green-50 text-green-700 border-green-200">Permitido</span>
                                    @else
                                        <span class="px-2.5 py-1 text-[10px] font-bold rounded border bg-red-50 text-red-700 border-red-200">Bloqueado</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                    <i class="ph ph-users-slash text-4xl mb-3 text-gray-300"></i>
                                    <p class="font-bold">Nenhum usuário corporativo cadastrado.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

    </div>
</div>