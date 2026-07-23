<div class="w-full max-w-md p-8 bg-white rounded-lg shadow-md">
    <div class="mb-8 text-center">
        <h2 class="text-2xl font-bold text-gray-900">Acesso Restrito</h2>
        <p class="text-sm text-gray-600">Área Administrativa</p>
    </div>

    <form wire:submit="authenticate" class="space-y-6">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">E-mail</label>
            <input type="email" id="email" wire:model="email" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('email') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Senha</label>
            <input type="password" id="password" wire:model="password" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('password') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </div>

        <div class="flex items-center">
            <input type="checkbox" id="remember" wire:model="remember" class="text-blue-600 border-gray-300 rounded focus:ring-blue-500">
            <label for="remember" class="ml-2 text-sm text-gray-600">Lembrar de mim</label>
        </div>

        <button type="submit" class="w-full px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            Entrar
        </button>
    </form>
</div>