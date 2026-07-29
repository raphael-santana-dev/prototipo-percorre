@props([
    'title',
    'subtitle' => null,
    'icon' => 'ph-cube',
    'bannerColor' => 'bg-indigo-600', // Cor de fundo do banner (Padrão)
    'iconColor' => 'text-indigo-600'  // Cor do ícone (Padrão)
])

<div class="relative mb-8 font-sans">
    
    <!-- 1. Banner Colorido Superior -->
    <!-- Usamos h-32 para dar altura e rounded-xl para as pontas arredondadas -->
    <div class="w-full h-32 {{ $bannerColor }} rounded-xl"></div>

    <!-- 2. Card Branco Flutuante (Sobreposto) -->
    <!-- A mágica acontece no margem negativa (-mt-12) que puxa o card para cima do banner -->
    <div class="relative mx-4 sm:mx-8 -mt-12 sm:-mt-14">
        <div class="flex flex-col sm:flex-row items-center sm:items-stretch bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 sm:p-5 gap-4 sm:gap-6">
            
            <!-- Caixa do Ícone -->
            <div class="flex items-center justify-center w-20 h-20 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-sm shrink-0 transition-transform hover:scale-105">
                <i class="text-4xl ph {{ $icon }} {{ $iconColor }}"></i>
            </div>
            
            <!-- Textos (Título e Subtítulo) -->
            <div class="flex-1 flex flex-col justify-center text-center sm:text-left">
                <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ $title }}</h1>
                @if($subtitle)
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">{!! $subtitle !!}</p>
                @endif
            </div>

            <!-- Espaço Direito (Para botões ou Status Badges informados na View) -->
            @if($slot->isNotEmpty())
                <div class="flex items-center justify-center sm:justify-end shrink-0 mt-4 sm:mt-0 gap-3">
                    {{ $slot }}
                </div>
            @endif
            
        </div>
    </div>
</div>