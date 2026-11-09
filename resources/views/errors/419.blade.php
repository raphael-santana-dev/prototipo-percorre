@extends('errors.layout')

@section('title', 'Sessão Expirada')
@section('accent_bar', 'bg-amber-500')
@section('icon_wrapper', 'bg-amber-50 text-amber-600 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800')
@section('icon', 'ph-fill ph-clock-counter-clockwise')
@section('badge_classes', 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800')
@section('badge_label', 'Tempo de Segurança Expirado')

@section('heading', 'Esta página expirou por inatividade')

@section('message')
    Para proteger as suas informações, a conexão desta página foi encerrada porque ela permaneceu aberta sem interação por muito tempo. Atualize a página ou acesse novamente para continuar.
@endsection

@section('custom_actions')
    <button type="button"
            onclick="window.location.reload();"
            class="w-full sm:w-auto px-5 py-2.5 rounded-xl text-xs font-extrabold text-white bg-purpura-600 hover:bg-purpura-700 shadow-sm transition flex items-center justify-center gap-2 cursor-pointer">
        <i class="ph-bold ph-arrows-clockwise text-sm"></i>
        <span>Atualizar e Continuar</span>
    </button>

    <a href="{{ url('/login') }}"
       class="w-full sm:w-auto px-5 py-2.5 rounded-xl text-xs font-bold text-gray-700 dark:text-gray-200 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 transition flex items-center justify-center gap-2">
        <i class="ph-bold ph-sign-in text-sm"></i>
        <span>Ir para o Login</span>
    </a>
@endsection