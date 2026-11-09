@extends('errors.layout')

@section('title', 'Sistema em Atualização')
@section('accent_bar', 'bg-purpura-600')
@section('icon_wrapper', 'bg-purpura-50 text-purpura-600 border-purpura-200 dark:bg-purpura-950/40 dark:text-purpura-400 dark:border-purpura-800')
@section('icon', 'ph-fill ph-wrench')
@section('badge_classes', 'bg-purpura-50 text-purpura-700 border-purpura-200 dark:bg-purpura-950/50 dark:text-purpura-300 dark:border-purpura-800')
@section('badge_label', 'Manutenção Rápida')

@section('heading', 'Estamos atualizando o sistema')

@section('message')
    Estamos aplicando melhorias neste exato momento para tornar sua experiência ainda melhor. O acesso estará de volta em poucos instantes.
@endsection

@section('custom_actions')
    <button type="button"
            onclick="window.location.reload();"
            class="w-full sm:w-auto px-6 py-2.5 rounded-xl text-xs font-extrabold text-white bg-purpura-600 hover:bg-purpura-700 shadow-sm transition flex items-center justify-center gap-2 cursor-pointer">
        <i class="ph-bold ph-arrows-clockwise text-sm"></i>
        <span>Verificar Disponibilidade</span>
    </button>
@endsection