<div class="w-full max-w-md p-8 bg-white rounded-2xl shadow-xl border border-slate-100">
    <div class="mb-8 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-100 text-indigo-600 mb-4 text-3xl">
            🎓
        </div>
        <h2 class="text-2xl font-bold text-slate-900">Portal do Aluno</h2>
        <p class="text-sm text-slate-500 mt-1">Acesse seus cursos e materiais</p>
    </div>

    <form wire:submit="authenticate" class="space-y-6">
        <div>
            <label class="block text-sm font-medium text-slate-700">E-mail Escolar</label>
            <input type="email" wire:model="email" class="w-full mt-1 border-slate-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('email') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">Senha</label>
            <input type="password" wire:model="password" class="w-full mt-1 border-slate-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('password') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </div>

        <button type="submit" class="w-full px-4 py-3 text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 font-medium transition-colors">
            Entrar no Portal
        </button>
    </form>
</div>