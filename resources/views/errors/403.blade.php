@extends('errors.layout')

@php
    // Identifica de forma segura se o bloqueio 403 foi causado por uma Feature Toggle desativada ou por Permissão ACL
    $rawMessage = isset($exception) ? mb_strtolower((string) $exception->getMessage(), 'UTF-8') : '';

    $isFeatureToggle = str_contains($rawMessage, 'desativad')
        || str_contains($rawMessage, 'feature')
        || str_contains($rawMessage, 'módulo')
        || str_contains($rawMessage, 'modulo')
        || str_contains($rawMessage, 'temporariamente');

    // Identifica o usuário logado e seu perfil de forma amigável (sem expor dados sensíveis)
    $usuarioNome = null;
    $perfilAtual = null;

    if (auth('web')->check()) {
        $u = auth('web')->user();
        $usuarioNome = $u->name;
        $perfilAtual = method_exists($u, 'getRoleNames') ? ($u->getRoleNames()->first() ?: 'Colaborador') : 'Colaborador';
    } elseif (auth('student')->check()) {
        $usuarioNome = auth('student')->user()->name;
        $perfilAtual = 'Estudante';
    } elseif (auth('company')->check()) {
        $usuarioNome = auth('company')->user()->name;
        $perfilAtual = 'Empresa Parceira';
    }
@endphp

@section('title', $isFeatureToggle ? 'Funcionalidade Desativada' : 'Acesso Restrito')

@section('accent_bar', $isFeatureToggle ? 'bg-amber-500' : 'bg-purpura-600')

@section('icon_wrapper', $isFeatureToggle
    ? 'bg-amber-50 text-amber-600 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800'
    : 'bg-purpura-50 text-purpura-600 border-purpura-200 dark:bg-purpura-950/40 dark:text-purpura-400 dark:border-purpura-800')

@section('icon', $isFeatureToggle ? 'ph-fill ph-toggle-left' : 'ph-fill ph-shield-Warning')

@section('badge_classes', $isFeatureToggle
    ? 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800'
    : 'bg-purpura-50 text-purpura-700 border-purpura-200 dark:bg-purpura-950/50 dark:text-purpura-300 dark:border-purpura-800')

@section('badge_label', $isFeatureToggle ? 'Módulo Desativado no Sistema' : 'Permissão de Acesso Necessária')

@section('heading', $isFeatureToggle
    ? 'Esta funcionalidade está temporariamente desativada'
    : 'Você não possui permissão para acessar esta área')

@section('message')
    @if($isFeatureToggle)
        O recurso ou módulo que você tentou acessar encontra-se desligado no momento por definição da administração do sistema.
    @else
        O seu perfil de acesso atual não possui a liberação necessária para visualizar esta página ou executar esta operação.
    @endif
@endsection

@section('extra_content')
    <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 space-y-2.5 text-xs">
        @if($usuarioNome)
            <div class="flex items-center justify-between gap-2 pb-2 border-b border-gray-200/80 dark:border-gray-700">
                <span class="text-gray-500 dark:text-gray-400 font-medium">Conta conectada:</span>
                <span class="font-bold text-gray-800 dark:text-gray-200 truncate">
                    {{ $usuarioNome }}
                    @if($perfilAtual)
                        <span class="ml-1 px-2 py-0.5 rounded bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-[10px] uppercase text-purpura-600 dark:text-purpura-400">
                            {{ $perfilAtual }}
                        </span>
                    @endif
                </span>
            </div>
        @endif

        <div class="flex items-start gap-2.5 text-gray-600 dark:text-gray-300 leading-relaxed">
            <i class="ph-fill ph-info text-base {{ $isFeatureToggle ? 'text-amber-500' : 'text-purpura-500' }} shrink-0 mt-0.5"></i>
            @if($isFeatureToggle)
                <span>Caso esta funcionalidade precise ser utilizada agora, solicite ao administrador a ativação da chave correspondente em <strong>Feature Toggles</strong>.</span>
            @else
                <span>Se você precisa acessar este recurso para realizar suas atividades, solicite à coordenação ou ao administrador a liberação da permissão no seu perfil de acesso.</span>
            @endif
        </div>
    </div>
@endsection