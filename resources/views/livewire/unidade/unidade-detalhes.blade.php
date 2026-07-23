<div class="space-y-6">
    <div class="flex items-center justify-between border-b pb-4 border-gray-200">
        <h1 class="text-3xl font-bold text-purpura-700 flex items-center gap-2">
            <i class="ph ph-book-bookmark"></i> {{ $unidade->nome }}
        </h1>
        <a href="{{ route('unidades.index') }}" class="px-4 py-2 text-sm font-bold border rounded-lg text-gray-500 border-gray-500 hover:bg-gray-100">Voltar</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Card de Informações -->
        <div class="p-6 bg-white border rounded-xl border-gray-200 shadow-gray-2">
            <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2"><i class="ph ph-map-pin"></i> Dados Cadastrais</h3>
            <ul class="space-y-3 text-sm text-gray-800">
                <li><strong>Endereço:</strong> {{ $unidade->endereco ?? 'Não informado' }}</li>
                <li><strong>E-mail:</strong> {{ $unidade->email ?? 'Não informado' }}</li>
                <li><strong>Contato:</strong> {{ $unidade->contatos ?? 'Não informado' }}</li>
                <li><strong>Status:</strong> {{ $unidade->status ? 'Unidade Operacional' : 'Fechada' }}</li>
            </ul>
        </div>

        <!-- Card de Professores Vinculados -->
        <div class="p-6 bg-white border rounded-xl border-gray-200 shadow-gray-2">
            <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2"><i class="ph ph-users"></i> Professores Vinculados</h3>
            <ul class="space-y-2 divide-y divide-gray-100">
                @forelse($unidade->usuarios as $user)
                    <li class="py-2 flex justify-between items-center text-sm">
                        <span class="font-semibold text-gray-900">{{ $user->name }}</span>
                        <span class="text-xs text-gray-500">{{ $user->email }}</span>
                    </li>
                @empty
                    <li class="py-2 text-sm text-gray-500 italic">Nenhum professor alocado nesta unidade.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>