{{-- resources/views/components/partials/analytics-heatmap.blade.php --}}
@if(config('services.clarity.id'))
<script type="text/javascript">
    (function(c,l,a,r,i,t,y){
        c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
        t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
        y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
    })(window, document, "clarity", "script", "{{ config('services.clarity.id') }}");

    @if(auth('web')->check())
        window.clarity("identify", "admin_{{ auth('web')->id() }}", "", "", "{{ auth('web')->user()->name }}");
        window.clarity("set", "user_type", "admin");
    @elseif(auth('student')->check())
        window.clarity("identify", "student_{{ auth('student')->id() }}", "", "", "{{ auth('student')->user()->name }}");
        window.clarity("set", "user_type", "student");
    @elseif(auth('company')->check())
        window.clarity("identify", "company_{{ auth('company')->id() }}", "", "", "{{ auth('company')->user()->name }}");
        window.clarity("set", "user_type", "company");
    @endif

    // Suporte a navegação SPA do Livewire 3 e eventos de conversão
    document.addEventListener('livewire:navigated', () => {
        if (typeof window.fbq === 'function') window.fbq('track', 'PageView');
    });
    window.addEventListener('inscricao-concluida', () => {
        if (typeof window.fbq === 'function') window.fbq('track', 'CompleteRegistration');
        if (typeof window.gtag === 'function') window.gtag('event', 'generate_lead');
    });
</script>
@endif

@if(config('services.meta.pixel_id'))
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '{{ config('services.meta.pixel_id') }}');
fbq('track', 'PageView');
</script>
@endif

@if(config('services.google.gtag_id'))
<script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google.gtag_id') }}"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '{{ config('services.google.gtag_id') }}');
</script>
@endif