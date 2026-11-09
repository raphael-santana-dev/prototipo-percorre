@extends('errors.layout')

@section('title', 'Instabilidade Temporária')
@section('accent_bar', 'bg-red-500')
@section('icon_wrapper', 'bg-red-50 text-red-600 border-red-200 dark:bg-red-950/40 dark:text-red-400 dark:border-red-800')
@section('icon', 'ph-fill ph-cloud-warning')
@section('badge_classes', 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/50 dark:text-red-300 dark:border-red-800')
@section('badge_label', 'Instabilidade Interna')

@section('heading', 'Ops! Algo não saiu como esperado')

@section('message')
    Encontramos uma instabilidade inesperada ao processar a sua solicitação. Não se preocupe: seus dados estão seguros e a nossa equipe técnica já recebeu o registro automático desta ocorrência.
@endsection

@section('extra_content')
    <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 flex items-center justify-between text-xs text-gray-600 dark:text-gray-300">
        <span class="flex items-center gap-2 font-medium">
            <i class="ph-fill ph-check-circle text-emerald-500 text-base"></i>
            Registro automático enviado ao suporte
        </span>
        <span class="font-mono text-[11px] text-gray-400">
            {{ now()->format('d/m H:i') }}
        </span>
    </div>
@endsection