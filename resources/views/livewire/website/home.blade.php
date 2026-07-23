<div class="flex flex-col items-center justify-center py-20 lg:py-32">
    <div class="px-4 mx-auto text-center max-w-7xl sm:px-6 lg:px-8">
        
        <!-- Badge / Tagline -->
        <div class="inline-flex items-center px-4 py-2 mb-8 text-sm font-semibold rounded-full text-purpura-700 bg-purpura-100 dark:bg-purpura-900/30 dark:text-purpura-300">
            <i class="ph-bold ph-sparkle mr-2"></i> O seu caminho começa aqui
        </div>

        <!-- Hero Title (Tipografia Display do Design System) -->
        <h1 class="text-5xl font-extrabold tracking-tight text-gray-900 dark:text-white sm:text-6xl lg:text-7xl">
            Transformando realidades através da <span class="text-transparent bg-clip-text bg-gradient-to-r from-purpura-500 to-petunia-500">Educação</span>
        </h1>
        
        <!-- Subtitle Body 18 -->
        <p class="max-w-2xl mx-auto mt-6 text-lg text-gray-600 dark:text-gray-300">
            Cursos 100% gratuitos de formação profissional para jovens de 15 a 29 anos. Escolha o caminho que quer percorrer e prepare-se para o mercado de trabalho.
        </p>

        <!-- CTAs Mobile (Aparecem só no celular, já que a navbar escondeu os botões) -->
        <div class="flex flex-col items-center justify-center gap-4 mt-10 md:hidden">
            <a href="{{ route('student.login') }}" class="w-full px-8 py-4 text-lg font-bold text-white rounded-xl bg-ponkan-500 hover:bg-ponkan-600 shadow-md">
                <i class="ph-bold ph-graduation-cap mr-2"></i> Portal do Estudante
            </a>
            <a href="{{ route('login') }}" class="w-full px-8 py-4 text-lg font-bold border-2 rounded-xl text-purpura-500 border-purpura-500 hover:bg-purpura-50 dark:hover:bg-gray-800">
                Acesso Administrativo
            </a>
        </div>
        
        <!-- CTAs Desktop (Opcionais no corpo da página) -->
        <div class="hidden md:flex items-center justify-center gap-6 mt-12">
            <a href="{{ route('student.login') }}" class="px-8 py-4 text-lg font-bold text-white transition-transform transform hover:-translate-y-1 rounded-xl bg-ponkan-500 hover:bg-ponkan-600 shadow-lg shadow-ponkan-500/30">
                Acessar Sala de Aula
            </a>
        </div>
    </div>
</div>