<div>
    <!-- Faixa Amarela Superior ("IOS AGORA É Percorre") - 32px altura, #FFA301 -->
    <div class="w-full flex items-center justify-center" style="background-color: #FFA301; height: 32px; padding: 0 16px;">
        <img src="{{ Vite::asset('resources/images/ios-agora-e-percorre-1.png') }}" alt="IOS agora é Instituto Percorre" style="height: 32px; width: auto; margin: 0 auto; display: block;">
    </div>

    <!-- Seção Hero com Fundo Personalizado (bg-hero.svg) -->
    <section id="hero" class="relative flex items-center justify-center bg-[#310B47] bg-center bg-no-repeat bg-cover transition-colors duration-300 dark:bg-gray-900" style="background-image: url('{{ Vite::asset('resources/images/bg-hero.svg') }}'); min-height: 670px; padding: 100px 20px;">
        <div class="px-4 mx-auto text-center max-w-5xl sm:px-6 lg:px-8">

            <!-- Hero Title (Exatamente 48px, Poppins/Inter, cor pura #FFFFFF) -->
            <h1 class="text-[36px] sm:text-[44px] lg:text-[48px] font-bold tracking-tight text-white leading-[1.15] font-sans">
                Seu caminho para o mercado<br class="hidden sm:inline"> começa aqui.
            </h1>
            
            <!-- Subtitle -->
            <p class="max-w-2xl mx-auto mt-5 text-base sm:text-lg font-normal text-white/90">
                Cursos 100% gratuitos de formação profissional para jovens de 15 a 29 anos.
            </p>

            <!-- Condicional de Inscrições Abertas -->
            @if(isset($inscricoesAbertas) && $inscricoesAbertas)
                <div class="mt-8 p-3.5 px-6 bg-white/10 backdrop-blur-md rounded-full inline-flex flex-col sm:flex-row items-center gap-3 border border-white/20">
                    <span class="text-white font-medium text-sm">As inscrições estão abertas!</span>
                    <a href="{{ route('publico.inscricao') }}" class="px-5 py-2 text-xs font-bold uppercase tracking-wider text-[#310B47] bg-[#FFA301] hover:bg-[#e08e00] rounded-full transition-colors">
                        Inscreva-se
                    </a>
                </div>
            @endif

            <!-- CTAs Mobile (Pill Arredondado Minimalista) -->
            <div class="flex flex-col items-center justify-center gap-3 mt-10 md:hidden">
                <a href="{{ route('student.login') }}" class="w-full px-8 py-3.5 text-sm font-bold text-[#310B47] bg-[#FFA301] hover:bg-[#e08e00] rounded-full transition-colors">
                    Conheça os cursos
                </a>
                <a href="{{ route('login') }}" class="w-full px-8 py-3.5 text-sm font-semibold text-white border-2 border-white rounded-full hover:bg-white/10 transition-colors">
                    Como apoiar
                </a>
            </div>
            
            <!-- CTAs Desktop (Pill Arredondado - Fiel à captura DevTools) -->
            <div class="hidden md:flex items-center justify-center gap-4 mt-10">
                <a href="{{ route('student.login') }}" class="px-8 py-3.5 text-base font-bold text-[#310B47] bg-[#FFA301] hover:bg-[#e08e00] rounded-full transition-colors">
                    Conheça os cursos
                </a>
                <a href="{{ route('login') }}" class="px-8 py-3.5 text-base font-semibold text-white border-2 border-white rounded-full hover:bg-white/10 transition-colors">
                    Como apoiar
                </a>
            </div>
        </div>
    </section>
</div>