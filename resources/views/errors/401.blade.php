@extends('errors.layout')

@section('title', 'Identificação Necessária')
@section('accent_bar', 'bg-blue-600')
@section('icon_wrapper', 'bg-blue-50 text-blue-600 border-blue-200 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-800')
@section('icon', 'ph-fill ph-user-circle-gear')
@section('badge_classes', 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-800')
@section('badge_label', 'Autenticação Obrigatória')

@section('heading', 'Faça login para acessar esta página')

@section('message')
    Você precisa estar conectado à sua conta para visualizar este conteúdo. Entre com suas credenciais para continuar.
@endsection

@section('custom_actions')
    <a href="{{ Route::has('login') ? route('login') : url('/login') }}"
       class="w-full sm:w-auto px-6 py-2.5 rounded-xl text-xs font-extrabold text-white bg-purpura-600 hover:bg-purpura-700 shadow-sm transition flex items-center justify-center gap-2">
        <i class="ph-bold ph-sign-in text-sm"></i>
        <span>Acessar Minha Conta</span>
    </a>
@endsection