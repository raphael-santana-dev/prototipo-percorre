@php
    $isAuthenticated = auth('web')->check() || auth('student')->check() || auth('company')->check();
    $lifetimeMinutes = (int) config('session.lifetime', 120);
    $warningMinutes  = (int) config('session.warning_time', 2);

    // Garante que o aviso seja menor que o tempo total
    if ($warningMinutes >= $lifetimeMinutes) {
        $warningMinutes = max(1, (int) floor($lifetimeMinutes / 2));
    }

    $totalTimeoutSeconds = $lifetimeMinutes * 60;
    $warningSeconds      = $warningMinutes * 60;
@endphp

@if($isAuthenticated)
<div x-data="{
        totalSeconds: {{ $totalTimeoutSeconds }},
        warningThreshold: {{ $warningSeconds }},
        remainingSeconds: {{ $totalTimeoutSeconds }},
        showWarningModal: false,
        isLoggingOut: false,
        storageKey: 'percorre_last_activity_ts',
        logoutStorageKey: 'percorre_force_logout_ts',
        lastServerPing: Date.now(),
        lastLocalThrottle: 0,
        timerInterval: null,

        init() {
            const now = Date.now();
            localStorage.setItem(this.storageKey, now.toString());

            // Escuta interações reais do humano (com throttle de 2s para performance máxima)
            const events = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click'];
            events.forEach(evt => {
                window.addEventListener(evt, () => this.registerHumanActivity(), { passive: true });
            });

            // Sincroniza atividade e logout entre múltiplas abas abertas
            window.addEventListener('storage', (e) => {
                if (e.key === this.storageKey && e.newValue) {
                    this.recalculateRemaining();
                    if (this.remainingSeconds > this.warningThreshold) {
                        this.showWarningModal = false;
                    }
                }
                if (e.key === this.logoutStorageKey && e.newValue) {
                    this.submitLogoutForm();
                }
            });

            // Loop principal a cada 1 segundo
            this.timerInterval = setInterval(() => this.tick(), 1000);
        },

        registerHumanActivity() {
            // Se o modal de alerta já está aberto, exige clique explícito no botão 'Continuar Conectado'
            if (this.showWarningModal || this.isLoggingOut) return;

            const now = Date.now();
            if (now - this.lastLocalThrottle < 2000) return;
            this.lastLocalThrottle = now;

            localStorage.setItem(this.storageKey, now.toString());
            this.remainingSeconds = this.totalSeconds;

            // Se o usuário está ativo no navegador há mais de 3 minutos sem fazer requisição HTTP,
            // envia um keep-alive silencioso para manter o servidor sincronizado
            if (now - this.lastServerPing > 180000) {
                this.pingServer(false);
            }
        },

        recalculateRemaining() {
            const lastTs = parseInt(localStorage.getItem(this.storageKey) || Date.now().toString(), 10);
            const elapsedSeconds = Math.floor((Date.now() - lastTs) / 1000);
            this.remainingSeconds = Math.max(0, this.totalSeconds - elapsedSeconds);
        },

        tick() {
            if (this.isLoggingOut) return;

            this.recalculateRemaining();

            if (this.remainingSeconds <= 0) {
                this.triggerLogout();
                return;
            }

            if (this.remainingSeconds <= this.warningThreshold) {
                this.showWarningModal = true;
            } else {
                this.showWarningModal = false;
            }
        },

        keepAlive() {
            const now = Date.now();
            localStorage.setItem(this.storageKey, now.toString());
            this.remainingSeconds = this.totalSeconds;
            this.showWarningModal = false;
            this.pingServer(true);
        },

        pingServer(showFeedback = false) {
            this.lastServerPing = Date.now();
            fetch('{{ route('session.keep-alive') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ active: true })
            }).then(res => {
                if (res.status === 401 || res.status === 419) {
                    this.triggerLogout();
                } else if (showFeedback) {
                    window.dispatchEvent(new CustomEvent('sucesso', { detail: { msg: 'Sessão renovada com sucesso!' } }));
                }
            }).catch(() => {});
        },

        triggerLogout() {
            if (this.isLoggingOut) return;
            this.isLoggingOut = true;
            clearInterval(this.timerInterval);
            localStorage.setItem(this.logoutStorageKey, Date.now().toString());
            this.submitLogoutForm();
        },

        submitLogoutForm() {
            if (this.$refs.logoutForm) {
                this.$refs.logoutForm.submit();
            } else {
                window.location.href = '/login?expired=1';
            }
        },

        formatTime(sec) {
            const m = Math.floor(sec / 60).toString().padStart(2, '0');
            const s = (sec % 60).toString().padStart(2, '0');
            return `${m}:${s}`;
        }
     }"
     x-cloak>

    {{-- Formulário oculto para encerrar a sessão via POST seguro --}}
    <form x-ref="logoutForm" method="POST" action="{{ route('session.timeout-logout') }}" class="hidden">
        @csrf
    </form>

    {{-- MODAL BLOQUEANTE DE INATIVIDADE --}}
    <div x-show="showWarningModal"
         x-transition.opacity.duration.250ms
         class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/75 backdrop-blur-sm px-4">
        
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full border border-gray-200 dark:border-gray-700 overflow-hidden animate-fade-in-up">
            <div class="h-1.5 w-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                <div class="h-full bg-amber-500 transition-all duration-1000 ease-linear"
                     :style="`width: ${Math.min(100, Math.round((remainingSeconds / warningThreshold) * 100))}%`"></div>
            </div>

            <div class="p-6 text-center space-y-4">
                <div class="w-16 h-16 rounded-full bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700 text-amber-600 dark:text-amber-400 flex items-center justify-center mx-auto shadow-xs">
                    <i class="ph-fill ph-timer text-3xl animate-pulse"></i>
                </div>

                <div>
                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">
                        Sessão Prestes a Expirar
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                        Por motivos de segurança, sua conexão será encerrada automaticamente por inatividade em:
                    </p>
                </div>

                <div class="py-2.5 px-4 bg-gray-50 dark:bg-gray-900/70 rounded-xl border border-gray-200 dark:border-gray-700 inline-block">
                    <span class="font-mono text-3xl font-black tracking-wider text-amber-600 dark:text-amber-400"
                          x-text="formatTime(remainingSeconds)">02:00</span>
                </div>

                <div class="flex flex-col sm:flex-row gap-2.5 pt-2">
                    <button type="button"
                            @click="triggerLogout()"
                            class="w-full sm:w-1/2 py-2.5 px-4 rounded-xl text-xs font-bold text-gray-600 dark:text-gray-300 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 transition">
                        Sair Agora
                    </button>
                    <button type="button"
                            @click="keepAlive()"
                            class="w-full sm:w-1/2 py-2.5 px-4 rounded-xl text-xs font-extrabold text-white bg-purpura-600 hover:bg-purpura-700 shadow-md transition flex items-center justify-center gap-1.5">
                        <i class="ph-bold ph-check-circle text-sm"></i> Continuar Conectado
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif